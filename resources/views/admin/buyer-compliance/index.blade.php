@php
    $cards = [
        ['Reported buyers', $stats['reported'], 'text-vd-ink'],
        ['Reports to review', $stats['pending'], 'text-[#9a6a12]'],
        ['Buyers with strikes', $stats['flagged'], 'text-[#a32b43]'],
        ['Suspended buyers', $stats['suspended'], 'text-[#52245b]'],
    ];
    $steps = collect($ladder)->map(fn ($days, $strikes) => $strikes.' approved reports: '.($days ? $days.'-day suspension' : 'permanent suspension'))->values();
    $tabs = ['all' => 'All', 'flagged' => 'With strikes', 'suspended' => 'Suspended'];
@endphp
<x-admin.layout title="Buyer Compliance">
    <div class="mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7">
        <div class="mb-5">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Buyer Compliance</h1>
            <p class="mt-1 text-sm text-vd-muted">Track reported buyers. Each approved report is one strike.</p>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($cards as [$label, $value, $tone])
                <div class="rounded-xl border border-vd-line bg-white px-4 py-3">
                    <p class="text-[13px] text-vd-muted">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold {{ $tone }}">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </div>

        <div class="mb-4 rounded-xl border border-vd-line bg-vd-tint px-4 py-3 text-[13px] text-vd-muted">
            <span class="font-medium text-vd-ink">Suspension steps:</span> {{ $steps->implode(' · ') }}. Timed suspensions lift on their own.
            <a href="{{ route('admin.complaints.index', ['kind' => 'user_report', 'status' => 'open']) }}" class="ml-1 font-medium text-[#52245b] underline underline-offset-2">Review open reports</a>
        </div>

        <form method="GET" action="{{ route('admin.buyer-compliance.index') }}" class="mb-4 flex flex-wrap items-center gap-2">
            <input type="hidden" name="standing" value="{{ $standing }}">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Search buyer name or email"
                class="h-10 w-full rounded-lg border border-vd-line bg-white px-3 text-sm sm:w-72">
            <button type="submit" class="h-10 rounded-lg border border-vd-line bg-white px-4 text-sm font-medium text-vd-ink">Search buyers</button>
            <div class="ml-auto flex gap-1" role="tablist">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.buyer-compliance.index', array_filter(['standing' => $key, 'search' => $filters['search'] ?? null])) }}"
                        class="rounded-lg border px-3 py-2 text-[13px] font-medium {{ $standing === $key ? 'border-[#52245b] bg-[#f6eef7] text-[#52245b]' : 'border-vd-line bg-white text-vd-muted' }}">{{ $label }}</a>
                @endforeach
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-vd-line bg-white">
            @if ($buyers->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-vd-ink">{{ filled($filters['search'] ?? null) || $standing !== 'all' ? 'No buyers found' : 'No reported buyers yet' }}</p>
                    <p class="mt-1 text-[13px] text-vd-muted">{{ filled($filters['search'] ?? null) || $standing !== 'all' ? 'Check the spelling or clear the filters.' : 'Buyers that sellers report appear here.' }}</p>
                </div>
            @else
                <div class="thin-scroll overflow-x-auto">
                    <table class="w-full min-w-[820px] text-sm">
                        <caption class="sr-only">Reported buyers and their strikes</caption>
                        <thead>
                            <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                                <th scope="col" class="px-5 py-3 text-left font-medium">Buyer</th>
                                <th scope="col" class="px-4 py-3 text-center font-medium">Strikes</th>
                                <th scope="col" class="px-4 py-3 text-center font-medium">To review</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">Standing</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-vd-line">
                            @foreach ($buyers as $buyer)
                                @php
                                    $suspended = $buyer->account_status === 'suspended';
                                    $left = \App\Services\BuyerComplianceService::strikesUntilNextStep((int) $buyer->approved_reports);
                                @endphp
                                <tr>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-vd-ink">{{ $buyer->name }}</p>
                                        <p class="text-[12px] text-vd-muted">{{ $buyer->email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold {{ $buyer->approved_reports > 0 ? 'text-[#a32b43]' : 'text-vd-muted' }}">{{ $buyer->approved_reports }}</td>
                                    <td class="px-4 py-3 text-center {{ $buyer->pending_reports > 0 ? 'font-semibold text-[#9a6a12]' : 'text-vd-muted' }}">{{ $buyer->pending_reports }}</td>
                                    <td class="px-4 py-3">
                                        @if ($suspended)
                                            <span class="inline-flex rounded-full border border-[#e9c7ce] bg-[#fdf1f3] px-2.5 py-1 text-[12px] font-medium text-[#a32b43]">Suspended</span>
                                            <p class="mt-1 text-[12px] text-vd-muted">{{ $buyer->suspended_until ? 'Until '.$buyer->suspended_until->format('M j, Y') : 'No end date' }}</p>
                                        @elseif ($buyer->approved_reports > 0)
                                            <span class="inline-flex rounded-full border border-[#efdfb1] bg-[#fdf8e7] px-2.5 py-1 text-[12px] font-medium text-[#9a6a12]">Warned</span>
                                            @if ($left)<p class="mt-1 text-[12px] text-vd-muted">{{ $left }} more {{ \Illuminate\Support\Str::plural('strike', $left) }} until suspension</p>@endif
                                        @else
                                            <span class="inline-flex rounded-full border border-vd-line bg-white px-2.5 py-1 text-[12px] font-medium text-vd-muted">No strikes</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.complaints.index', ['kind' => 'user_report', 'search' => $buyer->email]) }}" class="rounded-lg border border-vd-line bg-white px-3 py-1.5 text-[13px] font-medium text-vd-ink">View reports</a>
                                            @if ($suspended)
                                                <form method="POST" action="{{ route('admin.user-management.activate', $buyer) }}" onsubmit="return confirm('Lift this suspension and let the buyer sign in again?')">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg border border-[#52245b] bg-[#52245b] px-3 py-1.5 text-[13px] font-medium text-white">Lift suspension</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($buyers->hasPages())<div class="border-t border-vd-line px-4 py-3">{{ $buyers->links() }}</div>@endif
            @endif
        </div>
    </div>
</x-admin.layout>
