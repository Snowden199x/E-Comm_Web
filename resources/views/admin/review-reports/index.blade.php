<x-admin.layout>
    <div class="p-4 sm:p-5 lg:p-6">
        <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900">Product Review Reports</h1><p class="mt-1 text-sm text-gray-500">Review seller reports and record moderation decisions.</p></div>
        @if(session('success'))<p class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800" role="status">{{ session('success') }}</p>@endif
        <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach(['open' => 'Open reports', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'] as $status => $label)
                <a class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm" href="{{ route('admin.review-reports.index', ['status' => $status]) }}"><span class="text-sm text-gray-500">{{ $label }}</span><strong class="mt-1 block text-2xl text-gray-900">{{ number_format($counts[$status] ?? 0) }}</strong></a>
            @endforeach
        </div>
        <form method="GET" class="mb-4 flex flex-wrap items-center gap-3"><label class="text-sm text-gray-600">Report status <select name="status" class="ml-2 rounded-lg border-gray-300 text-sm"><option value="">All reports</option>@foreach(['open','resolved','dismissed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label><button class="rounded-lg bg-[#3b1735] px-4 py-2 text-sm font-medium text-white">Apply</button></form>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="w-full min-w-[850px] text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Review</th><th class="px-4 py-3">Product</th><th class="px-4 py-3">Seller</th><th class="px-4 py-3">Reason</th><th class="px-4 py-3">Reported</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th></tr></thead><tbody class="divide-y divide-gray-100">
                @forelse($reports as $report)<tr><td class="px-4 py-3"><a class="font-medium text-[#3b1735] hover:underline" href="{{ route('admin.review-reports.show', $report) }}">#{{ $report->review?->order_id }} · {{ $report->review?->rating }}/5</a><span class="block text-xs text-gray-500">{{ $report->review?->buyer?->name ?? 'Buyer' }}</span></td><td class="px-4 py-3">{{ $report->review?->product?->name ?? 'Unavailable product' }}</td><td class="px-4 py-3">{{ $report->seller?->name ?? 'Seller' }}</td><td class="max-w-xs px-4 py-3">{{ \Illuminate\Support\Str::limit($report->reason, 100) }}</td><td class="px-4 py-3">{{ $report->created_at->format('M j, Y') }}</td><td class="px-4 py-3"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs">{{ ucfirst($report->status) }}</span></td><td class="px-4 py-3"><a class="rounded-lg border border-[#6b4671] px-3 py-1.5 text-xs text-[#512258]" href="{{ route('admin.review-reports.show', $report) }}">Review</a></td></tr>
                @empty<tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">No review reports found.</td></tr>@endforelse
            </tbody></table>
        </div>
        <div class="mt-4">{{ $reports->links() }}</div>
    </div>
</x-admin.layout>
