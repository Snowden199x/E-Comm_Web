@php
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
    $field = 'mt-1 block w-full rounded-md border border-[#e5dce7] bg-white px-3 py-2 text-[13px] text-[#2b1730] transition-colors duration-200 focus:border-[#805487] focus:outline-none';
    $label = 'block text-[12px] font-medium text-[#402143]';
@endphp

<x-buyer.layout title="New support request — Vendo">
    <div class="mx-auto max-w-[720px] px-3 pb-10 pt-4 sm:px-4">
        <nav class="vb-reveal mb-3 text-[13px] text-[#7a6a7e]" aria-label="Breadcrumb">
            <a href="{{ route('buyer.support.index') }}" class="font-medium text-[#52245b] hover:underline">Support</a>
            <span class="px-1">/</span><span>New request</span>
        </nav>

        <div class="{{ $card }} vb-reveal p-5 sm:p-6">
            <h1 class="text-[20px] font-semibold text-[#2b1730]">New support request</h1>
            <p class="mt-1 text-[13px] leading-5 text-[#7a6a7e]">Tell Vendo what happened. Keep it to one problem so the replies stay clear.</p>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-[#f3d3da] bg-[#fdf1f3] px-4 py-3 text-[13px] text-[#a32b43]" role="alert">
                    @foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach
                </div>
            @endif

            <form action="{{ route('buyer.support.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="category" class="{{ $label }}">Topic</label>
                        <select id="category" name="category" required class="{{ $field }}">
                            @foreach (\App\Models\Support\SupportTicket::CATEGORIES as $key => $text)
                                <option value="{{ $key }}" @selected(old('category', 'order') === $key)>{{ $text }}</option>
                            @endforeach
                        </select>
                        @error('category')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="order_id" class="{{ $label }}">Order <span class="font-normal text-[#9a8a9d]">(optional)</span></label>
                        <select id="order_id" name="order_id" class="{{ $field }}">
                            <option value="">Not about one order</option>
                            @foreach ($orders as $order)
                                <option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>
                                    {{ $order->number }} — {{ $order->created_at?->format('M j, Y') }}
                                </option>
                            @endforeach
                        </select>
                        @error('order_id')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="subject" class="{{ $label }}">Subject</label>
                    <input id="subject" name="subject" value="{{ old('subject') }}" required maxlength="140" class="{{ $field }}" placeholder="e.g. Rider could not find my address">
                    @error('subject')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="message" class="{{ $label }}">What do you need help with?</label>
                    <textarea id="message" name="message" rows="6" required maxlength="5000" class="{{ $field }}" placeholder="Include the order number, the date and what you already tried.">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="attachments" class="{{ $label }}">Attach a photo or PDF <span class="font-normal text-[#9a8a9d]">(optional, up to 2 files, 5 MB each)</span></label>
                    <input id="attachments" type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf"
                        class="mt-1 block w-full rounded-md border border-[#e5dce7] bg-white px-3 py-2 text-[12px] text-[#3d2a42] focus:border-[#805487] focus:outline-none">
                    @foreach ($errors->get('attachments.*') as $messages)
                        @foreach ($messages as $errorText)<p class="mt-1 text-[12px] text-[#a32b43]">{{ $errorText }}</p>@endforeach
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-[0.97]">Send request</button>
                    <a href="{{ route('buyer.support.index') }}" class="inline-flex h-10 items-center rounded-md border border-[#e5dce7] px-5 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-buyer.layout>
