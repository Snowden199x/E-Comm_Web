{{-- Quick "Add to Cart" sheet for products that need a colour/size, plus the cart toast.
     Include once per page that renders product cards. Cards open it with
     $dispatch('quick-add', payload). Requires #cart-badge from the buyer layout. --}}
<div x-data="{
        show: false, p: null, color: '', size: '', qty: 1,
        open(d) {
            this.p = d;
            this.color = d.colors.length === 1 ? d.colors[0] : '';
            this.size = d.sizes.length === 1 ? d.sizes[0] : '';
            this.qty = 1;
            this.show = true;
        },
        close() { this.show = false; },
        get valid() { return this.p && (!this.p.colors.length || this.color) && (!this.p.sizes.length || this.size); },
    }"
    x-effect="document.body.style.overflow = show ? 'hidden' : ''"
    @quick-add.window="open($event.detail)" @keydown.escape.window="close()">

    <div x-show="show" x-cloak class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-label="Choose options">
        <div x-show="show" @click="close()"
            x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-[#1b0b1e]/50 backdrop-blur-[2px]"></div>

        <div x-show="show"
            x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-3 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-6 opacity-0 sm:scale-95"
            class="relative max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:max-w-[400px] sm:rounded-2xl">

            <button type="button" @click="close()" aria-label="Close"
                class="absolute right-3 top-3 grid h-8 w-8 place-items-center rounded-full text-[#8a7a8e] transition-colors duration-200 hover:bg-[#f3e8f5] hover:text-[#402143]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>

            <template x-if="p">
                <form method="POST" action="{{ route('buyer.cart.store') }}" x-target="cart-badge buyer-toast" @ajax:after="close()">
                    @csrf
                    <input type="hidden" name="product_id" :value="p.id">
                    <template x-if="p.colors.length"><input type="hidden" name="color" :value="color"></template>
                    <template x-if="p.sizes.length"><input type="hidden" name="size" :value="size"></template>
                    <input type="hidden" name="quantity" :value="qty">

                    <div class="flex items-center gap-3 pr-8">
                        <img :src="p.image" alt="" class="h-16 w-16 flex-shrink-0 rounded-lg bg-[#f8f3e6] object-cover">
                        <div class="min-w-0">
                            <p class="line-clamp-2 text-[13px] leading-[18px] text-[#2b1730]" x-text="p.name"></p>
                            <p class="mt-1 text-[16px] font-semibold text-[#52245b]" x-text="p.price"></p>
                        </div>
                    </div>

                    <div x-show="p.colors.length" class="mt-5">
                        <p class="mb-2 text-[12px] font-medium text-[#5b4a60]">Color</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="c in p.colors" :key="c">
                                <button type="button" @click="color = c"
                                    :class="color === c ? 'border-[#805487] bg-[#f5ecf6] text-[#52245b]' : 'border-[#e5dce7] text-[#3d2a42] hover:border-[#c9a9ce]'"
                                    class="rounded-md border px-3 py-1.5 text-[12px] transition-all duration-200 ease-vendo active:scale-95" x-text="c"></button>
                            </template>
                        </div>
                    </div>

                    <div x-show="p.sizes.length" class="mt-4">
                        <p class="mb-2 text-[12px] font-medium text-[#5b4a60]">Size</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="s in p.sizes" :key="s">
                                <button type="button" @click="size = s"
                                    :class="size === s ? 'border-[#805487] bg-[#f5ecf6] text-[#52245b]' : 'border-[#e5dce7] text-[#3d2a42] hover:border-[#c9a9ce]'"
                                    class="min-w-[40px] rounded-md border px-3 py-1.5 text-[12px] transition-all duration-200 ease-vendo active:scale-95" x-text="s"></button>
                            </template>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between">
                        <p class="text-[12px] font-medium text-[#5b4a60]">Quantity <span class="font-normal text-[#9a8a9d]" x-text="'(' + p.stock + ' available)'"></span></p>
                        <div class="flex h-8 items-center overflow-hidden rounded-md border border-[#e5dce7]">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" :disabled="qty <= 1" aria-label="Decrease quantity"
                                class="h-full w-8 text-[16px] text-[#493e4b] transition-colors duration-150 hover:bg-[#f5ecf6] disabled:text-[#c4b9c6] disabled:hover:bg-transparent">−</button>
                            <span class="w-10 text-center text-[13px]" x-text="qty"></span>
                            <button type="button" @click="qty = Math.min(p.stock, qty + 1)" :disabled="qty >= p.stock" aria-label="Increase quantity"
                                class="h-full w-8 text-[16px] text-[#493e4b] transition-colors duration-150 hover:bg-[#f5ecf6] disabled:text-[#c4b9c6] disabled:hover:bg-transparent">+</button>
                        </div>
                    </div>

                    <button type="submit" :disabled="!valid"
                        class="mt-6 flex h-10 w-full items-center justify-center rounded-lg bg-[#805487] text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#6d4574] hover:shadow-[0_12px_20px_-12px_rgba(128,84,135,0.95)] active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-[#cdbfd0] disabled:shadow-none">
                        <span x-text="valid ? 'Add to Cart' : 'Select ' + ((p.colors.length && !color) ? 'a color' : 'a size')"></span>
                    </button>
                </form>
            </template>
        </div>
    </div>

    <!-- Cart feedback (replaced in place after Add to Cart) -->
    <div id="buyer-toast" aria-live="polite" class="pointer-events-none fixed inset-x-0 bottom-6 z-[70] flex justify-center px-4">
        @if (session('success') || $errors->any())
            <div x-data="{ show: false }"
                x-init="requestAnimationFrame(() => show = true); setTimeout(() => show = false, 3600)"
                x-show="show"
                x-transition:enter="transition duration-500 ease-vendo" x-transition:enter-start="translate-y-4 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition duration-300 ease-vendo" x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-2 opacity-0"
                class="pointer-events-auto flex items-center gap-3 rounded-xl px-4 py-3 text-[13px] text-white shadow-[0_18px_40px_-18px_rgba(43,23,48,0.6)] {{ $errors->any() ? 'bg-[#7a2b3a]' : 'bg-[#402143]' }}">
                <span>{{ $errors->any() ? $errors->first() : session('success') }}</span>
                @unless ($errors->any())
                    <a href="{{ route('buyer.cart.index') }}" class="font-medium text-[#e8c874] underline-offset-2 hover:underline">View cart</a>
                @endunless
            </div>
        @endif
    </div>
</div>