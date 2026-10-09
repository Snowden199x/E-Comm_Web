{{--
    Seller Vouchers — create and manage discount vouchers for the shop.

    Data contract (future Seller\VoucherController, see docs/features/seller/backend-needs-2026-10-07.md section 10):
      $vouchers   LengthAwarePaginator<Voucher>  id, name, code, type (fixed|percent), value, max_discount, min_spend, usage_limit,
                  per_buyer_limit, used_count, starts_at, ends_at, scope (all|products|categories), status (active|paused),
                  products (id), categories (id), discount_given (sum), orders_count
      $counts     ['all','active','scheduled','expired','paused']
      $stats      ['redeemed' => int, 'discount_given' => float, 'orders' => int]
      $filters    ['status' => ?, 'search' => ?, 'product' => ?]
      $products   Collection of the seller's products for the picker: id, name, product_code, price
      $categories Collection of the seller's categories for the picker: id, name
    Routes used (each optional; the UI says "not available yet" while a route is missing):
      seller.vouchers.store · seller.vouchers.update · seller.vouchers.toggle · seller.vouchers.destroy
--}}
@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Route as R;

    $vouchers = $vouchers ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 8);
    $counts = ($counts ?? []) + ['all' => 0, 'active' => 0, 'scheduled' => 0, 'expired' => 0, 'paused' => 0];
    $stats = ($stats ?? []) + ['redeemed' => 0, 'discount_given' => 0, 'orders' => 0];
    $filters = ($filters ?? []) + ['status' => '', 'search' => '', 'product' => ''];
    $products = $products ?? collect();
    $categories = $categories ?? collect();
    $tabs = ['' => 'All', 'active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'paused' => 'Paused'];
    $activeTab = $filters['status'] ?? '';
    $money = fn ($n) => '₱'.number_format((float) $n, ((float) $n == floor((float) $n)) ? 0 : 2);
    $stateOf = function ($v) {
        $starts = $v->starts_at ? Carbon::parse($v->starts_at) : null;
        $ends = $v->ends_at ? Carbon::parse($v->ends_at) : null;
        return match (true) {
            ($v->status ?? 'active') === 'paused' => 'paused',
            $starts && $starts->isFuture() => 'scheduled',
            ($ends && $ends->isPast()) || ($v->usage_limit && $v->used_count >= $v->usage_limit) => 'expired',
            default => 'active',
        };
    };
    $stateLabel = ['active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'paused' => 'Paused'];
    $urlFor = fn (string $name, $v = null) => R::has($name) ? ($v ? route($name, $v) : route($name)) : '';
    $filterProduct = $filters['product'] ? $products->firstWhere('id', (int) $filters['product']) : null;
    $tabUrl = fn ($key) => route('seller.vouchers.index', array_filter(array_merge(request()->except(['page', 'status']), ['status' => $key]), fn ($x) => $x !== null && $x !== ''));
@endphp
<x-seller.layout title="Vouchers">
    @vite(['resources/css/seller/vouchers.css', 'resources/js/seller/vouchers.js'])

    <section class="vc-page" id="vcApp"
             data-store-url="{{ $urlFor('seller.vouchers.store') }}"
             data-open-create="{{ request('create') ? '1' : '' }}"
             data-prefill-product="{{ request('product') }}">
        <header class="vc-head">
            <div>
                <h1>Vouchers</h1>
                <p>Give buyers a reason to check out. Create discount codes for your whole shop or for chosen products.</p>
            </div>
            <button type="button" class="vc-btn vc-btn--primary" data-vc-create>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Create voucher
            </button>
        </header>

        <div class="vc-stats" role="group" aria-label="Voucher summary">
            <div class="vc-stat"><span class="vc-stat__icon vc-i--plum"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9a2 2 0 002-2V6h14v1a2 2 0 002 2v6a2 2 0 00-2 2v1H5v-1a2 2 0 00-2-2z"/><path d="M12 7v10" stroke-dasharray="2 2"/></svg></span><span><small>Active vouchers</small><strong>{{ number_format($counts['active']) }}</strong></span></div>
            <div class="vc-stat"><span class="vc-stat__icon vc-i--green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span><small>Times redeemed</small><strong>{{ number_format($stats['redeemed']) }}</strong></span></div>
            <div class="vc-stat"><span class="vc-stat__icon vc-i--amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7"/><circle cx="8" cy="8" r="2"/><circle cx="16" cy="16" r="2"/></svg></span><span><small>Discount given</small><strong>{{ $money($stats['discount_given']) }}</strong></span></div>
            <div class="vc-stat"><span class="vc-stat__icon vc-i--blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg></span><span><small>Orders with a voucher</small><strong>{{ number_format($stats['orders']) }}</strong></span></div>
        </div>

        <div class="vc-bar">
            <nav class="vc-tabs" aria-label="Voucher status">
                @foreach($tabs as $key => $label)
                    <a class="vc-tab @if($activeTab === $key) is-active @endif" href="{{ $tabUrl($key) }}" @if($activeTab === $key) aria-current="page" @endif>{{ $label }}<span>{{ number_format($counts[$key === '' ? 'all' : $key]) }}</span></a>
                @endforeach
            </nav>
            <form method="GET" action="{{ route('seller.vouchers.index') }}" class="vc-search">
                @if($activeTab !== '')<input type="hidden" name="status" value="{{ $activeTab }}">@endif
                @if($filters['product'])<input type="hidden" name="product" value="{{ $filters['product'] }}">@endif
                <label><span class="vc-sr">Search vouchers</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="search" value="{{ $filters['search'] }}" maxlength="60" placeholder="Search by name or code">
                </label>
            </form>
        </div>

        @if($filters['product'])
            <p class="vc-filter-note">Showing vouchers that apply to <strong>{{ $filterProduct?->name ?? 'this product' }}</strong>. <a href="{{ route('seller.vouchers.index', array_filter(['status' => $activeTab])) }}">Show all</a></p>
        @endif

        <ul class="vc-list">
            @forelse($vouchers as $v)
                @php
                    $state = $stateOf($v);
                    $isPercent = ($v->type ?? 'fixed') === 'percent';
                    $big = $isPercent ? rtrim(rtrim(number_format((float) $v->value, 2), '0'), '.').'%' : $money($v->value);
                    $starts = $v->starts_at ? Carbon::parse($v->starts_at) : null;
                    $ends = $v->ends_at ? Carbon::parse($v->ends_at) : null;
                    $used = (int) ($v->used_count ?? 0);
                    $limit = $v->usage_limit ? (int) $v->usage_limit : null;
                    $pct = $limit ? min(100, (int) round($used / $limit * 100)) : null;
                    $scope = $v->scope ?? 'all';
                    $scopeText = match ($scope) {
                        'products' => count($v->products ?? []).' selected '.\Illuminate\Support\Str::plural('product', count($v->products ?? [])),
                        'categories' => count($v->categories ?? []).' '.\Illuminate\Support\Str::plural('category', count($v->categories ?? [])),
                        default => 'All your products',
                    };
                    $payload = [
                        'id' => $v->id, 'name' => $v->name, 'code' => $v->code, 'type' => $v->type ?? 'fixed', 'value' => (float) $v->value,
                        'max_discount' => $v->max_discount, 'min_spend' => $v->min_spend, 'usage_limit' => $v->usage_limit,
                        'per_buyer_limit' => $v->per_buyer_limit ?? 1, 'starts_at' => $starts?->format('Y-m-d\TH:i'), 'ends_at' => $ends?->format('Y-m-d\TH:i'),
                        'scope' => $scope, 'status' => $v->status ?? 'active',
                        'product_ids' => collect($v->products ?? [])->map(fn ($p) => is_object($p) ? $p->id : $p)->values(),
                        'category_ids' => collect($v->categories ?? [])->map(fn ($c) => is_object($c) ? $c->id : $c)->values(),
                        'update_url' => $urlFor('seller.vouchers.update', $v),
                    ];
                @endphp
                <li class="vc-ticket vc-ticket--{{ $state }}" style="--i: {{ $loop->index }}">
                    <div class="vc-stub" aria-hidden="true"><strong>{{ $big }}</strong><span>{{ $isPercent ? 'OFF' : 'OFF' }}</span>@if($isPercent && $v->max_discount)<small>up to {{ $money($v->max_discount) }}</small>@endif</div>
                    <div class="vc-body">
                        <div class="vc-body__top">
                            <div>
                                <h2>{{ $v->name }}</h2>
                                <button type="button" class="vc-code" data-vc-copy="{{ $v->code }}" aria-label="Copy voucher code {{ $v->code }}"><code>{{ $v->code }}</code><span>Copy</span></button>
                            </div>
                            <span class="vc-pill vc-pill--{{ $state }}">{{ $stateLabel[$state] }}</span>
                        </div>
                        <ul class="vc-facts">
                            <li><span>Min. spend</span><b>{{ ($v->min_spend ?? 0) > 0 ? $money($v->min_spend) : 'None' }}</b></li>
                            <li><span>Valid</span><b>{{ $starts ? $starts->format('M j, Y') : 'Now' }} – {{ $ends ? $ends->format('M j, Y') : 'No end date' }}</b></li>
                            <li><span>Applies to</span><b>{{ $scopeText }}</b></li>
                            <li><span>Per buyer</span><b>{{ $v->per_buyer_limit ?? 1 }}×</b></li>
                        </ul>
                        <div class="vc-usage">
                            <div class="vc-usage__text"><span>{{ number_format($used) }} {{ $limit ? 'of '.number_format($limit) : '' }} used</span>@if(($v->discount_given ?? 0) > 0)<span>{{ $money($v->discount_given) }} discounted</span>@endif</div>
                            <div class="vc-meter" role="img" aria-label="{{ $limit ? $pct.'% of the usage limit used' : 'No usage limit' }}"><i style="width: {{ $pct ?? 0 }}%"></i></div>
                            @if(! $limit)<small>No usage limit</small>@endif
                        </div>
                    </div>
                    <div class="vc-actions">
                        <button type="button" class="vc-btn vc-btn--small" data-vc-edit='@json($payload)'>Edit</button>
                        @if(in_array($state, ['active', 'paused', 'scheduled'], true))
                            <button type="button" class="vc-btn vc-btn--small" data-vc-toggle="{{ $urlFor('seller.vouchers.toggle', $v) }}" data-vc-name="{{ $v->name }}" data-next="{{ ($v->status ?? 'active') === 'paused' ? 'active' : 'paused' }}">{{ ($v->status ?? 'active') === 'paused' ? 'Resume' : 'Pause' }}</button>
                        @endif
                        <button type="button" class="vc-btn vc-btn--small vc-btn--danger" data-vc-delete="{{ $urlFor('seller.vouchers.destroy', $v) }}" data-vc-name="{{ $v->name }}" data-vc-used="{{ $used }}">Delete</button>
                    </div>
                </li>
            @empty
                <li class="vc-empty">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9a2 2 0 002-2V6h14v1a2 2 0 002 2v6a2 2 0 00-2 2v1H5v-1a2 2 0 00-2-2z"/><path d="M12 7v10" stroke-dasharray="2 2"/></svg>
                    @if($filters['search'] || $activeTab !== '' || $filters['product'])
                        <h3>No vouchers match</h3><p>Try another status or search, or clear the filters.</p>
                        <a class="vc-btn" href="{{ route('seller.vouchers.index') }}">Clear filters</a>
                    @else
                        <h3>Create your first voucher</h3>
                        <p>Vouchers lift sales: buyers see them in their cart and at checkout.</p>
                        <ul class="vc-tips">
                            <li><b>Pick a clear offer.</b> “₱50 off ₱500” beats a tiny percentage.</li>
                            <li><b>Set a minimum spend</b> so the discount grows your basket size.</li>
                            <li><b>Limit the total uses</b> to protect your margin.</li>
                        </ul>
                        <button type="button" class="vc-btn vc-btn--primary" data-vc-create>Create voucher</button>
                    @endif
                </li>
            @endforelse
        </ul>

        @if($vouchers->total() > 0)
            <footer class="vc-foot">
                <span>Showing {{ $vouchers->count() }} out of {{ number_format($vouchers->total()) }} vouchers</span>
                @if($vouchers->hasPages())
                    <nav class="vc-pager" aria-label="Vouchers pagination">
                        @if($vouchers->onFirstPage())<span aria-disabled="true">‹</span>@else<a href="{{ $vouchers->previousPageUrl() }}" aria-label="Previous page">‹</a>@endif
                        @for($page = max(1, $vouchers->currentPage() - 2); $page <= min($vouchers->lastPage(), $vouchers->currentPage() + 2); $page++)
                            <a href="{{ $vouchers->url($page) }}" @if($page === $vouchers->currentPage()) aria-current="page" class="is-active" @endif>{{ $page }}</a>
                        @endfor
                        @if($vouchers->hasMorePages())<a href="{{ $vouchers->nextPageUrl() }}" aria-label="Next page">›</a>@else<span aria-disabled="true">›</span>@endif
                    </nav>
                @endif
            </footer>
        @endif

        {{-- ============ CREATE / EDIT ============ --}}
        <dialog class="vc-modal" id="vcModal" aria-labelledby="vcModalTitle">
            <form id="vcForm" novalidate>
                <header class="vc-modal__head">
                    <div><h2 id="vcModalTitle">Create voucher</h2><p>Buyers enter this code at checkout to get the discount.</p></div>
                    <button type="button" class="vc-x" data-vc-close aria-label="Close">×</button>
                </header>
                <div class="vc-modal__body">
                    <div class="vc-form">
                        <div class="vc-form__main">
                            <fieldset class="vc-fs">
                                <legend>Basics</legend>
                                <label class="vc-field">Voucher name<input name="name" maxlength="60" placeholder="e.g. Payday Sale" required><small>Only you see this name.</small></label>
                                <label class="vc-field">Voucher code
                                    <span class="vc-inline"><input name="code" maxlength="12" placeholder="PAYDAY50" autocapitalize="characters" spellcheck="false" required><button type="button" class="vc-btn vc-btn--small" data-vc-generate>Generate</button></span>
                                    <small>4–12 letters or numbers. Buyers type this at checkout.</small>
                                </label>
                            </fieldset>

                            <fieldset class="vc-fs">
                                <legend>Discount</legend>
                                <div class="vc-seg" role="radiogroup" aria-label="Discount type">
                                    <label><input type="radio" name="type" value="fixed" checked><span>Fixed amount (₱)</span></label>
                                    <label><input type="radio" name="type" value="percent"><span>Percentage (%)</span></label>
                                </div>
                                <div class="vc-grid2">
                                    <label class="vc-field"><span data-vc-value-label>Discount amount (₱)</span><input name="value" type="number" inputmode="decimal" min="1" step="0.01" placeholder="50" required></label>
                                    <label class="vc-field" data-vc-maxwrap hidden>Max discount (₱)<input name="max_discount" type="number" inputmode="decimal" min="1" step="0.01" placeholder="Optional"><small>Caps the percentage discount.</small></label>
                                </div>
                                <label class="vc-field">Minimum spend (₱)<input name="min_spend" type="number" inputmode="decimal" min="0" step="0.01" placeholder="0 = no minimum"><small>Items subtotal needed to use the voucher.</small></label>
                            </fieldset>

                            <fieldset class="vc-fs">
                                <legend>Limits and schedule</legend>
                                <div class="vc-grid2">
                                    <label class="vc-field">Total uses<input name="usage_limit" type="number" inputmode="numeric" min="1" step="1" placeholder="Leave empty for unlimited"></label>
                                    <label class="vc-field">Uses per buyer<input name="per_buyer_limit" type="number" inputmode="numeric" min="1" step="1" value="1"></label>
                                    <label class="vc-field">Starts<input name="starts_at" type="datetime-local"></label>
                                    <label class="vc-field">Ends<input name="ends_at" type="datetime-local"></label>
                                </div>
                                <small class="vc-hint">Leave Starts empty to begin right away.</small>
                            </fieldset>

                            <fieldset class="vc-fs">
                                <legend>Applies to</legend>
                                <div class="vc-seg vc-seg--3" role="radiogroup" aria-label="Applies to">
                                    <label><input type="radio" name="scope" value="all" checked><span>All products</span></label>
                                    <label><input type="radio" name="scope" value="products"><span>Specific products</span></label>
                                    <label><input type="radio" name="scope" value="categories"><span>Categories</span></label>
                                </div>
                                <div class="vc-picker" data-vc-picker="products" hidden>
                                    <label class="vc-picker__search"><span class="vc-sr">Search your products</span><input type="search" placeholder="Search your products" data-vc-picker-search></label>
                                    <div class="vc-picker__list" role="group" aria-label="Products">
                                        @forelse($products as $product)
                                            <label class="vc-pick" data-text="{{ strtolower($product->name.' '.($product->product_code ?? '')) }}"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}"><span><b>{{ $product->name }}</b><small>{{ $product->product_code ?? '' }} · {{ $money($product->price ?? 0) }}</small></span></label>
                                        @empty
                                            <p class="vc-muted">No products to choose from yet.</p>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="vc-picker" data-vc-picker="categories" hidden>
                                    <div class="vc-picker__list" role="group" aria-label="Categories">
                                        @forelse($categories as $category)
                                            <label class="vc-pick"><input type="checkbox" name="category_ids[]" value="{{ $category->id }}"><span><b>{{ $category->name }}</b></span></label>
                                        @empty
                                            <p class="vc-muted">No categories available.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </fieldset>

                            <label class="vc-switch"><input type="checkbox" name="paused" value="1"><span>Save as paused <small>It will not be usable until you resume it.</small></span></label>
                        </div>

                        <aside class="vc-preview" aria-label="Voucher preview">
                            <span class="vc-preview__label">Buyer preview</span>
                            <div class="vc-ticket vc-ticket--active vc-ticket--mini">
                                <div class="vc-stub"><strong data-vc-pv-big>₱50</strong><span>OFF</span></div>
                                <div class="vc-body"><h3 data-vc-pv-name>Voucher name</h3><code data-vc-pv-code>CODE</code><small data-vc-pv-min>No minimum spend</small><small data-vc-pv-valid>Valid now</small></div>
                            </div>
                            <p class="vc-preview__note">The discount lowers the total the buyer pays for the eligible items.</p>
                        </aside>
                    </div>
                    <p class="vc-error" id="vcError" role="alert" hidden></p>
                </div>
                <footer class="vc-modal__foot">
                    <button type="button" class="vc-btn" data-vc-close>Cancel</button>
                    <button type="submit" class="vc-btn vc-btn--primary" id="vcSubmit">Save voucher</button>
                </footer>
            </form>
        </dialog>

        <dialog class="vc-confirm" id="vcConfirm" aria-labelledby="vcConfirmTitle">
            <h2 id="vcConfirmTitle">Delete this voucher?</h2>
            <p id="vcConfirmText"></p>
            <p class="vc-error" id="vcConfirmError" role="alert" hidden></p>
            <div class="vc-confirm__actions">
                <button type="button" class="vc-btn" data-vc-confirm-cancel>Keep voucher</button>
                <button type="button" class="vc-btn vc-btn--danger-solid" data-vc-confirm-ok>Delete voucher</button>
            </div>
        </dialog>
        <div class="vc-toast" id="vcToast" role="status" aria-live="polite"></div>
    </section>
</x-seller.layout>