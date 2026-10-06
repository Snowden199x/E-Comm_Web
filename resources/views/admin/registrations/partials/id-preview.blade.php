{{--
    Valid ID thumbnail + file chips.
    Needs: $user, $details, $ext (callable).

    Clicking the thumbnail or a file chip opens the shared Admin document viewer popup
    (admin/partials/document-viewer.blade.php) through the "open-document" event.
    Nothing here opens a new tab or leaves the page. Files are served only by the
    private admin route; PDFs get a document tile because browsers cannot draw them in <img>.
--}}
@php
    $idUrl = route('admin.verification-documents.show', [$user, 'valid-id']);
    $idExt = $ext($details->valid_id_path, 'jpg');
    $idIsPdf = $idExt === 'pdf';

    $docs = [];
    if ($details->valid_id_path) {
        $docs[] = [
            'label' => 'Valid ID',
            'file' => 'valid_id.' . $idExt,
            'kind' => $idIsPdf ? 'pdf' : 'image',
            'url' => $idUrl,
        ];
    }
    if ($user->role === 'buyer' && ! empty($details->valid_id_path_2)) {
        $secondExt = $ext($details->valid_id_path_2, 'jpg');
        $docs[] = [
            'label' => 'Second ID',
            'file' => 'second_id.' . $secondExt,
            'kind' => $secondExt === 'pdf' ? 'pdf' : 'image',
            'url' => route('admin.verification-documents.show', [$user, 'second-id']),
        ];
    }

    // Payload for the viewer; kept as data so quotes in names cannot break the markup.
    $payload = fn (array $doc) => [
        'url' => $doc['url'],
        'title' => $doc['label'],
        'subtitle' => $user->name,
        'filename' => $doc['file'],
        'kind' => $doc['kind'],
    ];
@endphp

<figure class="min-w-0">
    <figcaption class="mb-2 text-[13px] font-medium text-gray-500">Valid ID</figcaption>

    @if ($docs)
        <div x-data="{ failed: false }">
            <button type="button" aria-haspopup="dialog" aria-label="View valid ID of {{ $user->name }}"
                @click="$dispatch('open-document', @js($payload($docs[0])))"
                class="group relative block w-full overflow-hidden rounded-xl border border-[#ece4ec] bg-[#FBF8FB] text-left
                       transition duration-200 hover:border-[#cdbbd2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">

                @if ($idIsPdf)
                    <span class="flex aspect-[1.6/1] w-full flex-col items-center justify-center gap-2 px-4 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#F1E7F3] text-[#5b2963] transition duration-300 ease-vendo group-hover:scale-105">
                            <x-admin.icon name="file-text" class="h-6 w-6" stroke="1.6" />
                        </span>
                        <span class="text-[13px] font-medium text-[#2B1730]">PDF document</span>
                        <span class="text-xs text-gray-500">Click to view</span>
                    </span>
                @else
                    <img src="{{ $idUrl }}" alt="Valid ID submitted by {{ $user->name }}" loading="lazy"
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
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            @foreach ($docs as $doc)
                <button type="button" aria-haspopup="dialog" @click="$dispatch('open-document', @js($payload($doc)))"
                    class="inline-flex max-w-full items-center gap-2 rounded-lg border border-[#d9ccdc] px-3 py-1.5 text-[13px] text-[#3b1735]
                           transition duration-200 hover:bg-[#F7F1F7] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="file" class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ $doc['file'] }}</span>
                </button>
            @endforeach
        </div>
    @else
        <p class="rounded-xl border border-dashed border-[#ddd0e0] bg-[#FBF8FB] px-4 py-8 text-center text-[13px] text-gray-500">
            No ID uploaded.
        </p>
    @endif
</figure>