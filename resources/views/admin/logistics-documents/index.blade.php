<x-admin.layout title="Logistics documents">
    @vite('resources/css/admin/registrations.css')

    @php
        $tabs = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
        $tabIcons = ['pending' => 'clock', 'approved' => 'check-circle', 'rejected' => 'x-circle'];

        $statusPill = [
            'pending' => ['Pending', 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink'],
            'approved' => ['Approved', 'border-vd-success-line bg-vd-success-bg text-vd-success-ink'],
            'rejected' => ['Rejected', 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink'],
        ];

        $emptyCopy = [
            'pending' => ['check-circle', 'No pending documents', 'New replacement documents appear here.'],
            'approved' => ['file-check', 'No approved documents', 'Documents you approve appear here.'],
            'rejected' => ['file-check', 'No rejected documents', 'Documents you reject appear here.'],
        ];

        $documentLabels = ['valid_id' => 'Valid ID', 'business_permit' => 'Business permit'];
        $pendingCount = (int) ($counts['pending'] ?? 0);

        $quickNotes = [
            'Upload a clearer copy.',
            'Upload a document that is still valid.',
            'Upload the document for this business.',
        ];

        // After a failed decision the page comes back with old input: reopen that request's dialog.
        $reopenId = old('document_request_id') ? (int) old('document_request_id') : null;
        if ($reopenId && ! $requests->contains('id', $reopenId)) {
            $reopenId = null;
        }

        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $btnSm = "inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {$focus}";
        $decisionBtn = 'inline-flex h-9 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2';
        $chip = 'inline-flex items-center rounded-full border border-vd-line-strong bg-vd-tint px-2.5 py-0.5 text-xs font-medium text-vd-ink';
        $label = 'mb-1.5 text-xs font-medium text-vd-muted';
    @endphp

    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-5 lg:p-6" x-data="{ openId: @js($reopenId) }"
        @keydown.escape.window="openId = null"
        @ajax:after.window="document.getElementById('main-content')?.scrollTo({ top: 0 })">

        <div class="mb-4">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Logistics documents</h1>
            <p class="mt-1 text-sm text-vd-muted">Open a request to check the file. Approving makes it the company's current document.</p>
        </div>

        {{-- The note error shows under the note field inside the dialog, so it is left out here. --}}
        <x-admin.flash class="mb-4" :except="['review_note']" />

        {{-- Status cards with counts: same look as Registrations, User management and Category requests. --}}
        <nav aria-label="Document status" data-skeleton="cards" class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
            @foreach ($tabs as $key => $tabLabel)
                @php $active = $status === $key; @endphp
                <a href="{{ route('admin.logistics-documents.index', ['status' => $key]) }}" x-target.push="main-content" style="--i: {{ $loop->index }}"
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

        @if ($requests->isEmpty())
            <div class="overflow-hidden rounded-xl border border-vd-line bg-white">
                <x-admin.empty-state :icon="$emptyCopy[$status][0]" :title="$emptyCopy[$status][1]" :text="$emptyCopy[$status][2]">
                    @if ($status !== 'pending' && $pendingCount > 0)
                        <a href="{{ route('admin.logistics-documents.index', ['status' => 'pending']) }}" x-target.push="main-content" data-skeleton="cards" class="{{ $btnSm }}">
                            Review {{ number_format($pendingCount) }} pending {{ \Illuminate\Support\Str::plural('document', $pendingCount) }}
                        </a>
                    @endif
                </x-admin.empty-state>
            </div>
        @else
            {{-- List: one row per request. A row opens its dialog. --}}
            <div class="overflow-hidden rounded-xl border border-vd-line bg-white">
                <div class="thin-scroll overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-[13px]">
                        <caption class="sr-only">{{ $tabs[$status] }} logistics documents. Select a row to review the file.</caption>
                        <thead>
                            <tr class="border-b border-vd-line bg-vd-tint text-xs text-vd-muted">
                                <th scope="col" class="px-4 py-2.5 font-medium">Company</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Document</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Submitted</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Status</th>
                                <th scope="col" class="px-4 py-2.5 text-right font-medium"><span class="sr-only">Action</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-vd-line">
                            @foreach ($requests as $item)
                                @php
                                    $company = $item->center?->business_name ?? 'Deleted company';
                                    $isPending = $item->status === 'pending';
                                    [$pillLabel, $pillClass] = $statusPill[$item->status] ?? [ucfirst((string) $item->status), 'border-vd-line-strong bg-vd-tint text-vd-muted'];
                                    $waitDays = (int) $item->created_at->diffInDays(now());
                                    $documentLabel = $documentLabels[$item->document_type] ?? ucfirst(str_replace('_', ' ', $item->document_type));
                                @endphp
                                <tr tabindex="0" aria-label="Open {{ $documentLabel }} from {{ $company }}"
                                    @click="if (!$event.target.closest('a, button')) openId = {{ $item->id }}"
                                    @keydown.enter.self="openId = {{ $item->id }}"
                                    class="cursor-pointer transition-colors duration-200 hover:bg-vd-tint {{ $focus }} focus-visible:ring-inset">
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2.5">
                                            <x-admin.avatar :name="$company" size="h-8 w-8" text="text-xs" />
                                            <div class="min-w-0">
                                                <p class="max-w-[220px] truncate font-medium text-vd-ink">{{ $company }}</p>
                                                @if ($item->center?->user?->email)
                                                    <p class="max-w-[220px] truncate text-xs text-vd-muted">{{ $item->center->user->email }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5"><span class="{{ $chip }}">{{ $documentLabel }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-vd-muted">
                                        {{ $item->created_at->format('M j, Y') }}
                                        @if ($isPending)
                                            <span @class(['block text-xs', 'font-medium text-vd-warning-ink' => $waitDays >= 3])>Waiting {{ $item->created_at->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $pillClass }}">{{ $pillLabel }}</span>
                                        @unless ($isPending)
                                            <span class="block text-xs text-vd-muted">by {{ $item->reviewer?->name ?? 'Admin' }}</span>
                                        @endunless
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <button type="button" @click="openId = {{ $item->id }}" aria-label="{{ $isPending ? 'Review' : 'View' }} {{ $documentLabel }} from {{ $company }}"
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

                @if ($requests->hasPages())
                    @include('admin.partials.pagination', ['paginator' => $requests, 'asLinks' => true])
                @endif
            </div>

            {{-- One dialog per request. --}}
            @foreach ($requests as $item)
                @php
                    $company = $item->center?->business_name ?? 'Deleted company';
                    $isPending = $item->status === 'pending';
                    [$pillLabel, $pillClass] = $statusPill[$item->status] ?? [ucfirst((string) $item->status), 'border-vd-line-strong bg-vd-tint text-vd-muted'];
                    $waitDays = (int) $item->created_at->diffInDays(now());
                    $documentLabel = $documentLabels[$item->document_type] ?? ucfirst(str_replace('_', ' ', $item->document_type));

                    // Payload for the shared document popup. The file is served only by the guarded admin route.
                    $fileExt = strtolower(pathinfo((string) $item->path, PATHINFO_EXTENSION)) ?: 'jpg';
                    $file = [
                        'url' => route('admin.logistics-documents.file', $item),
                        'title' => $documentLabel,
                        'subtitle' => $company,
                        'filename' => str_replace(' ', '_', strtolower($documentLabel)) . '.' . $fileExt,
                        'kind' => $fileExt === 'pdf' ? 'pdf' : 'image',
                    ];

                    $wasSubmitted = $reopenId === $item->id;
                @endphp

                <div x-show="openId === {{ $item->id }}" x-cloak
                    x-effect="if (openId === {{ $item->id }}) $nextTick(() => $el.querySelector('[data-modal-close]')?.focus())"
                    @click.self="openId = null"
                    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    role="dialog" aria-modal="true" aria-labelledby="document-{{ $item->id }}-title"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-vd-ink/50 p-4">

                    <div @click.stop class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-vd-line bg-white">

                        <div class="flex items-start gap-3 border-b border-vd-line p-4">
                            <x-admin.avatar :name="$company" size="h-10 w-10" text="text-sm" />
                            <div class="min-w-0 flex-1">
                                <h2 id="document-{{ $item->id }}-title" class="font-display text-base font-semibold leading-snug text-vd-ink [overflow-wrap:anywhere]">{{ $company }}</h2>
                                @if ($item->center?->user?->email)
                                    <p class="text-[13px] text-vd-muted [overflow-wrap:anywhere]">{{ $item->center->user->email }}</p>
                                @endif
                            </div>
                            <span class="inline-flex flex-shrink-0 items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $pillClass }}">{{ $pillLabel }}</span>
                            <button type="button" data-modal-close @click="openId = null" aria-label="Close"
                                class="-mr-1 -mt-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum {{ $focus }}">
                                <x-admin.icon name="x" class="h-4 w-4" />
                            </button>
                        </div>

                        <form method="POST" action="{{ route('admin.logistics-documents.decide', $item) }}"
                            x-data="{
                                rejecting: @js($wasSubmitted),
                                error: @js($wasSubmitted ? (string) $errors->first('review_note') : ''),
                                startReject() {
                                    this.rejecting = true;
                                    this.$nextTick(() => this.$refs.note.focus());
                                },
                                cancelReject() {
                                    this.rejecting = false;
                                    this.error = '';
                                    this.$refs.note.value = '';
                                },
                                addNote(text) {
                                    const field = this.$refs.note;
                                    field.value = field.value.trim() ? field.value.trim() + ' ' + text : text;
                                    this.error = '';
                                    field.focus();
                                },
                            }"
                            x-effect="if (openId !== {{ $item->id }}) { rejecting = false; error = '' }"
                            class="flex min-h-0 flex-1 flex-col">
                            @csrf
                            <input type="hidden" name="document_request_id" value="{{ $item->id }}">

                            <div class="thin-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
                                <p class="text-[13px] text-vd-muted">
                                    Submitted {{ $item->created_at->format('M j, Y, g:i A') }}
                                    @if ($isPending)
                                        · <span @class(['font-medium', 'text-vd-warning-ink' => $waitDays >= 3])>waiting {{ $item->created_at->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</span>
                                    @endif
                                </p>

                                <div>
                                    <p class="{{ $label }}">Document</p>
                                    <span class="{{ $chip }}">{{ $documentLabel }}</span>
                                </div>

                                <button type="button" @click="$dispatch('open-document', @js($file))" class="{{ $btnSm }}">
                                    <x-admin.icon name="file-text" class="h-4 w-4" /> {{ $fileExt === 'pdf' ? 'View PDF file' : 'View file' }}
                                </button>

                                @unless ($isPending)
                                    <div class="space-y-2 rounded-xl border border-vd-line bg-vd-tint p-3 text-[13px]">
                                        <p class="text-vd-muted">
                                            {{ $pillLabel }} by <span class="font-medium text-vd-ink">{{ $item->reviewer?->name ?? 'Admin' }}</span>
                                            · {{ $item->reviewed_at?->format('M j, Y, g:i A') }}
                                        </p>
                                        @if ($item->review_note)
                                            <p class="rounded-lg border border-vd-line bg-white p-2.5 text-vd-ink [overflow-wrap:anywhere]">{{ $item->review_note }}</p>
                                        @endif
                                    </div>
                                @endunless

                                @if ($isPending)
                                    {{-- Reject asks for a note first. It opens here so approving stays one click. --}}
                                    <div x-show="rejecting" x-cloak>
                                        <label for="note-{{ $item->id }}" class="block text-sm font-medium text-vd-ink">Note to company</label>
                                        <textarea id="note-{{ $item->id }}" name="review_note" rows="3" maxlength="1000" x-ref="note" @input="error = ''"
                                            :aria-invalid="error ? 'true' : 'false'" aria-describedby="note-error-{{ $item->id }}"
                                            :class="error ? 'border-vd-danger-ink' : 'border-vd-line-strong'"
                                            placeholder="What the company must fix"
                                            class="mt-1.5 w-full resize-none rounded-xl border bg-white p-3 text-sm text-vd-ink placeholder:text-vd-muted transition-colors duration-200 focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20">{{ $wasSubmitted ? old('review_note') : '' }}</textarea>
                                        <p id="note-error-{{ $item->id }}" x-show="error" x-cloak x-text="error" role="alert" class="mt-1.5 text-xs font-medium text-vd-danger-ink"></p>

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
                                    <template x-if="!rejecting">
                                        <div class="flex gap-2">
                                            <button type="button" @click="startReject()" aria-label="Reject {{ $documentLabel }} from {{ $company }}"
                                                class="{{ $decisionBtn }} border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink hover:border-vd-danger-ink focus-visible:ring-vd-danger-ink/40">Reject</button>
                                            <button type="submit" name="decision" value="approved" aria-label="Approve {{ $documentLabel }} from {{ $company }}"
                                                class="{{ $decisionBtn }} border-vd-success-line bg-vd-success-bg text-vd-success-ink hover:border-vd-success-ink focus-visible:ring-vd-success-ink/40">Approve</button>
                                        </div>
                                    </template>
                                    <template x-if="rejecting">
                                        <div class="flex gap-2">
                                            <button type="button" @click="cancelReject()"
                                                class="{{ $decisionBtn }} border-vd-line-strong bg-white text-vd-ink hover:bg-vd-paper focus-visible:ring-vd-plum/40">Go back</button>
                                            <button type="submit" name="decision" value="rejected" aria-label="Send rejection to {{ $company }}"
                                                @click="if (!$refs.note.value.trim()) { $event.preventDefault(); error = 'Add a note so the company knows what to fix.'; $refs.note.focus(); }"
                                                class="{{ $decisionBtn }} border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink hover:border-vd-danger-ink focus-visible:ring-vd-danger-ink/40">Reject document</button>
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
</x-admin.layout>
