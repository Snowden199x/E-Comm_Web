<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\ShCheckpointScanEvent;
use App\Models\Profiles\ShScannerStation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShCheckpointScanWorkflow
{
    public function lookup(ShScannerStation $station, string $trackingNumber): array
    {
        $order = Order::query()->where('tracking_number', $trackingNumber)
            ->with(['routePlan.stops.checkpoint', 'destinationLogisticsCenter'])
            ->firstOrFail();
        $this->authorizeNextStop($station, $order);

        return [
            'tracking_number' => $order->tracking_number,
            'order_number' => $order->number,
            'checkpoint' => $this->checkpointData($station),
            'message' => 'Confirm arrival and sorting at '.$station->checkpoint->code.' — '.$station->checkpoint->name.'.',
        ];
    }

    public function record(ShScannerStation $station, string $trackingNumber, string $scanKey): array
    {
        return DB::transaction(function () use ($station, $trackingNumber, $scanKey) {
            $station = ShScannerStation::query()->with('checkpoint')
                ->lockForUpdate()->findOrFail($station->id);
            abort_unless(! $station->revoked_at && $station->token_hash
                && $station->token_expires_at?->isFuture() && $station->checkpoint?->is_active,
                401, 'Scanner session is expired or revoked. Pair again.');
            $order = Order::query()->where('tracking_number', $trackingNumber)
                ->lockForUpdate()->firstOrFail();
            $existing = ShCheckpointScanEvent::query()->where('scan_key', $scanKey)->first();
            if ($existing) {
                abort_unless($existing->order_id === $order->id && $existing->station_id === $station->id, 409,
                    'This scan key belongs to a different parcel or station.');

                return $this->scanResponse($order, $existing, true);
            }

            $this->authorizeNextStop($station, $order);
            $plan = $order->routePlan()->with('stops.checkpoint', 'toLogisticsCenter')->firstOrFail();
            $stop = $plan->stops->firstWhere('position', $order->route_step);
            abort_unless($stop && $stop->checkpoint_id === $station->checkpoint_id, 409,
                'The parcel route changed. Refresh and scan again.');

            $next = $plan->stops->firstWhere('position', $stop->position + 1);
            $fromStatus = $order->status;
            $order->next_route_checkpoint_id = $next?->checkpoint_id;
            $order->route_step = $next?->position;
            if ($fromStatus === 'sorted') {
                $order->status = 'in_transit_to_hub';
            }
            // SH notifications below use the actual confirmed locality. The
            // generic status observer would otherwise send a second message.
            Order::withoutEvents(fn () => $order->save());

            $event = ShCheckpointScanEvent::query()->create([
                'order_id' => $order->id,
                'station_id' => $station->id,
                'checkpoint_id' => $station->checkpoint_id,
                'route_plan_id' => $plan->id,
                'route_step' => $stop->position,
                'scan_key' => $scanKey,
            ]);
            $note = 'Scanner confirmed arrival and sorting at '
                .$station->checkpoint->code.' — '.$station->checkpoint->name.'. '
                .($next
                    ? 'Next planned checkpoint: '.$next->checkpoint->code.' — '.$next->checkpoint->name.'.'
                    : 'Next planned stop: '.$plan->toLogisticsCenter?->business_name.' Main Hub; receipt there is pending.');
            $note = Str::limit($note, 500, '');
            $order->statusEvents()->create([
                'user_id' => null,
                'from_status' => $fromStatus,
                'to_status' => $order->status,
                'note' => $note,
            ]);
            foreach (array_unique(array_filter([
                $order->buyer_id,
                $order->seller_id,
                $order->logisticsCenter?->user_id,
                $order->destinationLogisticsCenter?->user_id,
            ])) as $userId) {
                $link = $userId === $order->buyer_id ? route('buyer.orders.show', $order)
                    : ($userId === $order->seller_id ? route('seller.orders.show', $order)
                        : route('logistics.delivery-monitoring'));
                Notification::query()->create([
                    'user_id' => $userId,
                    'type' => 'shipment_update',
                    'title' => $order->number.': '.$station->checkpoint->code.' scan confirmed',
                    'message' => $note,
                    'link' => $link,
                ]);
            }

            return $this->scanResponse($order, $event, false);
        }, 3);
    }

    public function history(ShScannerStation $station): array
    {
        return ShCheckpointScanEvent::query()->where('station_id', $station->id)
            ->with(['order:id,number,tracking_number', 'checkpoint:id,code,name'])
            ->latest()->limit(20)->get()->map(fn ($event) => [
                'tracking_number' => $event->order?->tracking_number,
                'checkpoint' => $event->checkpoint?->code,
                'scanned_at' => $event->created_at?->toIso8601String(),
            ])->all();
    }

    private function authorizeNextStop(ShScannerStation $station, Order $order): void
    {
        abort_unless(in_array($order->status, ['sorted', 'in_transit_to_hub'], true)
            && $order->linehaul_rider_id, 409,
            'Parcel is not assigned to a Truck Rider in an eligible linehaul stage.');
        $plan = $order->routePlan;
        abort_unless($plan && $plan->is_active && $order->route_step
            && $order->next_route_checkpoint_id && $order->destination_logistics_center_id
            && (int) $plan->from_logistics_center_id === (int) $order->logistics_center_id
            && (int) $plan->to_logistics_center_id === (int) $order->destination_logistics_center_id,
            409, 'No active SH route is awaiting a scan for this parcel.');
        abort_unless(in_array((int) $station->logistics_center_id, [
            (int) $plan->from_logistics_center_id,
            (int) $plan->to_logistics_center_id,
        ], true), 403, 'This station is outside the parcel route.');
        $stop = $plan->stops->firstWhere('position', $order->route_step);
        abort_unless($stop && $stop->checkpoint?->is_active
            && (int) $stop->checkpoint_id === (int) $order->next_route_checkpoint_id,
            409, 'The stored route checkpoint is inactive or inconsistent.');
        abort_unless((int) $station->checkpoint_id === (int) $stop->checkpoint_id, 409,
            'Wrong SH station. Next expected checkpoint: '.$stop->checkpoint->code.' — '.$stop->checkpoint->name.'.');
    }

    private function scanResponse(Order $order, ShCheckpointScanEvent $event, bool $duplicate): array
    {
        $plan = $event->route_plan_id ? \App\Models\Profiles\LogisticsRoutePlan::query()
            ->with('stops.checkpoint', 'toLogisticsCenter')->find($event->route_plan_id) : null;
        $next = $plan?->stops->firstWhere('position', $event->route_step + 1);

        return [
            'tracking_number' => $order->tracking_number,
            'order_number' => $order->number,
            'checkpoint' => [
                'code' => $event->checkpoint?->code,
                'name' => $event->checkpoint?->name,
            ],
            'scanned_at' => $event->created_at?->toIso8601String(),
            'duplicate' => $duplicate,
            'next' => $next ? [
                'type' => 'sh', 'code' => $next->checkpoint?->code,
                'name' => $next->checkpoint?->name,
            ] : [
                'type' => 'main_hub',
                'name' => $plan?->toLogisticsCenter?->business_name,
            ],
        ];
    }

    private function checkpointData(ShScannerStation $station): array
    {
        return ['code' => $station->checkpoint->code, 'name' => $station->checkpoint->name];
    }
}
