<x-admin.layout title="Logistics Documents">
    <div class="mx-auto w-full max-w-[1100px] p-4 sm:p-6">
        <div class="mb-6"><h1 class="text-2xl font-semibold text-[#2B1730]">Logistics Documents</h1><p class="mt-1 text-sm text-[#675A69]">Review private replacement documents before they become the account's current files.</p></div>
        @if (session('success'))<p class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</p>@endif
        @if ($errors->any())<p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</p>@endif
        <nav class="mb-4 flex gap-4 text-sm" aria-label="Document status">
            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.logistics-documents.index', ['status' => $key]) }}" class="border-b-2 pb-2 {{ $status === $key ? 'border-[#5A2D62] font-semibold text-[#2B1730]' : 'border-transparent text-[#675A69]' }}" @if ($status === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="overflow-x-auto rounded-lg border border-[#E8DFE9] bg-white">
            <table class="w-full min-w-[740px] text-left text-sm">
                <thead class="border-b border-[#E8DFE9] bg-[#FAF8FB] text-[#675A69]"><tr><th class="px-4 py-3">Company</th><th class="px-4 py-3">Document</th><th class="px-4 py-3">Submitted</th><th class="px-4 py-3">File</th><th class="px-4 py-3">Decision</th></tr></thead>
                <tbody class="divide-y divide-[#E8DFE9]">
                    @forelse ($requests as $item)
                        <tr>
                            <td class="px-4 py-3 align-top"><strong>{{ $item->center?->business_name }}</strong><div class="text-xs text-[#675A69]">{{ $item->center?->user?->email }}</div></td>
                            <td class="px-4 py-3 align-top">{{ ucfirst(str_replace('_', ' ', $item->document_type)) }}</td>
                            <td class="px-4 py-3 align-top">{{ $item->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-3 align-top"><a class="font-medium text-[#5A2D62] underline" href="{{ route('admin.logistics-documents.file', $item) }}" target="_blank" rel="noopener">Open private file</a></td>
                            <td class="px-4 py-3 align-top">
                                @if ($item->status === 'pending')
                                    <form method="POST" action="{{ route('admin.logistics-documents.decide', $item) }}" class="space-y-2">@csrf
                                        <label class="sr-only" for="reviewNote{{ $item->id }}">Review note</label>
                                        <input id="reviewNote{{ $item->id }}" name="review_note" maxlength="1000" placeholder="Reason if rejected" class="w-full rounded-lg border border-[#D8CDD9] px-3 py-2">
                                        <div class="flex gap-2"><button name="decision" value="approved" class="rounded-lg bg-[#5A2D62] px-3 py-2 text-white">Approve</button><button name="decision" value="rejected" class="rounded-lg border border-[#D8CDD9] px-3 py-2">Reject</button></div>
                                    </form>
                                @else
                                    <span>{{ ucfirst($item->status) }}</span>@if ($item->review_note)<div class="text-xs text-[#675A69]">{{ $item->review_note }}</div>@endif
                                @endif
                            </td>
                        </tr>
                    @empty<tr><td colspan="5" class="px-4 py-8 text-center text-[#675A69]">No {{ $status }} document requests.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
</x-admin.layout>
