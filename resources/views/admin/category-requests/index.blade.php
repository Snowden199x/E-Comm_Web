<x-admin.layout title="Category requests">
    @vite('resources/css/admin/registrations.css')

    @php
        $tabs = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

        $tabIcons = ['pending' => 'clock', 'approved' => 'check-circle', 'rejected' => 'x-circle'];

        $statusPill = [
            'pending' => ['Pending', 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink'],
            'approved' => ['Approved', 'border-vd-success-line bg-vd-success-bg text-vd-success-ink'],
            'rejected' => ['Rejected', 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink'],
        ];

        // What an empty tab says. Pending empty is good news, so it gets the check icon.
        $emptyCopy = [
            'pending' => ['check-circle', 'No pending requests', 'New seller requests appear here.'],
            'approved' => ['layers', 'No approved requests', 'Requests you approve appear here.'],
            'rejected' => ['layers', 'No rejected requests', 'Requests you reject appear here.'],
        ];

        $pendingCount = (int) ($counts['pending'] ?? 0);

        // One-click reasons for a rejection. Each is a full instruction to the seller and stays editable.
        $quickNotes = [
            'Upload a clearer permit.',
            'Upload a permit that is still valid.',
            'Upload a permit that covers these categories.',
        ];

        // After a failed decision the page comes back with old input: reopen that request's dialog.
        $reopenId = old('category_request_id') ? (int) old('category_request_id') : null;
        if ($reopenId && ! $categoryRequests->contains('id', $reopenId)) {
            $reopenId = null;
        }

        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $btnSm = "inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {$focus}";
        $decisionBtn = 'inline-flex h-9 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2';
        $chip = 'inline-flex items-center rounded-full border border-vd-line-strong bg-vd-tint px-2.5 py-0.5 text-xs font-medium text-vd-ink';
        $label = 'mb-1.5 text-xs font-medium text-vd-muted';
    @endphp

    {{-- After a tab or page swap, start at the top of the list. --}}
    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-5 lg:p-6" x-data="{ openId: @js($reopenId) }"
        @keydown.escape.window="openId = null"
        @ajax:after.window="document.getElementById('main-content')?.scrollTo({ top: 0 })">

        <div class="mb-4">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Category requests</h1>
            <p class="mt-1 text-sm text-vd-muted">Open a request to check the permit, then approve or reject.</p>
        </div>

        {{-- The note error shows under the note field inside the dialog, so it is left out here. --}}
        <x-admin.flash class="mb-4" :except="['admin_note']" />

        {{-- Status cards with counts: same look as the count cards on Registrations and User management. They are links, so each one opens its list. --}}
        <nav aria-label="Request status" data-skeleton="cards" class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
            @foreach ($tabs as $key => $tabLabel)
                @php $active = $status === $key; @endphp
                <a href="{{ route('admin.category-requests.index', ['status' => $key]) }}" x-target.push="main-content" style="--i: {{ $loop->index }}"
                    @if ($active) aria-current="page" @endif
                    @class([
                        'rg-card flex items-center gap-3 rounded-[18px] border px-4 py-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40',
                        'border-vd-plum bg-[#DDD0DB] ring-2 ring-vd-orchid/30' => $active,
                        'border-vd-orchid bg-[#E6DEE5] hover:-translate-y-0.5 hover:border-vd-plum hover:bg-[#E0D5DF]' => ! $active,
                    ])>
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid text-white">
                        <x-admin.icon :name="$tabIcons[$key]" class="h-[18px] w-[18px]" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-display text-[13px] font-medium text-vd-ink">{{ $tabLabel }}</span>
                        <span class="block font-display text-lg font-semibold leading-tight tabular-nums text-vd-ink">{{ number_format($counts[$key] ?? 0) }}</span>
                    </span>
                </a>
            @endforeach
        </nav>

        @if ($categoryRequests->isEmpty())
            <div class="overflow-hidden rounded-xl border border-vd-line bg-white">
                <x-admin.empty-state :icon="$emptyCopy[$status][0]" :title="$emptyCopy[$status][1]" :text="$emptyCopy[$status][2]">
                    @if ($status !== 'pending' && $pendingCount > 0)
                        <a href="{{ route('admin.category-requests.index', ['status' => 'pending']) }}" x-target.push="main-content" data-skeleton="cards" class="{{ $btnSm }}">
                            Review {{ number_format($pendingCount) }} pending {{ \Illuminate\Support\Str::plural('request', $pendingCount) }}
                        </a>
                    @endif
                </x-admin.empty-state>
            </div>
        @else
            {{-- List: one row per request. A row opens its dialog. --}}
            <div class="overflow-hidden rounded-xl border border-vd-line bg-white">
                <div class="thin-scroll overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-[13px]">
                        <caption class="sr-only">{{ $tabs[$status] }} category requests. Select a row to review the request.</caption>
                        <thead>
                            <tr class="border-b border-vd-line bg-vd-tint text-xs text-vd-muted">
                                <th scope="col" class="px-4 py-2.5 font-medium">Seller</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Requested categories</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Submitted</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Status</th>
                                <th scope="col" class="px-4 py-2.5 text-right font-medium"><span class="sr-only">Action</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-vd-line">
                            @foreach ($categoryRequests as $categoryRequest)
                                @php
                                    $seller = $categoryRequest->seller;
                                    $sellerName = $seller?->name ?? 'Deleted seller';
                                    $isPending = $categoryRequest->status === 'pending';
                                    [$pillLabel, $pillClass] = $statusPill[$categoryRequest->status] ?? [ucfirst((string) $categoryRequest->status), 'border-vd-line-strong bg-vd-tint text-vd-muted'];
                                    $waitDays = (int) $categoryRequest->created_at->diffInDays(now());
                                    $requested = $categoryRequest->categories;
                                @endphp
                                <tr tabindex="0" aria-label="Open request from {{ $sellerName }}"
                                    @click="if (!$event.target.closest('a, button')) openId = {{ $categoryRequest->id }}"
                                    @keydown.enter.self="openId = {{ $categoryRequest->id }}"
                                    class="cursor-pointer transition-colors duration-200 hover:bg-vd-tint {{ $focus }} focus-visible:ring-inset">
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2.5">
                                            <x-admin.avatar :user="$seller" name="Deleted seller" size="h-8 w-8" text="text-xs" />
                                            <div class="min-w-0">
                                                <p class="max-w-[200px] truncate font-medium text-vd-ink">{{ $sellerName }}</p>
                                                @if ($seller?->email)
                                                    <p class="max-w-[200px] truncate text-xs text-vd-muted">{{ $seller->email }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($requested->take(2) as $category)
                                                <x-admin.category-chip :category="$category" />
                                            @endforeach
                                            @if ($requested->count() > 2)
                                                <span class="{{ $chip }} bg-white text-vd-muted" title="{{ $requested->skip(2)->pluck('name')->implode(', ') }}">+{{ $requested->count() - 2 }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-vd-muted">
                                        {{ $categoryRequest->created_at->format('M j, Y') }}
                                        @if ($isPending)
                                            <span @class(['block text-xs', 'font-medium text-vd-warning-ink' => $waitDays >= 3])>Waiting {{ $categoryRequest->created_at->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $pillClass }}">{{ $pillLabel }}</span>
                                        @unless ($isPending)
                                            <span class="block text-xs text-vd-muted">by {{ $categoryRequest->reviewer?->name ?? 'Admin' }}</span>
                                        @endunless
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <button type="button" @click="openId = {{ $categoryRequest->id }}" aria-label="{{ $isPending ? 'Review' : 'View' }} request from {{ $sellerName }}"
                                            @class([
                                                'inline-flex h-8 items-center rounded-lg px-3 text-[13px] font-medium transition-colors duration-200',
                                                $focus,
                                                'bg-vd-plum text-white hover:bg-vd-plum-hover' => $isPending,
                                                'border border-vd-line-strong bg-white text-vd-plum hover:bg-vd-tint' => ! $isPending,
                                            ])>{{ $isPending ? 'Review' : 'View details' }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($categoryRequests->hasPages())
                    @include('admin.partials.pagination', ['paginator' => $categoryRequests, 'asLinks' => true])
                @endif
            </div>

            {{-- One dialog per request. --}}
            @foreach ($categoryRequests as $categoryRequest)
                @php
                    $seller = $categoryRequest->seller;
                    $sellerName = $seller?->name ?? 'Deleted seller';
                    $isPending = $categoryRequest->status === 'pending';
                    [$pillLabel, $pillClass] = $statusPill[$categoryRequest->status] ?? [ucfirst((string) $categoryRequest->status), 'border-vd-line-strong bg-vd-tint text-vd-muted'];
                    $waitDays = (int) $categoryRequest->created_at->diffInDays(now());
                    $approvedCategories = $seller?->categories ?? collect();

                    // Payload for the shared document popup. The permit is served only by the guarded admin route.
                    $permitExt = strtolower(pathinfo((string) $categoryRequest->permit_path, PATHINFO_EXTENSION)) ?: 'jpg';
                    $permit = [
                        'url' => route('admin.category-requests.permit', $categoryRequest),
                        'title' => 'Supporting permit',
                        'subtitle' => $sellerName,
                        'filename' => 'permit.' . $permitExt,
                        'kind' => $permitExt === 'pdf' ? 'pdf' : 'image',
                    ];

                    // A failed submit comes back with old input for every request; only the one that was sent gets it.
                    $wasSubmitted = $reopenId === $categoryRequest->id;
                @endphp

                <div x-show="openId === {{ $categoryRequest->id }}" x-cloak
                    x-effect="if (openId === {{ $categoryRequest->id }}) $nextTick(() => $el.querySelector('[data-modal-close]')?.focus())"
                    @click.self="openId = null"
                    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    role="dialog" aria-modal="true" aria-labelledby="request-{{ $categoryRequest->id }}-title"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-vd-ink/50 p-4">

                    <div @click.stop class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-vd-line bg-white">

                        <div class="flex items-start gap-3 border-b border-vd-line p-4">
                            <x-admin.avatar :user="$seller" name="Deleted seller" size="h-10 w-10" text="text-sm" />
                            <div class="min-w-0 flex-1">
                                <h2 id="request-{{ $categoryRequest->id }}-title" class="font-display text-base font-semibold leading-snug text-vd-ink [overflow-wrap:anywhere]">{{ $sellerName }}</h2>
                                @if ($seller?->email)
                                    <p class="text-[13px] text-vd-muted [overflow-wrap:anywhere]">{{ $seller->email }}</p>
                                @endif
                            </div>
                            <span class="inline-flex flex-shrink-0 items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $pillClass }}">{{ $pillLabel }}</span>
                            <button type="button" data-modal-close @click="openId = null" aria-label="Close"
                                class="-mr-1 -mt-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum {{ $focus }}">
                                <x-admin.icon name="x" class="h-4 w-4" />
                            </button>
                        </div>

                        <form method="POST" action="{{ route('admin.category-requests.decide', $categoryRequest) }}"
                            data-draft-key="admin-{{ auth('admin')->id() }}-category-request-{{ $categoryRequest->id }}"
                            x-data="{
                                rejecting: @js($wasSubmitted),
                                error: @js($wasSubmitted ? (string) $errors->first('admin_note') : ''),
                                startReject() {
                                    this.rejecting = true;
                                    this.$nextTick(() => this.$refs.note.focus());
                                },
                                cancelReject() {
                                    this.rejecting = false;
                                    this.error = '';
                                    this.$refs.note.value = '';
                                    this.$refs.note.dispatchEvent(new Event('input', { bubbles: true }));
                                },
                                addNote(text) {
                                    const field = this.$refs.note;
                                    field.value = field.value.trim() ? field.value.trim() + ' ' + text : text;
                                    this.error = '';
                                    field.dispatchEvent(new Event('input', { bubbles: true }));
                                    field.focus();
                                },
                            }"
                            x-effect="if (openId !== {{ $categoryRequest->id }}) { rejecting = false; error = '' }"
                            class="flex min-h-0 flex-1 flex-col">
                            @csrf
                            <input type="hidden" name="category_request_id" value="{{ $categoryRequest->id }}">

                            <div class="thin-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
                                <p class="text-[13px] text-vd-muted">
                                    Submitted {{ $categoryRequest->created_at->format('M j, Y, g:i A') }}
                                    @if ($isPending)
                                        · <span @class(['font-medium', 'text-vd-warning-ink' => $waitDays >= 3])>waiting {{ $categoryRequest->created_at->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</span>
                                    @endif
                                </p>

                                <div>
                                    <p class="{{ $label }}">Requested categories</p>
                                    <ul class="flex flex-wrap gap-1.5">
                                        @foreach ($categoryRequest->categories as $category)
                                            <li><x-admin.category-chip :category="$category" /></li>
                                        @endforeach
                                    </ul>
                                </div>

                                @if ($isPending)
                                    <div>
                                        <p class="{{ $label }}">Already sells in</p>
                                        @if ($approvedCategories->isEmpty())
                                            <p class="text-[13px] text-vd-muted">No categories yet</p>
                                        @else
                                            <ul class="flex flex-wrap gap-1.5">
                                                @foreach ($approvedCategories as $category)
                                                    <li><x-admin.category-chip :category="$category" /></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif

                                @if ($categoryRequest->note)
                                    <div>
                                        <p class="{{ $label }}">Seller note</p>
                                        <p class="rounded-xl border border-vd-line bg-vd-tint p-3 text-[13px] text-vd-ink [overflow-wrap:anywhere]">{{ $categoryRequest->note }}</p>
                                    </div>
                                @endif

                                <button type="button" @click="$dispatch('open-document', @js($permit))" class="{{ $btnSm }}">
                                    <x-admin.icon name="file-text" class="h-4 w-4" /> {{ $permitExt === 'pdf' ? 'View PDF permit' : 'View permit photo' }}
                                </button>

                                @unless ($isPending)
                                    <div class="space-y-2 rounded-xl border border-vd-line bg-vd-tint p-3 text-[13px]">
                                        <p class="text-vd-muted">
                                            {{ $pillLabel }} by <span class="font-medium text-vd-ink">{{ $categoryRequest->reviewer?->name ?? 'Admin' }}</span>
                                            · {{ $categoryRequest->reviewed_at?->format('M j, Y, g:i A') }}
                                        </p>
                                        @if ($categoryRequest->admin_note)
                                            <p class="rounded-lg border border-vd-line bg-white p-2.5 text-vd-ink [overflow-wrap:anywhere]">{{ $categoryRequest->admin_note }}</p>
                                        @endif
                                    </div>
                                @endunless

                                @if ($isPending)
                                    {{-- Reject asks for a note first. It opens here so approving stays one click. --}}
                                    <div x-show="rejecting" x-cloak>
                                        <label for="note-{{ $categoryRequest->id }}" class="block text-sm font-medium text-vd-ink">Note to seller</label>
                                        <textarea id="note-{{ $categoryRequest->id }}" name="admin_note" rows="3" maxlength="1000" x-ref="note" @input="error = ''"
                                            :aria-invalid="error ? 'true' : 'false'" aria-describedby="note-error-{{ $categoryRequest->id }}"
                                            :class="error ? 'border-vd-danger-ink' : 'border-vd-line-strong'"
                                            placeholder="What the seller must fix"
                                            class="mt-1.5 w-full resize-none rounded-xl border bg-white p-3 text-sm text-vd-ink placeholder:text-vd-muted transition-colors duration-200 focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20">{{ $wasSubmitted ? old('admin_note') : '' }}</textarea>
                                        <p id="note-error-{{ $categoryRequest->id }}" x-show="error" x-cloak x-text="error" role="alert" class="mt-1.5 text-xs font-medium text-vd-danger-ink"></p>

                                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                            <span class="text-xs font-medium text-vd-muted">Quick notes</span>
                                            @foreach ($quickNotes as $quickNote)
                                                <button type="button" @click="addNote(@js($quickNote))"
                                                    class="rounded-full border border-vd-line-strong bg-white px-2.5 py-1 text-xs font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender {{ $focus }}">{{ rtrim($quickNote, '.') }}</button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="flex justify-end gap-2 border-t border-vd-line bg-vd-tint p-3">
                                @if ($isPending)
                                    {{-- Step 1: choose. --}}
                                    <template x-if="!rejecting">
                                        <div class="flex gap-2">
                                            <button type="button" @click="startReject()" aria-label="Reject request from {{ $sellerName }}"
                                                class="{{ $decisionBtn }} border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink hover:border-vd-danger-ink focus-visible:ring-vd-danger-ink/40">Reject</button>
                                            <button type="submit" name="decision" value="approved" aria-label="Approve request from {{ $sellerName }}"
                                                class="{{ $decisionBtn }} border-vd-success-line bg-vd-success-bg text-vd-success-ink hover:border-vd-success-ink focus-visible:ring-vd-success-ink/40">Approve</button>
                                        </div>
                                    </template>
                                    {{-- Step 2: a rejection needs a note. An empty note never leaves the browser. --}}
                                    <template x-if="rejecting">
                                        <div class="flex gap-2">
                                            <button type="button" @click="cancelReject()"
                                                class="{{ $decisionBtn }} border-vd-line-strong bg-white text-vd-ink hover:bg-vd-paper focus-visible:ring-vd-plum/40">Go back</button>
                                            <button type="submit" name="decision" value="rejected" aria-label="Send rejection to {{ $sellerName }}"
                                                @click="if (!$refs.note.value.trim()) { $event.preventDefault(); error = 'Add a note so the seller knows what to fix.'; $refs.note.focus(); }"
                                                class="{{ $decisionBtn }} border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink hover:border-vd-danger-ink focus-visible:ring-vd-danger-ink/40">Reject request</button>
                                        </div>
                                    </template>
                                @else
                                    <button type="button" @click="openId = null" class="{{ $decisionBtn }} border-vd-line-strong bg-white text-vd-ink hover:bg-vd-paper focus-visible:ring-vd-plum/40">Close</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Another admin decided, or a seller sent a request: offer a refresh instead of reloading under the admin's hands. --}}
    @include('shared.live-revision', ['endpoint' => route('admin.live', 'category-requests'), 'mode' => 'banner'])
</x-admin.layout>
