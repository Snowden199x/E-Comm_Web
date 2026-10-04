<x-buyer.layout title="Shopping Cart — Vendo">
    <div class="cart-page vb-enter">
        <header class="cart-head">
            <h1>Shopping Cart</h1>
            <nav class="cart-crumbs" aria-label="Breadcrumb">
                <a href="{{ route('buyer.dashboard') }}">Home</a>
                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
                <span aria-current="page">Cart</span>
            </nav>
        </header>

        @error('items')<p class="cart-error" role="alert">{{ $message }}</p>@enderror
        @error('quantity')<p class="cart-error" role="alert">{{ $message }}</p>@enderror

        <form id="checkoutSelection" action="{{ route('buyer.checkout.index') }}" method="GET"></form>

        @if($cartItems->isNotEmpty())
            <div class="cart-layout">
                <section class="cart-main" aria-label="Cart items">
                    <div class="cart-columns">
                        <label class="cart-columns__all">
                            <input type="checkbox" id="selectAllCart" aria-label="Select all items">
                            <span>Product</span>
                        </label>
                        <span class="cart-columns__col">Quantity</span>
                        <span class="cart-columns__col">Total Price</span>
                        <span></span>
                    </div>

                    <div class="cart-stores">
                        @foreach($cartItems->groupBy(fn ($item) => $item->product->seller_id) as $sellerItems)
                            @php
                                $seller = $sellerItems->first()->product->seller;
                                $shopName = $seller?->sellerDetail?->business_name ?: ($seller?->name ?? 'Store');
                            @endphp
                            <section class="cart-store" data-store-group aria-label="{{ $shopName }} items">
                                <div class="cart-store__heading">
                                    <input type="checkbox" data-store-select aria-label="Select all items from {{ $shopName }}">
                                    <a href="{{ route('buyer.sellers.show', $seller) }}" class="cart-store__link">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9h18l-1.3-5H4.3L3 9Z"/><path d="M4 9v11h16V9M8 20v-7h8v7M3 9c0 1.7 2.5 2.5 4.5 1.1C9 11.6 11 11.6 12 10c1 1.6 3 1.6 4.5.1C18.5 11.5 21 10.7 21 9"/></svg>
                                        <span>{{ $shopName }}</span>
                                        <svg class="cart-store__chevron" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
                                    </a>
                                </div>

                                @foreach($sellerItems as $item)
                                    @php
                                        $line = $item->unit_price * $item->quantity;
                                        $stock = $item->variant?->stock ?? $item->product->stock;
                                        $stockKey = $item->product_variant_id ?: 'product-'.$item->product_id;
                                    @endphp
                                    <div class="cart-item">
                                        <input type="checkbox" name="items[]" value="{{ $item->id }}" form="checkoutSelection" data-cart-item data-price="{{ $line }}" aria-label="Select {{ $item->product->name }} for checkout">

                                        <div class="cart-item__product">
                                            <a href="{{ route('buyer.products.show', $item->product) }}" class="cart-item__image-link"><img src="{{ $item->product->images->first() ? asset('storage/'.$item->product->images->first()->path) : asset('images/products/tote-bag.jpg') }}" alt="{{ $item->product->name }}" loading="lazy"></a>
                                            <div class="cart-item__details">
                                                <a href="{{ route('buyer.products.show', $item->product) }}" class="cart-item__name">{{ $item->product->name }}</a>
                                                @if($item->color)<p class="cart-item__variant">Color: {{ $item->color }}</p>@endif
                                                @if($item->size)<p class="cart-item__variant">Size: {{ $item->size }}</p>@endif
                                                @if($item->variant)<p class="cart-item__variant">{{ $item->variant->label }}</p>@endif
                                                <span class="cart-item__unit">₱{{ number_format($item->unit_price, 2) }}</span>
                                                @if($totalsByProduct[$stockKey] > $stock)
                                                    <p class="cart-item__stock">Only {{ $stock }} left; you have {{ $totalsByProduct[$stockKey] }} in cart.</p>
                                                @endif
                                            </div>
                                        </div>

                                        <form action="{{ route('buyer.cart.update', $item) }}" method="POST" class="cart-item__quantity" data-quantity-form>
                                            @csrf @method('PATCH')
                                            <button type="button" data-quantity-step="-1" aria-label="Decrease quantity of {{ $item->product->name }}" @disabled($item->quantity <= 1)>−</button>
                                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" aria-label="Quantity of {{ $item->product->name }}" onchange="this.form.requestSubmit()">
                                            <button type="button" data-quantity-step="1" aria-label="Increase quantity of {{ $item->product->name }}">+</button>
                                        </form>

                                        <strong class="cart-item__subtotal">₱{{ number_format($line, 2) }}</strong>

                                        <form action="{{ route('buyer.cart.destroy', $item) }}" method="POST" class="cart-item__remove-form">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="cart-item__remove" aria-label="Remove {{ $item->product->name }} from cart">
                                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4.5h6V7M6.5 7l.8 12.5h9.4L17.5 7M10 11v5M14 11v5"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                </section>

                <aside class="cart-summary" aria-labelledby="orderSummaryTitle">
                    <h2 id="orderSummaryTitle">Order Summary</h2>
                    <dl class="cart-summary__rows">
                        <div><dt>Subtotal (<span id="selectedCount">0 items</span>)</dt><dd id="selectedTotal">₱0.00</dd></div>
                        <div><dt>Shipping Fee</dt><dd class="cart-summary__muted">Calculated at checkout</dd></div>
                    </dl>
                    <div class="cart-summary__total"><span>Total</span><strong id="grandTotal">₱0.00</strong></div>
                    <div class="cart-summary__payment"><span>Payment Method</span><span>Cash on Delivery</span></div>
                    <button type="submit" form="checkoutSelection" id="checkoutSelected" disabled class="cart-summary__checkout">Proceed to Checkout <span id="checkoutCountWrap">(<span id="checkoutCount">0</span>)</span></button>
                    <p class="cart-summary__hint" id="checkoutHint">Select the items you want to check out.</p>
                </aside>
            </div>
        @else
            <div class="cart-empty">
                <span class="cart-empty__icon"><span class="vb-icon" style="--icon: url('{{ asset('assets/icons/buyer/cart-icon.svg') }}')"></span></span>
                <p>Your cart is empty.</p>
                <a href="{{ route('buyer.products.index') }}">Start Shopping</a>
            </div>
        @endif

        {{-- Optional: controller can pass $alsoLike (collection of Product with images, seller.sellerDetail) --}}
        @if(isset($alsoLike) && $alsoLike->isNotEmpty())
            <section class="cart-also" aria-labelledby="alsoLikeTitle">
                <h2 id="alsoLikeTitle">You May Also Like</h2>
                <div class="cart-also__grid">
                    @foreach($alsoLike as $product)
                        @include('buyer.partials.product-card', ['product' => $product, 'compact' => true])
                    @endforeach
                </div>
            </section>
            @include('buyer.partials.quick-add')
        @endif
    </div>

    <script>
    (() => {
        const items = [...document.querySelectorAll('[data-cart-item]')];
        const all = document.getElementById('selectAllCart');
        if (!all) return;
        const selectionKey = 'vendo-cart-selection-{{ auth()->id() }}';
        const money = value => '₱' + value.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // Restore the previous selection; on first visit everything starts selected.
        let saved = null;
        try { saved = JSON.parse(sessionStorage.getItem(selectionKey)); } catch (_) {}
        items.forEach(box => box.checked = Array.isArray(saved) ? saved.includes(box.value) : true);

        function update() {
            document.querySelectorAll('[data-store-group]').forEach(group => {
                const boxes = [...group.querySelectorAll('[data-cart-item]')];
                const selected = boxes.filter(box => box.checked).length;
                const store = group.querySelector('[data-store-select]');
                store.checked = selected === boxes.length;
                store.indeterminate = selected > 0 && selected < boxes.length;
            });
            const selected = items.filter(box => box.checked);
            const total = selected.reduce((sum, box) => sum + Number(box.dataset.price), 0);
            all.checked = selected.length === items.length;
            all.indeterminate = selected.length > 0 && selected.length < items.length;
            document.getElementById('selectedCount').textContent = selected.length + (selected.length === 1 ? ' item' : ' items');
            document.getElementById('selectedTotal').textContent = money(total);
            document.getElementById('grandTotal').textContent = money(total);
            document.getElementById('checkoutCount').textContent = selected.length;
            document.getElementById('checkoutSelected').disabled = selected.length === 0;
            document.getElementById('checkoutHint').hidden = selected.length > 0;
            try { sessionStorage.setItem(selectionKey, JSON.stringify(selected.map(box => box.value))); } catch (_) {}
        }

        all.addEventListener('change', () => { items.forEach(box => box.checked = all.checked); update(); });
        document.querySelectorAll('[data-store-select]').forEach(store => store.addEventListener('change', () => {
            store.closest('[data-store-group]').querySelectorAll('[data-cart-item]').forEach(box => box.checked = store.checked);
            update();
        }));
        items.forEach(box => box.addEventListener('change', update));
        document.querySelectorAll('[data-quantity-form]').forEach(form => form.addEventListener('submit', event => {
            if (form.dataset.submitting === '1') { event.preventDefault(); return; }
            form.dataset.submitting = '1';
            form.querySelectorAll('button').forEach(button => button.disabled = true);
        }));
        document.querySelectorAll('[data-quantity-step]').forEach(button => button.addEventListener('click', () => {
            const form = button.closest('[data-quantity-form]');
            const input = form.querySelector('input[name="quantity"]');
            input.value = Math.max(1, Number(input.value || 1) + Number(button.dataset.quantityStep));
            form.requestSubmit();
        }));
        update();
    })();
    </script>
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'cart'), 'mode' => 'notice'])
</x-buyer.layout>
