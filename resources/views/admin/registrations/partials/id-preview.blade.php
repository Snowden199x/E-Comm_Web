{{--
    Valid ID thumbnail + file chip.
    Needs: $user, $details, $ext (callable). Expects the parent scope from applicant-details
    (lightbox, lbSrc). Documents are served by the private admin route only.
--}}
@php
    $idUrl = route('admin.verification-documents.show', [$user, 'valid-id']);
@endphp

<figure class="min-w-0">
    <figcaption class="mb-2 text-[13px] font-medium text-gray-500">Valid ID</figcaption>

    @if ($details->valid_id_path)
        <div x-data="{ failed: false }">
            <button type="button" x-show="!failed" @click="lbSrc = lbSrc || @js($idUrl); lightbox = true"
                aria-label="Enlarge valid ID"
                class="group relative block w-full overflow-hidden rounded-xl border border-[#ece4ec] bg-[#FBF8FB]
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                <img src="{{ $idUrl }}" alt="Valid ID submitted by {{ $user->name }}" loading="lazy"
                    x-on:error="failed = true"
                    class="aspect-[1.6/1] w-full object-contain transition duration-300 ease-vendo group-hover:scale-[1.03]">
                <span aria-hidden="true"
                    class="pointer-events-none absolute bottom-2 right-2 inline-flex items-center rounded-full bg-[#2B1730]/80 px-2.5 py-1 text-[11px] font-medium text-white
                           opacity-0 transition duration-200 group-hover:opacity-100 group-focus-visible:opacity-100">
                    Enlarge
                </span>
            </button>

            <p x-show="failed" x-cloak
                class="rounded-xl border border-dashed border-[#ddd0e0] bg-[#FBF8FB] px-4 py-8 text-center text-[13px] text-gray-500">
                Preview unavailable. Open the file to review it.
            </p>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <a href="{{ $idUrl }}" target="_blank" rel="noopener noreferrer"
                class="inline-flex max-w-full items-center gap-2 rounded-lg border border-[#d9ccdc] px-3 py-1.5 text-[13px] text-[#3b1735]
                       transition duration-200 hover:bg-[#F7F1F7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                <img src="{{ asset('assets/icons/registration/document-icon.svg') }}" alt="" class="h-4 w-4 flex-shrink-0">
                <span class="truncate">valid_id.{{ $ext($details->valid_id_path, 'jpg') }}</span>
            </a>

            @if ($user->role === 'buyer' && ! empty($details->valid_id_path_2))
                <a href="{{ route('admin.verification-documents.show', [$user, 'second-id']) }}" target="_blank" rel="noopener noreferrer"
                    class="inline-flex max-w-full items-center gap-2 rounded-lg border border-[#d9ccdc] px-3 py-1.5 text-[13px] text-[#3b1735]
                           transition duration-200 hover:bg-[#F7F1F7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <img src="{{ asset('assets/icons/registration/document-icon.svg') }}" alt="" class="h-4 w-4 flex-shrink-0">
                    <span class="truncate">second_id.{{ $ext($details->valid_id_path_2, 'jpg') }}</span>
                </a>
            @endif
        </div>
    @else
        <p class="rounded-xl border border-dashed border-[#ddd0e0] bg-[#FBF8FB] px-4 py-8 text-center text-[13px] text-gray-500">
            No ID uploaded.
        </p>
    @endif
</figure>