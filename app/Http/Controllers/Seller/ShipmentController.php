<?php

namespace App\Http\Controllers\Seller;

use App\Support\SellerSearch;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsCompany;
use App\Services\SellerOrderWorkflow;
use Illuminate\Http\Request;
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
        ], [
            'search.max' => 'Search is too long. Use 100 characters or fewer.',
            'status.in' => 'Choose All or one of the status tabs.',
            'courier.regex' => 'That courier is not valid. Choose a courier from the list or clear the filter.',
            'date_from.date_format' => 'Start date is not valid. Pick a date from the calendar.',
            'date_to.date_format' => 'End date is not valid. Pick a date from the calendar.',
            'date_to.after_or_equal' => 'End date is before the start date. Pick an end date on or after the start date.',
            'page.min' => 'That page does not exist. Go back to the first page.',
        ]);
        $base = Order::where('seller_id', $request->user()->id)->where('status', '!=', 'placed');
        $couriers = LogisticsCompany::whereIn('id', (clone $base)->whereNotNull('delivery_company_id')->select('delivery_company_id'))->orderBy('business_name')->get(['id', 'business_name']);
        if ($search = SellerSearch::clean($filters['search'] ?? '')) {
            $base->where(function ($query) use ($search) {
                $query->whereHas('buyer', fn ($q) => $q->where('name', 'like', SellerSearch::like($search)))
                    ->orWhereHas('items.product', fn ($p) => $p->where('name', 'like', SellerSearch::like($search)));
                SellerSearch::orOrderColumns($query, $search);
            });
        }
        if (! empty($filters['date_from'])) {
            $base->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $base->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (($filters['courier'] ?? '') === 'unassigned') {
            $base->whereNull('delivery_company_id');
        } elseif (! empty($filters['courier'])) {
            $base->where('delivery_company_id', $filters['courier']);
        }
        $raw = (clone $base)->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');
        $counts = ['all' => (int) $raw->sum()];
        foreach (Order::SHIPMENT_GROUPS as $group => $statuses) {
            $counts[$group] = collect($statuses)->sum(fn ($s) => (int) ($raw[$s] ?? 0));
        }
        $query = (clone $base)->with(['buyer', 'courier', 'deliveryCompany', 'items.product.images'])->withSum('items', 'quantity');
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
            ->with(['buyer', 'courier', 'deliveryCompany', 'items.product.images', 'statusEvents'])->findOrFail($order);

        if (! $request->ajax()) {
            return redirect()->route('seller.orders.show', $order);
        }

        return view('seller.shipments.detail', compact('order'));
    }

    public function update(Request $request, int $order)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['cancel'])],
            'expected_status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'reason' => 'required_if:action,cancel|nullable|string|max:500',
            'reason_details' => 'required_if:reason,other|nullable|string|max:450',
        ]);
        app(SellerOrderWorkflow::class)->transition(
            $request->user(), $order, 'cancel_shipment', $data['expected_status'],
            $data['reason'] ?? null, $data['reason_details'] ?? null,
        );

        return response()->json(['message' => 'Shipment cancelled. Reserved stock has been restored.']);
    }
}
