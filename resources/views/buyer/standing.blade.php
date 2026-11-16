@php
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
    $cardHead = 'rounded-t-lg bg-[#f6f2f7] px-4 py-3 text-[15px] font-semibold text-[#402143] sm:px-5';
@endphp
<x-buyer.layout title="Account standing | Vendo">
    <div class="mx-auto w-full max-w-3xl space-y-4 px-4 py-6 sm:px-6">
        <div>
            <h1 class="text-xl font-semibold text-[#2b1730] sm:text-2xl">Account standing</h1>
            <p class="mt-1 text-[13px] text-[#7a6a7e]">When Admin approves a report about you, it counts as one strike.</p>
        </div>

        @if ($suspended)
            <section class="rounded-lg border border-[#e9c7ce] bg-[#fdf1f3] p-4 text-[13px] text-[#a32b43] sm:p-5" role="alert">
                <p class="font-semibold">Your account is suspended</p>
                <p class="mt-1">{{ $buyer->suspended_until ? 'It ends on '.$buyer->suspended_until->format('M j, Y g:i A').'.' : 'It has no end date.' }} {{ $buyer->suspension_notes }}</p>
                <p class="mt-1">If you think this is a mistake, <a href="{{ route('buyer.support.index') }}" class="font-medium underline underline-offset-2">contact support</a>.</p>
            </section>
        @endif

        <section class="{{ $card }}">
            <h2 class="{{ $cardHead }}">Your strikes</h2>
            <div class="p-4 sm:p-5">
                <p class="text-[28px] font-semibold leading-none {{ $strikes > 0 ? 'text-[#a32b43]' : 'text-[#2e6b46]' }}">{{ $strikes }}</p>
                <p class="mt-2 text-[13px] text-[#3d2a42]">
                    @if ($strikes === 0)
                        You have no strikes. Keep following Vendo policies.
                    @elseif ($untilNext)
                        {{ $untilNext }} more {{ \Illuminate\Support\Str::plural('strike', $untilNext) }} until your next suspension.
                    @else
                        You are at the highest step.
                    @endif
                </p>
                <ul class="mt-3 space-y-1 text-[12px] text-[#7a6a7e]">
                    @foreach ($ladder as $count => $days)
                        <li>{{ $count }} strikes: {{ $days ? $days.'-day suspension' : 'permanent suspension' }}</li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $cardHead }}">Approved reports</h2>
            @if ($reports->isEmpty())
                <p class="p-4 text-[13px] text-[#7a6a7e] sm:p-5">No approved reports. Nothing to fix.</p>
            @else
                <ul class="divide-y divide-[#f1e8f2]">
                    @foreach ($reports as $report)
                        <li>
                            <a href="{{ route('buyer.standing.report', $report->id) }}" class="flex items-center justify-between gap-3 px-4 py-3 text-[13px] hover:bg-[#faf5fa] sm:px-5">
                                <span class="font-medium text-[#2b1730]">{{ $report->type }}</span>
                                <span class="flex items-center gap-2 text-[#7a6a7e]">{{ ($report->reviewed_at ?? $report->created_at)->format('M j, Y') }} <span aria-hidden="true">&rsaquo;</span><span class="sr-only">View details</span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-buyer.layout>
