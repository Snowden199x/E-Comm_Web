@php
    $isBuyer = auth()->user()->role === 'buyer';
    $role = $isBuyer ? 'buyer' : 'seller';
@endphp

<x-dynamic-component :component="$role . '.layout'" title="Case messages">
    <main class="mx-auto max-w-3xl px-4 py-6">
        <a href="{{ route($role . '.messages.index') }}" class="text-sm text-[#805487] hover:underline">← Back to messages</a>
        <h1 class="mt-4 text-xl font-semibold text-[#2B1730]">Case #{{ $complaint->id }} messages</h1>
        <p class="mt-1 text-sm text-[#6d5d70]">Private conversation with Vendo Support about this case.</p>

        @if (session('case_message_sent'))
            <p class="mt-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">Message sent.</p>
        @endif

        <div class="mt-5 divide-y divide-[#eee6ef] rounded-lg border border-[#eee6ef] bg-white">
            @foreach ($conversation->messages as $message)
                <div class="p-4">
                    <div class="flex items-baseline justify-between gap-3 text-xs text-[#7a6a7e]">
                        <strong class="text-[#402143]">{{ $message->sender_id === auth()->id() ? 'You' : 'Vendo Support' }}</strong>
                        <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, Y g:i A') }}</time>
                    </div>
                    <p class="mt-2 whitespace-pre-wrap break-words text-sm text-[#2B1730]">{{ $message->body }}</p>
                </div>
            @endforeach
        </div>

        @if ($complaint->status !== 'resolved' && $conversation->status === 'open')
            <form method="POST" action="{{ route($role . '.case-messages.store', $complaint) }}" class="mt-5 space-y-3">
                @csrf
                <label for="case-reply" class="block text-sm font-medium text-[#2B1730]">Reply</label>
                <textarea id="case-reply" name="body" rows="4" required maxlength="2000" class="w-full rounded-md border-[#d9cddd] focus:border-[#805487] focus:ring-[#805487]">{{ old('body') }}</textarea>
                @error('body')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                <button type="submit" class="rounded-md bg-[#402143] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#52245b]">Send reply</button>
            </form>
        @else
            <p class="mt-5 text-sm text-[#6d5d70]">This case conversation is closed.</p>
        @endif
    </main>
</x-dynamic-component>
