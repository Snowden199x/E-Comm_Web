{{--
    Buyer storefront footer. Included by components/buyer/layout.blade.php (pass :footer="false" to hide it on a page).
    Only links that exist today are shown: there is no Help Center, contact page, or social account to link to yet.
--}}
@php
    $footerShopsUrl = \Illuminate\Support\Facades\Route::has('buyer.sellers.index') ? route('buyer.sellers.index') : null;
@endphp
<footer class="vb-footer mt-10" aria-label="Site footer">
    <div class="mx-auto max-w-[1200px] px-4 pb-6 pt-10">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1fr]">

            <div>
                <a href="{{ route('buyer.dashboard') }}" aria-label="Vendo home" class="inline-block">
                    <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="h-10 w-auto">
                </a>
                <p class="mt-3 max-w-[320px] text-[13px] leading-6">Discover products from approved sellers, pay on delivery, and follow every order from the seller to your door.</p>
                <ul class="mt-4 flex flex-wrap gap-2 text-[11px] font-medium text-white/80">
                    <li class="rounded-full border border-white/15 px-3 py-1">Cash on Delivery</li>
                    <li class="rounded-full border border-white/15 px-3 py-1">Order tracking</li>
                    <li class="rounded-full border border-white/15 px-3 py-1">Approved sellers</li>
                </ul>
            </div>

            <nav aria-labelledby="vb-foot-shop">
                <h2 id="vb-foot-shop" class="text-[12px] font-semibold uppercase tracking-[0.14em] text-white">Shop</h2>
                <ul class="mt-3 space-y-0.5 text-[13px]">
                    <li><a class="vb-footer__link" href="{{ route('buyer.dashboard') }}">Home</a></li>
                    <li><a class="vb-footer__link" href="{{ route('buyer.products.index') }}">All products</a></li>
                    <li><a class="vb-footer__link" href="{{ route('buyer.categories') }}">Categories</a></li>
                    @if ($footerShopsUrl)
                        <li><a class="vb-footer__link" href="{{ $footerShopsUrl }}">Find a shop</a></li>
                    @endif
                </ul>
            </nav>

            <nav aria-labelledby="vb-foot-account">
                <h2 id="vb-foot-account" class="text-[12px] font-semibold uppercase tracking-[0.14em] text-white">My account</h2>
                <ul class="mt-3 space-y-0.5 text-[13px]">
                    <li><a class="vb-footer__link" href="{{ route('buyer.orders.index') }}">My Orders</a></li>
                    <li><a class="vb-footer__link" href="{{ route('buyer.cart.index') }}">Cart</a></li>
                    <li><a class="vb-footer__link" href="{{ route('buyer.messages.index') }}">Messages</a></li>
                    <li><a class="vb-footer__link" href="{{ route('buyer.notifications.index') }}">Notifications</a></li>
                    <li><a class="vb-footer__link" href="{{ route('buyer.account.index') }}">Account Management</a></li>
                    <li><a class="vb-footer__link" href="{{ route('buyer.account.index', ['tab' => 'settings']) }}">Settings</a></li>
                </ul>
            </nav>

            <nav aria-labelledby="vb-foot-legal">
                <h2 id="vb-foot-legal" class="text-[12px] font-semibold uppercase tracking-[0.14em] text-white">Policies</h2>
                <ul class="mt-3 space-y-0.5 text-[13px]">
                    <li><a class="vb-footer__link" href="{{ route('legal.terms') }}">Terms and Conditions</a></li>
                    <li><a class="vb-footer__link" href="{{ route('legal.privacy') }}">Privacy Policy</a></li>
                    <li><a class="vb-footer__link" href="{{ route('buyer.account.index', ['tab' => 'privacy']) }}">All policies</a></li>
                </ul>
            </nav>
        </div>

        <div class="mt-8 flex flex-col items-start justify-between gap-2 border-t border-white/10 pt-5 text-[12px] text-white/55 sm:flex-row sm:items-center">
            <p>&copy; {{ date('Y') }} Vendo. All rights reserved.</p>
            <p>Buy. Sell. Delivered.</p>
        </div>
    </div>
</footer>