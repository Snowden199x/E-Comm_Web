<x-buyer.layout>
    <div class="max-w-3xl mx-auto p-4 sm:p-5 lg:p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">My Orders</h2>

        <div class="space-y-3">
            @forelse ($orders as $order)
                <a href="{{ route('buyer.orders.show', $order) }}"
                    class="block bg-white rounded-2xl p-4 shadow-sm hover:shadow-md transition">
                    <div class="flex justify-between">
                        <span class="text-sm font-medium">Order #{{ $order->id }}</span>
                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 capitalize">{{ str_replace('_', ' ', $order->status) }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $order->items->count() }} item(s) — ₱{{ number_format($order->total_amount, 2) }}</p>
                </a>
            @empty
                <p class="text-center text-gray-400">No orders yet.</p>
            @endforelse
        </div>
    </div>
@include('shared.live-revision', ['endpoint' => route('buyer.live', 'orders'), 'mode' => 'reload'])
</x-buyer.layout>
