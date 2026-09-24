<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\User;
use App\Services\SellerOrderWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100', 'status' => ['nullable', Rule::in(array_keys(Order::SHIPMENT_GROUPS))],
            'courier' => ['nullable', 'regex:/^(unassigned|[1-9][0-9]*)$/'],
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'page' => 'nullable|integer|min:1',
        ]);
        $base = Order::where('seller_id', $request->user()->id)->where('status', '!=', 'placed');
        $couriers = User::whereIn('id', (clone $base)->whereNotNull('courier_id')->select('courier_id'))->orderBy('name')->get(['id', 'name']);
        if ($search = trim($filters['search'] ?? '')) {
            $id = preg_match('/^(?:#?VND?-)?0*(\d+)$/i', $search, $matches) ? (int) $matches[1] : null;
            $base->where(function ($query) use ($search, $id) {
                $query->whereHas('buyer', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                    ->orWhere('tracking_number', 'like', '%'.$search.'%')->orWhere('carrier_tracking_number', 'like', '%'.$search.'%');
                if ($id !== null) {
                    $query->orWhere('id', $id);
                }
            });
        }
        if (! empty($filters['date_from'])) {
            $base->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $base->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (($filters['courier'] ?? '') === 'unassigned') {
            $base->whereNull('courier_id');
        } elseif (! empty($filters['courier'])) {
            $base->where('courier_id', $filters['courier']);
        }
        $raw = (clone $base)->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');
        $counts = ['all' => (int) $raw->sum()];
        foreach (Order::SHIPMENT_GROUPS as $group => $statuses) {
            $counts[$group] = collect($statuses)->sum(fn ($s) => (int) ($raw[$s] ?? 0));
        }
        $query = (clone $base)->with(['buyer', 'courier', 'items.product.images'])->withSum('items', 'quantity');
        if (! empty($filters['status'])) {
            $query->whereIn('status', Order::SHIPMENT_GROUPS[$filters['status']]);
        }
        $recent = (clone $query)->latest('updated_at')->orderByDesc('id')->limit(4)->get();
        $shipments = $query->latest()->orderByDesc('id')->paginate(8)->withQueryString();

        return view('seller.shipments.index', compact('shipments', 'counts', 'filters', 'couriers', 'recent'));
    }

    public function show(Request $request, int $order)
    {
        $order = Order::where('seller_id', $request->user()->id)->where('status', '!=', 'placed')
            ->with(['buyer', 'courier', 'items.product.images', 'statusEvents'])->findOrFail($order);

        return view('seller.shipments.detail', compact('order'));
    }

    public function update(Request $request, int $order)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['ship', 'cancel'])],
            'expected_status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'reason' => 'required_if:action,cancel|nullable|string|max:500',
            'handoff_confirmed' => 'accepted_if:action,ship',
        ]);
        app(SellerOrderWorkflow::class)->transition($request->user(), $order, $data['action'] === 'ship' ? 'pickup' : 'cancel_shipment', $data['expected_status'], $data['reason'] ?? null);

        return response()->json(['message' => $data['action'] === 'ship' ? 'Pickup confirmed. The shipment is now in transit.' : 'Shipment cancelled. Reserved stock has been restored.']);
    }

    public function tracking(Request $request, int $order)
    {
        $data = $request->validate([
            'carrier_name' => 'nullable|string|max:100|required_with:carrier_tracking_number', 'carrier_tracking_number' => 'nullable|string|max:100|required_with:carrier_name',
            'estimated_delivery_from' => 'nullable|date_format:Y-m-d|required_with:estimated_delivery_to',
            'estimated_delivery_to' => 'nullable|date_format:Y-m-d|required_with:estimated_delivery_from|after_or_equal:estimated_delivery_from',
            'expected_status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'expected_revision' => 'required|string',
        ]);
        DB::transaction(function () use ($request, $order, $data) {
            $record = Order::where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === $data['expected_status'] && $record->revision === $data['expected_revision'], 409, 'Shipment changed. Reopen its details.');
            abort_if(in_array($record->status, ['placed', 'delivered', 'completed', 'cancelled', 'returned']), 409, 'Tracking details cannot be changed for this shipment.');
            unset($data['expected_status'], $data['expected_revision']);
            $record->fill($data);
            if ($record->isDirty()) {
                $record->save();
                $record->statusEvents()->create(['user_id' => $request->user()->id, 'from_status' => $record->status, 'to_status' => $record->status, 'note' => 'Shipment tracking details updated.']);
            }
        });

        return response()->json(['message' => 'Tracking details saved.']);
    }
}
