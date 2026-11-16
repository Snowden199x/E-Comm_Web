@php
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
    $cardHead = 'rounded-t-lg bg-[#f6f2f7] px-4 py-3 text-[15px] font-semibold text-[#402143] sm:px-5';
@endphp
<x-buyer.layout title="Report details | Vendo">
    <div class="mx-auto w-full max-w-3xl space-y-4 px-4 py-6 sm:px-6">
        <div>
            <a href="{{ route('buyer.standing') }}" class="text-[13px] font-medium text-[#52245b] underline underline-offset-2">Back to account standing</a>
            <h1 class="mt-2 text-xl font-semibold text-[#2b1730] sm:text-2xl">Report details</h1>
        </div>

        <section class="{{ $card }}">
            <h2 class="{{ $cardHead }}">{{ $report->type }}</h2>
            <dl class="space-y-3 p-4 text-[13px] sm:p-5">
                <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Reported by</dt><dd class="text-[#2b1730]">{{ $reporterRole }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Reported on</dt><dd class="text-[#2b1730]">{{ $report->created_at->format('M j, Y') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Approved on</dt><dd class="text-[#2b1730]">{{ ($report->reviewed_at ?? $report->updated_at)->format('M j, Y') }}</dd></div>
                @if ($report->order)
                    <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Order</dt>
                        <dd><a href="{{ route('buyer.orders.show', $report->order) }}" class="font-medium text-[#52245b] underline underline-offset-2">{{ $report->order->number ?? '#'.$report->order->id }}</a></dd></div>
                @endif
                <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Effect</dt><dd class="text-[#a32b43]">1 strike</dd></div>
            </dl>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $cardHead }}">Admin's note</h2>
            <p class="whitespace-pre-line break-words p-4 text-[13px] leading-6 text-[#3d2a42] sm:p-5">{{ $note ?: 'Admin did not leave a note.' }}</p>
        </section>

        <p class="text-[12px] text-[#7a6a7e]">Think this is a mistake? <a href="{{ route('buyer.support.index') }}" class="font-medium text-[#52245b] underline underline-offset-2">Contact support</a> and mention this report.</p>
    </div>
</x-buyer.layout>
