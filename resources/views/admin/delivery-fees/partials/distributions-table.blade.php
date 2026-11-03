{{--
    Delivery fee distributions. Rendered inside #df-table and swapped when the page changes.
    Needs: $history (paginator). Page buttons carry data-page and are handled by the page scope (onTableClick).
--}}
<div class="overflow-hidden rounded-xl border border-vd-line bg-white">
    @if ($history->isEmpty())
        <x-admin.empty-state icon="truck" title="No delivery fees split" text="Delivered parcels placed with saved rates appear here." />
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <caption class="sr-only">How each delivery fee was split between the Rider, Vendo and the Logistics Company</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 font-medium">Order</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Delivery fee</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Rider</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Vendo</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Logistics</th>
                        <th scope="col" class="px-4 py-3 font-medium">Rates used</th>
                        <th scope="col" class="px-5 py-3 font-medium">Settled</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($history as $row)
                        <tr style="--i: {{ $loop->index }}" class="transition-colors duration-200 hover:bg-vd-tint">
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-vd-ink">
                                {{ $row->order_number }}
                                @if ($row->status === 'review')
                                    <span title="Route mismatch" class="ml-2 inline-flex items-center rounded-full border border-vd-warning-line bg-vd-warning-bg px-2 py-0.5 text-[11px] font-medium text-vd-warning-ink">Needs review</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-vd-ink">₱{{ number_format((float) $row->shipping_fee, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-vd-muted">₱{{ number_format((float) $row->rider_total, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-vd-ink">₱{{ number_format((float) $row->vendo_commission, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-vd-muted">₱{{ number_format((float) $row->logistics_remainder, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-vd-muted">
                                ₱{{ number_format((float) $row->rider_pay_rate, 2) }} Rider · {{ rtrim(rtrim(number_format((float) $row->vendo_delivery_commission_rate, 2), '0'), '.') }}% Vendo
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-vd-muted">{{ \Illuminate\Support\Carbon::parse($row->settled_at)->format('M j, Y, g:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $history])
    @endif
</div>
