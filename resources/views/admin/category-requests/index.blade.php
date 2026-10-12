<x-admin.layout title="Seller Category Requests">
    <div class="mx-auto w-full max-w-[1100px] p-4 sm:p-6">
        <header class="mb-6">
            <h1 class="font-display text-2xl font-semibold text-[#2B1730]">Seller category requests</h1>
            <p class="mt-1 text-sm text-gray-600">Review the seller's supporting permit before adding selling categories.</p>
        </header>

        @if(session('success'))<p class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800" role="status">{{ session('success') }}</p>@endif
        @if($errors->any())<p class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</p>@endif

        <nav class="mb-5 flex flex-wrap gap-2" aria-label="Request status">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.category-requests.index', ['status' => $key]) }}" @if($status === $key) aria-current="page" @endif
                   class="rounded-lg border px-3 py-2 text-sm {{ $status === $key ? 'border-[#3b1735] bg-[#3b1735] text-white' : 'border-gray-200 bg-white text-[#3b1735]' }}">
                    {{ $label }} ({{ $counts[$key] ?? 0 }})
                </a>
            @endforeach
        </nav>

        <div class="space-y-4">
            @forelse($categoryRequests as $categoryRequest)
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-[#2B1730]">{{ $categoryRequest->seller?->name ?? 'Deleted seller' }}</h2>
                            <p class="text-sm text-gray-600">{{ $categoryRequest->seller?->email }} · Submitted {{ $categoryRequest->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase text-gray-700">{{ $categoryRequest->status }}</span>
                    </div>
                    <p class="mt-3 text-sm"><strong>Requested:</strong> {{ $categoryRequest->categories->pluck('name')->implode(', ') }}</p>
                    @if($categoryRequest->note)<p class="mt-2 text-sm"><strong>Seller note:</strong> {{ $categoryRequest->note }}</p>@endif
                    <a class="mt-3 inline-block text-sm font-semibold text-[#694176] underline" href="{{ route('admin.category-requests.permit', $categoryRequest) }}" target="_blank" rel="noopener">View supporting permit</a>
                    @if($categoryRequest->status === 'pending')
                        <form method="POST" action="{{ route('admin.category-requests.decide', $categoryRequest) }}" class="mt-4 border-t border-gray-100 pt-4">
                            @csrf
                            <label class="block text-sm font-medium text-[#2B1730]">Review note <span class="font-normal text-gray-500">(required for rejection)</span>
                                <textarea name="admin_note" rows="2" maxlength="1000" class="mt-1 block w-full rounded-lg border border-gray-300 p-2 text-sm" placeholder="Explain any missing requirements">{{ old('admin_note') }}</textarea>
                            </label>
                            <div class="mt-3 flex gap-2">
                                <button type="submit" name="decision" value="approved" class="rounded-lg bg-[#3b1735] px-4 py-2 text-sm font-semibold text-white">Approve</button>
                                <button type="submit" name="decision" value="rejected" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700">Reject</button>
                            </div>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-gray-600">Reviewed {{ $categoryRequest->reviewed_at?->format('M j, Y g:i A') }} by {{ $categoryRequest->reviewer?->name ?? 'Admin' }}@if($categoryRequest->admin_note) · {{ $categoryRequest->admin_note }}@endif</p>
                    @endif
                </article>
            @empty
                <p class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600">No {{ $status }} category requests.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $categoryRequests->links() }}</div>
    </div>
</x-admin.layout>
