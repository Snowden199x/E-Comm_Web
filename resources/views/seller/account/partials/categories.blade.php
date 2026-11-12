{{--
    Selling categories + "request new categories" (Admin approval and a supporting permit are required).

    Data contract supplied by Seller\AccountController:
      $seller              the signed-in seller (categories relation = approved categories)
      $categoryRequests    Collection of requests, newest first; each has: id, status (pending|approved|rejected),
                           categories (Collection<Category>), created_at, reviewed_at?, admin_note?
      $availableCategories optional; defaults below to top-level categories the seller does not have yet.
--}}
@php
    $categoryRequests = $categoryRequests ?? collect();
    $ownedIds = $seller->categories->pluck('id');
    $availableCategories = $availableCategories
        ?? \App\Models\Category::whereNull('parent_id')->whereNotIn('id', $ownedIds)->orderBy('name')->get();
    $pendingIds = $categoryRequests->where('status', 'pending')->flatMap(fn ($r) => $r->categories->pluck('id'))->unique();
    $endpoint = \Illuminate\Support\Facades\Route::has('seller.category-requests.store') ? route('seller.category-requests.store') : '';
    $statusText = ['pending' => 'Waiting for Admin', 'approved' => 'Approved', 'rejected' => 'Not approved'];
    $statusTone = ['pending' => 'butter', 'approved' => 'mint', 'rejected' => 'rose'];
@endphp
@vite('resources/js/seller/category-requests.js')

<section class="do-card ac-section cr-card" id="sellingCategories" data-cr-endpoint="{{ $endpoint }}">
    <header class="cr-head">
        <div class="do-card__head">
            <h2>Selling categories</h2>
            <p>You can list products only in categories Admin approved for your shop.</p>
        </div>
        <button type="button" class="do-btn do-btn--primary" data-cr-open @disabled($availableCategories->isEmpty())>
            <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Request categories
        </button>
    </header>
    @if($availableCategories->isEmpty())<p class="ac-hint">You already sell in every category.</p>@endif

    <ul class="cr-chips" aria-label="Approved categories">
        @forelse($seller->categories as $category)
            <li>{{ $category->name }}</li>
        @empty
            <li class="cr-chips__empty">No approved categories yet. Request one to start listing products.</li>
        @endforelse
    </ul>

    @if($categoryRequests->isNotEmpty())
        <h3 class="cr-sub">Your requests</h3>
        <ul class="cr-requests">
            @foreach($categoryRequests as $categoryRequest)
                <li class="cr-request">
                    <div>
                        <strong>{{ $categoryRequest->categories->pluck('name')->implode(', ') ?: 'Category request' }}</strong>
                        <small>Sent {{ $categoryRequest->created_at->format('M j, Y') }}@if($categoryRequest->reviewed_at) · Reviewed {{ $categoryRequest->reviewed_at->format('M j, Y') }}@endif</small>
                        @if($categoryRequest->status === 'rejected' && $categoryRequest->admin_note)<p class="cr-note"><b>Admin's note:</b> {{ $categoryRequest->admin_note }}</p>@endif
                    </div>
                    <span class="do-badge do-badge--{{ $statusTone[$categoryRequest->status] ?? 'lilac' }}">{{ $statusText[$categoryRequest->status] ?? ucfirst($categoryRequest->status) }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    <dialog class="cr-dialog" id="crDialog" aria-labelledby="crTitle">
        <form id="crForm" enctype="multipart/form-data" novalidate>
            <header class="cr-dialog__head">
                <div>
                    <h2 id="crTitle">Request new categories</h2>
                    <p>Admin reviews every request. Attach a permit or license that covers what you plan to sell.</p>
                </div>
                <button type="button" class="cr-x" data-cr-close aria-label="Close">×</button>
            </header>
            <div class="cr-dialog__body">
                <fieldset class="cr-fieldset">
                    <legend>Choose categories <span id="crCount">0 selected</span></legend>
                    <div class="cr-options">
                        @foreach($availableCategories as $category)
                            @php $isPending = $pendingIds->contains($category->id); @endphp
                            <label class="cr-option @if($isPending) is-disabled @endif">
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}" @disabled($isPending)>
                                <span>{{ $category->name }}@if($isPending)<small>Request pending</small>@endif</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="cr-field">Supporting permit
                    <input type="file" name="permit" id="crPermit" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                    <small>JPG, PNG, or PDF, up to 5 MB. Food and health products need a specific license.</small>
                </label>

                <label class="cr-field">Note to Admin <em>(optional)</em>
                    <textarea name="note" rows="3" maxlength="500" placeholder="Anything that helps Admin review your request"></textarea>
                </label>

                <p class="cr-error" id="crError" role="alert" hidden></p>
            </div>
            <footer class="cr-dialog__foot">
                <button type="button" class="do-btn do-btn--soft" data-cr-close>Cancel</button>
                <button type="submit" class="do-btn do-btn--primary" id="crSubmit">Submit request</button>
            </footer>
        </form>
    </dialog>
</section>
