<x-buyer.layout>
    <div class="cart-page">
        <header class="cart-page__heading">
            <h1>My Cart</h1>
            <span>{{ $cartItems->count() }} {{ $cartItems->count() === 1 ? 'item' : 'items' }}</span>
        </header>

        @error('items')<p class="cart-error" role="alert">{{ $message }}</p>@enderror
        @error('quantity')<p class="cart-error" role="alert">{{ $message }}</p>@enderror

        <form id="checkoutSelection" action="{{ route('buyer.checkout.index') }}" method="GET"></form>

        @if($cartItems->isNotEmpty())
            <div class="cart-columns" aria-hidden="true">
                <span></span><span>Product</span><span>Unit price</span><span>Quantity</span><span>Subtotal</span><span></span>
            </div>
        @endif

        <div class="cart-stores">
            @forelse($cartItems->groupBy(fn ($item) => $item->product->seller_id) as $sellerItems)
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
                            <svg class="cart-store__chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
                        </a>
                        <span class="cart-store__count">{{ $sellerItems->count() }} {{ $sellerItems->count() === 1 ? 'item' : 'items' }}</span>
                    </div>

                    @foreach($sellerItems as $item)
                        <div class="cart-item">
                            <input type="checkbox" name="items[]" value="{{ $item->id }}" form="checkoutSelection" data-cart-item data-price="{{ $item->product->price * $item->quantity }}" aria-label="Select {{ $item->product->name }} for checkout">
                            <div class="cart-item__product">
                                <a href="{{ route('buyer.products.show', $item->product) }}" class="cart-item__image-link"><img src="{{ $item->product->images->first() ? asset('storage/'.$item->product->images->first()->path) : asset('images/products/tote-bag.jpg') }}" alt="{{ $item->product->name }}"></a>
                                <div class="cart-item__details">
                                    <a href="{{ route('buyer.products.show', $item->product) }}" class="cart-item__name">{{ $item->product->name }}</a>
                                    @if($item->color || $item->size)<p class="cart-item__variant">{{ implode(' / ', array_filter([$item->color, $item->size])) }}</p>@endif
                                    <span class="cart-item__mobile-price">₱{{ number_format($item->product->price, 2) }} each</span>
                                    @if($totalsByProduct[$item->product_id] > $item->product->stock)
                                        <p class="cart-item__stock">Only {{ $item->product->stock }} left; you have {{ $totalsByProduct[$item->product_id] }} in cart.</p>
                                    @endif
                                </div>
                            </div>
                            <span class="cart-item__unit">₱{{ number_format($item->product->price, 2) }}</span>
                            <form action="{{ route('buyer.cart.update', $item) }}" method="POST" class="cart-item__quantity" data-quantity-form>
                                @csrf @method('PATCH')
                                <button type="button" data-quantity-step="-1" aria-label="Decrease quantity of {{ $item->product->name }}" @disabled($item->quantity <= 1)>−</button>
                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" aria-label="Quantity of {{ $item->product->name }}" onchange="this.form.requestSubmit()">
                                <button type="button" data-quantity-step="1" aria-label="Increase quantity of {{ $item->product->name }}">+</button>
                            </form>
                            <strong class="cart-item__subtotal">₱{{ number_format($item->product->price * $item->quantity, 2) }}</strong>
                            <form action="{{ route('buyer.cart.destroy', $item) }}" method="POST" class="cart-item__remove-form">
                                @csrf @method('DELETE')
                                <button type="submit" class="cart-item__remove">Remove</button>
                            </form>
                        </div>
                    @endforeach
                </section>
            @empty
                <div class="cart-empty">
                    <p>Your cart is empty.</p>
                    <a href="{{ route('buyer.products.index') }}">Browse products</a>
                </div>
            @endforelse
        </div>

        @if($cartItems->isNotEmpty())
            <div class="cart-summary">
                <label class="cart-summary__select"><input type="checkbox" id="selectAllCart"><span>Select all ({{ $cartItems->count() }})</span></label>
                <div class="cart-summary__total"><span>Total (<span id="selectedCount">0 items</span>):</span><strong id="selectedTotal">₱0.00</strong></div>
                <button type="submit" form="checkoutSelection" id="checkoutSelected" disabled class="cart-summary__checkout">Checkout (<span id="checkoutCount">0</span>)</button>
            </div>
        @endif
    </div>

    <script>
    (() => {
        const items = [...document.querySelectorAll('[data-cart-item]')];
        const all = document.getElementById('selectAllCart');
        if (!all) return;
        const selectionKey = 'vendo-cart-selection-{{ auth()->id() }}';
        try {
            const saved = JSON.parse(sessionStorage.getItem(selectionKey) || '[]');
            if (Array.isArray(saved)) items.forEach(box => box.checked = saved.includes(box.value));
        } catch (_) {}

        function update() {
            document.querySelectorAll('[data-store-group]').forEach(group => {
                const boxes = [...group.querySelectorAll('[data-cart-item]')];
                const selected = boxes.filter(box => box.checked).length;
                const store = group.querySelector('[data-store-select]');
                store.checked = selected === boxes.length;
                store.indeterminate = selected > 0 && selected < boxes.length;
            });
            const selected = items.filter(box => box.checked);
            all.checked = selected.length === items.length;
            all.indeterminate = selected.length > 0 && selected.length < items.length;
            document.getElementById('selectedCount').textContent = selected.length + (selected.length === 1 ? ' item' : ' items');
            document.getElementById('selectedTotal').textContent = '₱' + selected.reduce((sum, box) => sum + Number(box.dataset.price), 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('checkoutCount').textContent = selected.length;
            document.getElementById('checkoutSelected').disabled = selected.length === 0;
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
