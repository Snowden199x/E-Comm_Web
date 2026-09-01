<div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3 font-medium">Seller</th>
                <th class="pb-3 font-medium">Email</th>
                <th class="pb-3 font-medium">Reason</th>
                <th class="pb-3 font-medium">Time Remaining</th>
                <th class="pb-3 font-medium">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sellers as $seller)
                <tr class="border-b last:border-0">
                    <td class="py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                            {{ strtoupper(substr($seller->name, 0, 1)) }}
                        </div>
                        <span class="text-gray-900">{{ $seller->name }}</span>
                    </td>
                    <td class="py-3 text-gray-600">{{ $seller->email }}</td>
                    <td class="py-3 text-gray-600">{{ $seller->suspension_reason }}</td>
                    <td class="py-3 text-red-600 font-medium"
                        x-data="{
                            endTime: new Date('{{ $seller->suspended_until?->toIso8601String() }}').getTime(),
                            display: '',
                            tick() {
                                const diff = this.endTime - Date.now();
                                if (diff <= 0) { this.display = '0d 0h 0m 0s'; return; }
                                const d = Math.floor(diff / 86400000);
                                const h = Math.floor((diff % 86400000) / 3600000);
                                const m = Math.floor((diff % 3600000) / 60000);
                                const s = Math.floor((diff % 60000) / 1000);
                                this.display = `${d}d ${h}h ${m}m ${s}s`;
                            }
                        }"
                        x-init="tick(); setInterval(() => tick(), 1000)">
                        <span x-text="display"></span>
                    </td>
                    <td class="py-3">
                        <form method="POST" action="{{ route('user-management.activate', $seller) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg border border-green-300 text-green-700 text-xs font-medium hover:bg-green-50">Activate</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-8 text-center text-gray-400">No suspended sellers.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($sellers->hasPages())
        <div class="flex items-center justify-between mt-4 pt-4 border-t">
            <p class="text-xs text-gray-500">Showing {{ $sellers->count() }} out of {{ $sellers->total() }} entries</p>
            <div>{{ $sellers->links() }}</div>
        </div>
    @endif
</div>