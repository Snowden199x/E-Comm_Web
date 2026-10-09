{{--
    Identification files for one applicant / user: every submitted ID is shown as its own thumbnail.

    Needs: $user, $details, $ext (callable).

    Change (7 Oct, UI pass 2): a Buyer who registered with two secondary IDs used to get one large
    thumbnail (the first ID) and the second ID only as a small filename chip underneath, so it looked
    like only one ID had been submitted. Each ID now has its own labelled, clickable thumbnail, side by
    side when there are two, and a clear notice if the form said "two IDs" but only one file is on record.

    Clicking a thumbnail opens the shared Admin document viewer popup (admin/partials/document-viewer.blade.php)
    through the "open-document" event. Nothing here opens a new tab or leaves the page. Files are served only
    by the private admin route; PDFs get a document tile because browsers cannot draw them in <img>.

    The parent decides the column layout: with two IDs it stacks this block full width under the personal
    rows ($hasSecondId); with one ID it keeps the right-hand column.
--}}
@php
    $secondPath = $user->role === 'buyer' ? ($details->valid_id_path_2 ?? null) : null;
    $multiple = $details->valid_id_path && $secondPath;

    $docs = [];
    if ($details->valid_id_path) {
        $firstExt = $ext($details->valid_id_path, 'jpg');
        $docs[] = [
            'label' => $multiple ? 'ID 1 · Valid ID' : 'Valid ID',
            'name' => 'Valid ID',
            'file' => 'valid_id.' . $firstExt,
            'kind' => $firstExt === 'pdf' ? 'pdf' : 'image',
            'url' => route('admin.verification-documents.show', [$user, 'valid-id']),
        ];
    }
    if ($secondPath) {
        $secondExt = $ext($secondPath, 'jpg');
        $docs[] = [
            'label' => 'ID 2 · Second ID',
            'name' => 'Second ID',
            'file' => 'second_id.' . $secondExt,
            'kind' => $secondExt === 'pdf' ? 'pdf' : 'image',
            'url' => route('admin.verification-documents.show', [$user, 'second-id']),
        ];
    }

    // The Buyer form stores "primary" (one ID) or "secondary" (two IDs). Flag a mismatch instead of hiding it.
    $declaredTwo = $user->role === 'buyer' && ($details->id_type ?? null) === 'secondary';
    $secondMissing = $declaredTwo && empty($secondPath);

    // Payload for the viewer; kept as data so quotes in names cannot break the markup.
    $payload = fn (array $doc) => [
        'url' => $doc['url'],
        'title' => $doc['name'],
        'subtitle' => $user->name,
        'filename' => $doc['file'],
        'kind' => $doc['kind'],
    ];
@endphp

<figure class="min-w-0">
    <figcaption class="mb-2 flex flex-wrap items-center justify-between gap-2 text-[13px] font-medium text-gray-500">
        <span>{{ $multiple ? 'Identification' : 'Valid ID' }}</span>
        @if ($declaredTwo)
            <span class="inline-flex items-center rounded-full bg-[#F1E7F3] px-2 py-0.5 text-[11px] font-medium text-[#5b2963]">
                Two secondary IDs
            </span>
        @endif
    </figcaption>

    @if ($docs)
        <div class="grid gap-4 {{ $multiple ? 'sm:grid-cols-2' : '' }}">
            @foreach ($docs as $doc)
                <div class="min-w-0" x-data="{ failed: false }">
                    @if ($multiple)
                        <p class="mb-1.5 text-xs font-semibold text-[#3b1735]">{{ $doc['label'] }}</p>
                    @endif

                    <button type="button" aria-haspopup="dialog" aria-label="View {{ strtolower($doc['name']) }} of {{ $user->name }}"
                        @click="$dispatch('open-document', @js($payload($doc)))"
                        class="group relative block w-full overflow-hidden rounded-xl border border-[#ece4ec] bg-[#FBF8FB] text-left
                               transition duration-200 hover:border-[#cdbbd2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">

                        @if ($doc['kind'] === 'pdf')
                            <span class="flex aspect-[1.6/1] w-full flex-col items-center justify-center gap-2 px-4 text-center">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#F1E7F3] text-[#5b2963] transition duration-300 ease-vendo group-hover:scale-105">
                                    <x-admin.icon name="file-text" class="h-6 w-6" stroke="1.6" />
                                </span>
                                <span class="text-[13px] font-medium text-[#2B1730]">PDF document</span>
                                <span class="text-xs text-gray-500">Click to view</span>
                            </span>
                        @else
                            <img src="{{ $doc['url'] }}" alt="{{ $doc['name'] }} submitted by {{ $user->name }}" loading="lazy"
                                x-show="!failed" x-on:error="failed = true"
                                class="aspect-[1.6/1] w-full object-contain transition duration-300 ease-vendo group-hover:scale-[1.03]">
                            <span x-show="failed" x-cloak class="flex aspect-[1.6/1] w-full flex-col items-center justify-center gap-2 px-4 text-center">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#FBF1EE] text-[#b4452a]">
                                    <x-admin.icon name="image" class="h-6 w-6" stroke="1.6" />
                                </span>
                                <span class="text-[13px] font-medium text-[#2B1730]">Preview couldn't load</span>
                                <span class="text-xs text-gray-500">Click to see why</span>
                            </span>
                        @endif

                        <span aria-hidden="true"
                            class="pointer-events-none absolute bottom-2 right-2 inline-flex items-center gap-1.5 rounded-full bg-[#2B1730]/85 px-2.5 py-1 text-[11px] font-medium text-white
                                   opacity-0 transition duration-200 group-hover:opacity-100 group-focus-visible:opacity-100">
                            <x-admin.icon name="eye" class="h-3 w-3" /> View
                        </span>
                    </button>

                    <p class="mt-2 flex items-center gap-1.5 text-xs text-gray-500">
                        <x-admin.icon name="file" class="h-3.5 w-3.5 flex-shrink-0" />
                        <span class="truncate">{{ $doc['file'] }}</span>
                    </p>
                </div>
            @endforeach
        </div>

        @if ($secondMissing)
            <p role="status" class="mt-3 flex items-start gap-2 rounded-xl border border-[#F3D9A6] bg-[#FDF3E2] px-3 py-2.5 text-[13px] text-[#8a5614]">
                <x-admin.icon name="alert-circle" class="mt-0.5 h-4 w-4 flex-shrink-0" />
                <span>This applicant chose two secondary IDs, but only one file is on record. Ask them to resubmit the missing ID.</span>
            </p>
        @endif
    @else
        <p class="rounded-xl border border-dashed border-[#ddd0e0] bg-[#FBF8FB] px-4 py-8 text-center text-[13px] text-gray-500">
            No ID uploaded.
        </p>
    @endif
</figure>