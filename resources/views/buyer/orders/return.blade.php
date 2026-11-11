@php
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
    $cardHead = 'rounded-t-lg bg-[#f6f2f7] px-4 py-3 text-[15px] font-semibold text-[#402143] sm:px-5';
    $field = 'mt-1 block w-full rounded-md border border-[#e5dce7] bg-white px-3 py-2 text-[13px] text-[#2b1730] focus:border-[#805487] focus:ring-[#805487]';
    $label = 'block text-[13px] font-medium text-[#402143]';
    $fmt = fn ($amount) => '₱'.number_format((float) $amount, 2);
@endphp

<x-buyer.layout :title="'Return ' . $order->number . ' | Vendo'">
    <div class="vb-enter mx-auto max-w-[720px] px-3 pb-14 pt-4 sm:px-4">
        <a href="{{ route('buyer.orders.show', $order) }}" class="text-[13px] font-medium text-[#52245b] underline underline-offset-2">Back to order</a>

        <header class="mt-3">
            <h1 class="text-[22px] font-semibold leading-7 text-[#2b1730]">Return order {{ $order->number }}</h1>
            <p class="mt-1 text-[13px] text-[#5b4a60]">The seller reviews every return request. If they approve, the refund is
                <strong>{{ $fmt($order->payable_total) }}</strong> — the amount you paid on delivery.</p>
            @if ($windowClosesAt)
                <p class="mt-1 text-[12px] text-[#8a7a8e]">Return requests close on {{ $windowClosesAt->format('M j, Y') }} (7 days after delivery).</p>
            @endif
        </header>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-[#f3d3da] bg-[#fdf1f3] p-4 text-[13px] text-[#a32b43]" role="alert">
                @foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        @endif

        <form action="{{ route('buyer.orders.return.store', $order) }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <section class="{{ $card }}">
                <h2 class="{{ $cardHead }}">Why are you returning it?</h2>
                <div class="space-y-2 p-4 sm:p-5">
                    @foreach ($reasons as $key => $text)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-[#eee6ef] px-3 py-2.5 transition-colors duration-200 hover:border-[#d9c8dc] has-[:checked]:border-[#805487] has-[:checked]:bg-[#faf5fa]">
                            <input type="radio" name="reason" value="{{ $key }}" class="mt-0.5 text-[#52245b] focus:ring-[#805487]"
                                @checked(old('reason') === $key) required>
                            <span class="text-[13px] text-[#3d2a42]">{{ $text }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="{{ $card }} p-4 sm:p-5">
                <label for="details" class="{{ $label }}">Details <span class="font-normal text-[#8a7a8e]">(required for “Other”)</span></label>
                <textarea id="details" name="details" rows="4" maxlength="450" class="{{ $field }}"
                    placeholder="Tell the seller what happened.">{{ old('details') }}</textarea>
                <p class="mt-1 text-[12px] text-[#8a7a8e]">Up to 450 characters.</p>
            </section>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-[#52245b] px-4 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#402143]">
                    Send return request
                </button>
                <a href="{{ route('buyer.orders.show', $order) }}" class="inline-flex h-10 items-center justify-center rounded-md border border-[#e5dce7] px-4 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:bg-[#faf5fa]">
                    Keep the order
                </a>
            </div>
        </form>
    </div>
</x-buyer.layout>
