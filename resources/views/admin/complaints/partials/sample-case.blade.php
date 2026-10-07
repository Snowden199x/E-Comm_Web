{{--
    Preview-only sample case so the list and drawer can be seen with an empty database.
    Nothing is saved or queried: the models below exist only in memory for this request.
    Delete this include from index.blade.php (and this file) once real cases exist.
--}}
@php
    $buyer = (new \App\Models\User)->forceFill([
        'name' => 'Maria Santos', 'email' => 'maria.santos@example.com', 'role' => 'buyer',
    ]);
    $seller = (new \App\Models\User)->forceFill([
        'name' => 'Casa Verde Home Goods', 'email' => 'hello@casaverde.example.com', 'role' => 'seller',
    ]);
    $order = (new \App\Models\Ecommerce\Order)->forceFill(['id' => 1042, 'total_amount' => 2480.00]);

    $sampleComplaint = (new \App\Models\Complaints\Complaint)->forceFill([
        'id' => 1042,
        'type' => 'Item Not Received',
        'kind' => 'complaint',
        'status' => 'open',
        'decision' => null,
        'description' => "Tracking says my parcel was delivered on October 2, but nothing arrived at my address.\n"
            . "The rider's number is unreachable and nobody in my building received it. I've waited three days and would like a refund or a re-delivery.",
        'created_at' => now()->subDays(3)->setTime(9, 42),
    ]);
    $sampleComplaint->setRelation('complainant', $buyer);
    $sampleComplaint->setRelation('respondent', $seller);
    $sampleComplaint->setRelation('order', $order);
@endphp

<section id="cs-sample" x-show="sample" x-cloak
    x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="mb-5 overflow-hidden rounded-2xl border border-dashed border-[#cdbbd2] bg-white" aria-label="Sample case preview">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-dashed border-[#cdbbd2] bg-[#FBF8FB] px-5 py-3">
        <p class="flex items-center gap-2 text-sm text-[#2B1730]">
            <x-admin.icon name="info" class="h-4 w-4 text-[#5b2963]" />
            <span><span class="font-semibold">Sample case.</span> This is a preview only. Nothing here is saved. Click the row to open the case preview.</span>
        </p>
        <button type="button" @click="sample = false; if (drawerId === 'sample') drawerId = null"
            class="text-sm font-medium text-[#3b1735] underline underline-offset-2 hover:text-[#4d1f45]">Hide sample</button>
    </div>
    <div class="thin-scroll overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
            <caption class="sr-only">Sample complaint</caption>
            <tbody>
                @include('admin.complaints.partials.complaint-row', ['complaint' => $sampleComplaint, 'sample' => true, 'index' => 0])
            </tbody>
        </table>
    </div>
</section>

{{-- Outside the section so its transition never offsets the fixed drawer --}}
@include('admin.complaints.partials.preview-modal', ['complaint' => $sampleComplaint, 'sample' => true])