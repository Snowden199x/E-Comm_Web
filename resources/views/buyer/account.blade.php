{{--
    Account (Profile, Password) and Settings (Appearance, Delivery, Notifications, Favorites, Privacy) on one page.

    Data from BuyerAccountController@index (unchanged): $buyerDetail, $policies, $deliveryCompanies.
    Deep links: ?tab=profile | security (Account) and ?tab=settings (= preferences) | delivery | notifications | saved | privacy.
    Forms and field names are unchanged: account.update (name, phone_number, house_no, street, zip_code, province, municipality,
    barangay, latitude, longitude), account.password (current_password, password, password_confirmation),
    account.delivery-preference (preferred_delivery_company_id), banner and profile-picture upload/remove.

    States: success and failure messages open as banners; every field shows its error with the fix under it; uploads show
    a spinner over the photo and are checked in the browser first (type and size); "Save" shows a spinner while it posts;
    delivery, favorites and the policy list have empty states.
--}}
@php
    use Illuminate\Support\Str;

    $user = auth()->user();
    $aliases = ['settings' => 'preferences', 'policies' => 'privacy'];
    $requested = $aliases[request('tab', 'profile')] ?? request('tab', 'profile');
    $tab = in_array($requested, ['profile', 'security', 'preferences', 'delivery', 'notifications', 'saved', 'privacy'], true) ? $requested : 'profile';
    $deletionFailed = $errors->has('reasons') || $errors->has('reasons.0') || $errors->has('comments');
    if ($errors->has('current_password') || $errors->has('password') || session('password_success')) {
        $tab = 'security';
    } elseif ($errors->has('preferred_delivery_company_id')) {
        $tab = 'delivery';
    } elseif ($deletionFailed) {
        $tab = 'privacy';
    } elseif ($errors->any()) {
        $tab = 'profile';
    }
    $memberSince = $user->created_at?->format('F Y');
    $avatar = $user->profile_picture ? asset('storage/' . $user->profile_picture) : asset('images/logo/vendo-icon.png');
    $bannerUrl = $buyerDetail?->banner_path ? asset('storage/' . $buyerDetail->banner_path) : null;

    $groups = [
        'Account' => [
            'profile' => ['Profile', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 20c0-4 3.6-6 8-6s8 2 8 6'],
            'security' => ['Password', 'M6 11V8a6 6 0 1 1 12 0v3M5 11h14v9H5zM12 15v2'],
        ],
        'Settings' => [
            'preferences' => ['Appearance', 'M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'],
            'delivery' => ['Delivery', 'M3 7h11v8H3zM14 10h4l3 3v2h-7zM6.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM17.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z'],
            'notifications' => ['Notifications', 'M18 16v-5a6 6 0 1 0-12 0v5l-2 3h16zM10 21a2 2 0 0 0 4 0'],
            'saved' => ['Favorites', 'M12 20.4s-7.6-4.6-9.2-9.5C1.7 7.5 3.8 4.6 6.9 4.6c1.9 0 3.6 1 5.1 3 1.5-2 3.2-3 5.1-3 3.1 0 5.2 2.9 4.1 6.3-1.6 4.9-9.2 9.5-9.2 9.5z'],
            'privacy' => ['Privacy and terms', 'M12 3l7 3v5c0 5-3 8.5-7 10-4-1.5-7-5-7-10V6zM9 12l2 2 4-4'],
        ],
    ];

    // One look for every field, label, error and section
    $field = 'vb-field h-10 w-full px-3';
    $label = 'mb-1 flex items-baseline justify-between text-[12px] font-medium text-[#5b4a60]';
    $card = 'rounded-xl border border-[#eee6ef] bg-white';
    $cardHead = 'border-b border-[#f1e8f2] px-5 py-4';
    $title = 'text-[15px] font-semibold text-[#402143]';
    $sub = 'mt-0.5 text-[12px] leading-5 text-[#7a6a7e]';
    $invalid = fn (string $name) => $errors->has($name) ? '!border-[#e3a5b0] !bg-[#fffafb]' : '';
    $enter = 'transition duration-500 ease-vendo';
    $errorIcon = '<svg class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5h.01"/></svg>';
@endphp

<x-buyer.layout title="Account — Vendo">
    {{-- Server messages open as banners --}}
    @if (session('success') || session('password_success'))
        <span x-data x-init="$store.banner.show(@js(['type' => 'success', 'title' => session('password_success') ?: session('success')]))"></span>
    @elseif ($errors->any())
        <span x-data x-init="$store.banner.show(@js(['type' => 'error', 'title' => "Couldn't save", 'text' => 'Fix the highlighted fields, then try again.']))"></span>
    @endif

    <div class="mx-auto max-w-[1040px] px-3 pb-8 pt-4 sm:px-4"
        x-data="{
            tab: @js($tab),
            delOpen: @js($deletionFailed),
            prefs: window.vendoPrefs.get(),
            confirmReset: false,
            go(t) {
                this.tab = t;
                const url = new URL(location.href);
                url.searchParams.set('tab', t);
                history.replaceState(null, '', url);
                if (window.innerWidth < 1024) this.$nextTick(() => this.$refs.panels.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            },
            async setPref(key, value) {
                try {
                    this.prefs = await window.vendoPrefs.set(key, value);
                    $store.banner.show({ type: 'success', title: 'Saved', duration: 2000 });
                } catch (error) { $store.ui.say(error.message); }
            },
            async resetPrefs() {
                try {
                    this.prefs = await window.vendoPrefs.reset();
                    this.confirmReset = false;
                    $store.banner.show({ type: 'success', title: 'Settings reset', duration: 2500 });
                } catch (error) { $store.ui.say(error.message); }
            },
        }">

        <div class="vb-reveal">
            <h1 class="text-[20px] font-semibold leading-7 text-[#402143]" x-text="['profile','security'].includes(tab) ? 'Account' : 'Settings'">Account</h1>
            <p class="mt-0.5 text-[12px] text-[#7a6a7e]" x-text="['profile','security'].includes(tab) ? 'Manage your profile and password.' : 'Choose how Vendo looks, delivers and notifies you.'">Manage your profile and password.</p>
        </div>

        <div class="mt-4 grid items-start gap-4 lg:grid-cols-[240px_1fr]">

            <!-- ===================== Side menu ===================== -->
            <aside class="vb-reveal lg:sticky lg:top-[132px]" style="--i: 1">
                <div class="{{ $card }} hidden items-center gap-3 p-4 lg:flex">
                    <img src="{{ $avatar }}" alt="" class="h-12 w-12 flex-shrink-0 rounded-full border border-[#eee6ef] bg-white object-cover">
                    <div class="min-w-0">
                        <p class="truncate text-[14px] font-semibold text-[#2b1730]">{{ $user->name }}</p>
                        <p class="truncate text-[12px] text-[#7a6a7e]">{{ $user->email }}</p>
                    </div>
                </div>

                <nav aria-label="Account sections" class="vb-no-scrollbar -mx-3 flex gap-1.5 overflow-x-auto px-3 pb-1 sm:-mx-4 sm:px-4 lg:mx-0 lg:mt-3 lg:block lg:space-y-3 lg:overflow-visible lg:p-0">
                    @foreach ($groups as $groupName => $items)
                        <div class="flex flex-shrink-0 gap-1.5 lg:block lg:rounded-xl lg:border lg:border-[#eee6ef] lg:bg-white lg:p-2">
                            <p class="hidden px-3 pb-1 pt-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-[#7a6a7e] lg:block">{{ $groupName }}</p>
                            @foreach ($items as $key => [$itemLabel, $iconPath])
                                <button type="button" @click="go('{{ $key }}')" :aria-current="tab === '{{ $key }}' ? 'page' : null"
                                    :class="tab === '{{ $key }}' ? 'bg-[#402143] text-white' : 'bg-white text-[#3d2a42] hover:bg-[#f5ecf6] lg:bg-transparent'"
                                    class="vb-act flex h-10 flex-shrink-0 items-center gap-2.5 whitespace-nowrap rounded-lg border border-[#eee6ef] px-3 text-[13px] font-medium transition-colors duration-200 ease-vendo lg:w-full lg:border-0">
                                    <svg class="vb-ico h-[18px] w-[18px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $iconPath }}" /></svg>
                                    {{ $itemLabel }}
                                    @if ($key === 'saved')
                                        <span x-show="$store.fav.items.length" x-cloak x-text="$store.fav.items.length"
                                            :class="tab === 'saved' ? 'bg-white/20 text-white' : 'bg-[#f3e8f5] text-[#52245b]'"
                                            class="ml-auto rounded-full px-1.5 text-[11px] font-medium leading-[18px]"></span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </nav>
            </aside>

            <div class="min-w-0 space-y-4" x-ref="panels">

                <!-- ===================== Profile ===================== -->
                <section x-show="tab === 'profile'" @if ($tab !== 'profile') x-cloak @endif
                    x-transition:enter="{{ $enter }}" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="space-y-4" aria-label="Profile">

                    {{-- Photo, banner and name. No overflow-hidden on the card, so the two menus are never clipped. --}}
                    <div class="{{ $card }}"
                        x-data="{
                            bannerMenu: false, avatarMenu: false, uploading: null,
                            crop: { open: false, busy: false, kind: 'avatar', src: '', w: 0, h: 0, fw: 0, fh: 0, min: 1, z: 1, x: 0, y: 0, input: null, pointers: {} },
                            spec: {
                                avatar: { out: [512, 512], ratio: 1, max: 300, title: 'Crop photo', save: 'Save photo' },
                                banner: { out: [1600, 400], ratio: 4, max: 520, title: 'Crop banner', save: 'Save banner' },
                            },
                            // Step 1: a file was chosen. Check it, read it, then open the cropper.
                            pick(e, kind) {
                                const file = e.target.files[0];
                                if (!file) return;
                                const fail = (title, text) => { e.target.value = ''; $store.banner.show({ type: 'error', title, text }); };
                                if (!file.type.startsWith('image/')) return fail(`That isn't an image`, 'Choose a JPG, PNG or WebP file.');
                                if (file.size > 15 * 1024 * 1024) return fail('That image is too large', 'Choose an image under 15 MB.');
                                const src = URL.createObjectURL(file);
                                const img = new Image();
                                img.onload = () => this.openCrop(e.target, kind, src, img.naturalWidth, img.naturalHeight);
                                img.onerror = () => { URL.revokeObjectURL(src); fail(`We couldn't read that image`, 'Try a JPG, PNG or WebP file.'); };
                                img.src = src;
                            },
                            openCrop(input, kind, src, w, h) {
                                const spec = this.spec[kind];
                                const fw = Math.round(Math.min(spec.max, window.innerWidth - 72));
                                const fh = Math.round(fw / spec.ratio);
                                const min = Math.max(fw / w, fh / h);
                                this.crop = { open: true, busy: false, kind, src, w, h, fw, fh, min, z: min, x: (fw - w * min) / 2, y: (fh - h * min) / 2, input, pointers: {} };
                                this.$nextTick(() => this.$refs.cropFrame?.focus());
                            },
                            // The image must always cover the frame
                            clamp() {
                                const c = this.crop;
                                c.x = Math.min(0, Math.max(c.fw - c.w * c.z, c.x));
                                c.y = Math.min(0, Math.max(c.fh - c.h * c.z, c.y));
                            },
                            // Zoom around the middle of the frame, 1x to 4x
                            zoomTo(z) {
                                const c = this.crop;
                                z = Math.min(c.min * 4, Math.max(c.min, z));
                                const cx = (c.fw / 2 - c.x) / c.z, cy = (c.fh / 2 - c.y) / c.z;
                                c.z = z; c.x = c.fw / 2 - cx * z; c.y = c.fh / 2 - cy * z;
                                this.clamp();
                            },
                            get zoomPct() { return this.crop.min ? Math.round((this.crop.z / this.crop.min - 1) / 3 * 100) : 0; },
                            setZoomPct(p) { this.zoomTo(this.crop.min * (1 + 3 * p / 100)); },
                            resetCrop() {
                                const c = this.crop;
                                c.z = c.min; c.x = (c.fw - c.w * c.min) / 2; c.y = (c.fh - c.h * c.min) / 2;
                            },
                            down(e) { this.crop.pointers[e.pointerId] = { x: e.clientX, y: e.clientY }; e.currentTarget.setPointerCapture(e.pointerId); },
                            move(e) {
                                const c = this.crop, p = c.pointers[e.pointerId];
                                if (!p) return;
                                const ids = Object.keys(c.pointers);
                                if (ids.length === 2) {
                                    // Two fingers: pinch to zoom
                                    const other = c.pointers[ids.find((id) => Number(id) !== e.pointerId)];
                                    const before = Math.hypot(p.x - other.x, p.y - other.y);
                                    const after = Math.hypot(e.clientX - other.x, e.clientY - other.y);
                                    c.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
                                    if (before) this.zoomTo(c.z * after / before);
                                    return;
                                }
                                c.x += e.clientX - p.x; c.y += e.clientY - p.y;
                                c.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
                                this.clamp();
                            },
                            up(e) { delete this.crop.pointers[e.pointerId]; },
                            key(e) {
                                const c = this.crop, step = e.shiftKey ? 30 : 10;
                                const moves = { ArrowLeft: [step, 0], ArrowRight: [-step, 0], ArrowUp: [0, step], ArrowDown: [0, -step] };
                                if (moves[e.key]) { e.preventDefault(); c.x += moves[e.key][0]; c.y += moves[e.key][1]; this.clamp(); }
                                else if (e.key === '+' || e.key === '=') { e.preventDefault(); this.zoomTo(c.z * 1.1); }
                                else if (e.key === '-') { e.preventDefault(); this.zoomTo(c.z / 1.1); }
                            },
                            closeCrop() {
                                if (this.crop.busy) return;
                                if (this.crop.input) this.crop.input.value = '';
                                URL.revokeObjectURL(this.crop.src);
                                this.crop.open = false;
                            },
                            // Step 2: draw the visible part to a canvas, put it in the file input, and send the form as before.
                            async saveCrop() {
                                const c = this.crop;
                                if (c.busy) return;
                                c.busy = true;
                                try {
                                    const [ow, oh] = this.spec[c.kind].out;
                                    const img = await new Promise((resolve, reject) => { const i = new Image(); i.onload = () => resolve(i); i.onerror = reject; i.src = c.src; });
                                    const canvas = document.createElement('canvas');
                                    canvas.width = ow; canvas.height = oh;
                                    const ctx = canvas.getContext('2d');
                                    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, ow, oh);
                                    ctx.imageSmoothingQuality = 'high';
                                    ctx.drawImage(img, -c.x / c.z, -c.y / c.z, c.fw / c.z, c.fh / c.z, 0, 0, ow, oh);
                                    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
                                    if (!blob) throw new Error('empty');
                                    const transfer = new DataTransfer();
                                    transfer.items.add(new File([blob], c.kind + '.jpg', { type: 'image/jpeg' }));
                                    c.input.files = transfer.files;
                                    this.uploading = c.kind;
                                    const form = c.input.form;
                                    c.open = false;
                                    form.submit();
                                } catch (error) {
                                    c.busy = false;
                                    $store.banner.show({ type: 'error', title: `Couldn't prepare the image`, text: 'Try a different image, or a smaller one.' });
                                }
                            },
                        }"
                        @pageshow.window="uploading = null">
                        <form id="banner-form" action="{{ route('buyer.account.banner.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="banner" accept="image/*" x-ref="bannerInput" class="absolute h-px w-px overflow-hidden opacity-0" tabindex="-1" aria-label="Upload banner" @change="pick($event, 'banner')">
                        </form>
                        <form id="avatar-form" action="{{ route('buyer.account.profile-picture.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="profile_picture" accept="image/*" x-ref="avatarInput" class="absolute h-px w-px overflow-hidden opacity-0" tabindex="-1" aria-label="Upload profile picture" @change="pick($event, 'avatar')">
                        </form>

                        {{-- Crop dialog for photo and banner. Moved to <body> so it always dims the whole screen. --}}
                        <template x-teleport="body">
                            <div x-show="crop.open" x-cloak class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center sm:p-4"
                                @keydown.escape.window="if (crop.open) closeCrop()" x-effect="document.body.style.overflow = crop.open ? 'hidden' : ''">
                                <div x-show="crop.open" @click="closeCrop()"
                                    x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition-opacity duration-200 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                    class="absolute inset-0 bg-[#1b0b1e]/50"></div>

                                <section x-show="crop.open" role="dialog" aria-modal="true" aria-labelledby="crop-title"
                                    x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                                    x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100" x-transition:leave-end="translate-y-6 opacity-0 sm:scale-95"
                                    class="relative max-h-[94dvh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:w-auto sm:min-w-[400px] sm:max-w-[600px] sm:rounded-2xl sm:p-6">

                                    <h2 id="crop-title" class="text-[16px] font-semibold leading-6 text-[#2b1730]" x-text="spec[crop.kind].title">Crop photo</h2>
                                    <p class="text-[12px] leading-5 text-[#7a6a7e]">Drag to move. Scroll, pinch or use the slider to zoom.</p>

                                    <div class="mt-4 flex justify-center">
                                        <div x-ref="cropFrame" tabindex="0" role="img" aria-label="Crop area. Use arrow keys to move and plus or minus to zoom."
                                            @pointerdown.prevent="down($event)" @pointermove="move($event)" @pointerup="up($event)" @pointercancel="up($event)"
                                            @wheel.prevent="zoomTo(crop.z * ($event.deltaY < 0 ? 1.08 : 0.92))" @keydown="key($event)"
                                            class="relative cursor-grab select-none overflow-hidden rounded-lg bg-[#2b1730] outline-none focus-visible:ring-2 focus-visible:ring-[#805487] focus-visible:ring-offset-2 active:cursor-grabbing"
                                            :style="{ width: crop.fw + 'px', height: crop.fh + 'px', touchAction: 'none' }">
                                            <img :src="crop.src" alt="" draggable="false" class="pointer-events-none absolute select-none"
                                                :style="{ width: (crop.w * crop.z) + 'px', height: (crop.h * crop.z) + 'px', left: crop.x + 'px', top: crop.y + 'px', maxWidth: 'none' }">
                                            {{-- Photo: the circle is what shows. Banner: thirds grid. --}}
                                            <span x-show="crop.kind === 'avatar'" class="pointer-events-none absolute inset-0 rounded-full" style="box-shadow: 0 0 0 999px rgba(255,255,255,0.72), inset 0 0 0 2px #fff"></span>
                                            <span x-show="crop.kind === 'banner'" class="pointer-events-none absolute inset-0" aria-hidden="true"
                                                style="background-image: linear-gradient(to right, transparent calc(33.33% - 0.5px), rgba(255,255,255,.55) calc(33.33% - 0.5px), rgba(255,255,255,.55) calc(33.33% + 0.5px), transparent calc(33.33% + 0.5px), transparent calc(66.66% - 0.5px), rgba(255,255,255,.55) calc(66.66% - 0.5px), rgba(255,255,255,.55) calc(66.66% + 0.5px), transparent calc(66.66% + 0.5px)), linear-gradient(to bottom, transparent calc(33.33% - 0.5px), rgba(255,255,255,.55) calc(33.33% - 0.5px), rgba(255,255,255,.55) calc(33.33% + 0.5px), transparent calc(33.33% + 0.5px), transparent calc(66.66% - 0.5px), rgba(255,255,255,.55) calc(66.66% - 0.5px), rgba(255,255,255,.55) calc(66.66% + 0.5px), transparent calc(66.66% + 0.5px))"></span>
                                        </div>
                                    </div>

                                    <div class="mx-auto mt-4 flex items-center gap-3" :style="{ maxWidth: crop.fw + 'px' }">
                                        <button type="button" @click="zoomTo(crop.z / 1.15)" aria-label="Zoom out" class="vb-act grid h-8 w-8 flex-shrink-0 place-items-center rounded-full text-[#805487] transition-colors duration-200 hover:bg-[#f3e8f5]">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5M8 11h6" /></svg>
                                        </button>
                                        <input type="range" min="0" max="100" step="1" :value="zoomPct" @input="setZoomPct($event.target.value)" aria-label="Zoom" class="h-1.5 w-full cursor-pointer accent-[#805487]">
                                        <button type="button" @click="zoomTo(crop.z * 1.15)" aria-label="Zoom in" class="vb-act grid h-8 w-8 flex-shrink-0 place-items-center rounded-full text-[#805487] transition-colors duration-200 hover:bg-[#f3e8f5]">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5M8 11h6M11 8v6" /></svg>
                                        </button>
                                    </div>

                                    <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                                        <button type="button" @click="resetCrop()" :disabled="crop.busy" class="text-[12px] font-medium text-[#805487] underline underline-offset-2 hover:text-[#402143] sm:text-left">Reset position</button>
                                        <div class="flex flex-col-reverse gap-2.5 sm:flex-row">
                                            <button type="button" @click="closeCrop()" :disabled="crop.busy" class="vb-btn vb-btn--outline">Cancel</button>
                                            <button type="button" @click="saveCrop()" :disabled="crop.busy" :class="crop.busy && 'vb-loading'" class="vb-btn vb-btn--primary" x-text="crop.busy ? 'Saving…' : spec[crop.kind].save">Save photo</button>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </template>

                        <div class="relative h-32 sm:h-40">
                            <div class="absolute inset-0 overflow-hidden rounded-t-xl bg-[#f3e8f5]"
                                @if ($bannerUrl) style="background-image:url('{{ $bannerUrl }}');background-size:cover;background-position:center" @endif>
                                @unless ($bannerUrl)
                                    <span class="absolute inset-0 grid place-items-center text-[12px] text-[#805487]">No banner yet</span>
                                @endunless
                                <div x-show="uploading === 'banner'" x-cloak class="absolute inset-0 grid place-items-center bg-white/70">
                                    <span class="vb-loading text-[13px] font-medium text-[#52245b]">Uploading banner…</span>
                                </div>
                            </div>

                            <div class="absolute bottom-3 right-3 z-10" @click.outside="bannerMenu = false" @keydown.escape="bannerMenu = false">
                                <button type="button" @click="bannerMenu = !bannerMenu" :aria-expanded="bannerMenu" aria-haspopup="menu"
                                    class="vb-btn vb-btn--sm vb-act bg-white text-[#402143] shadow-[0_6px_14px_-8px_rgba(43,23,48,0.6)] hover:bg-[#f5ecf6]">
                                    <svg class="vb-ico h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h3l2-3h6l2 3h3v12H4zM12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></svg>
                                    Edit banner
                                </button>
                                <div x-show="bannerMenu" x-cloak role="menu"
                                    x-transition:enter="transition duration-200 ease-vendo" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                    class="absolute right-0 top-full z-20 mt-1.5 w-44 overflow-hidden rounded-xl border border-[#eee6ef] bg-white py-1 text-[13px] shadow-[0_18px_40px_-16px_rgba(43,23,48,0.5)]">
                                    <button type="button" role="menuitem" @click="$refs.bannerInput.click(); bannerMenu = false" class="vb-menu-link w-full text-left">Upload banner</button>
                                    @if ($bannerUrl)
                                        <form action="{{ route('buyer.account.banner.remove') }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" role="menuitem" class="vb-menu-link w-full text-left !text-[#a32b43] hover:!bg-[#fdf1f3]">Remove banner</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-end gap-4 px-5 pb-5">
                            <div class="relative -mt-10" @click.outside="avatarMenu = false" @keydown.escape="avatarMenu = false">
                                <img src="{{ $avatar }}" alt="" class="h-20 w-20 rounded-full border-4 border-white bg-white object-cover shadow-[0_8px_18px_-10px_rgba(43,23,48,0.6)]">
                                <span x-show="uploading === 'avatar'" x-cloak class="absolute inset-0 grid place-items-center rounded-full bg-white/75"><span class="vb-loading text-[#52245b] [&::before]:mr-0"></span></span>
                                <button type="button" @click="avatarMenu = !avatarMenu" :aria-expanded="avatarMenu" aria-haspopup="menu" aria-label="Change profile picture"
                                    class="vb-act absolute bottom-0 right-0 grid h-7 w-7 place-items-center rounded-full border-2 border-white bg-[#402143] text-white transition-colors duration-200 hover:bg-[#52245b]">
                                    <svg class="vb-ico h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                                </button>
                                <div x-show="avatarMenu" x-cloak role="menu"
                                    x-transition:enter="transition duration-200 ease-vendo" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                    class="absolute left-0 top-full z-20 mt-1.5 w-44 overflow-hidden rounded-xl border border-[#eee6ef] bg-white py-1 text-[13px] shadow-[0_18px_40px_-16px_rgba(43,23,48,0.5)]">
                                    <button type="button" role="menuitem" @click="$refs.avatarInput.click(); avatarMenu = false" class="vb-menu-link w-full text-left">Upload photo</button>
                                    @if ($user->profile_picture)
                                        <form action="{{ route('buyer.account.profile-picture.remove') }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" role="menuitem" class="vb-menu-link w-full text-left !text-[#a32b43] hover:!bg-[#fdf1f3]">Remove photo</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="min-w-0 flex-1 pb-1">
                                <p class="truncate text-[17px] font-semibold text-[#2b1730]">{{ $user->name }}</p>
                                <p class="truncate text-[12px] text-[#7a6a7e]">{{ $user->email }}@if ($memberSince) &middot; Buyer since {{ $memberSince }}@endif</p>
                            </div>

                            <div class="flex flex-wrap gap-2 pb-1">
                                <a href="{{ route('buyer.orders.index') }}" class="vb-btn vb-btn--sm vb-btn--outline">View orders</a>
                                <a href="{{ route('buyer.cart.index') }}" class="vb-btn vb-btn--sm vb-btn--outline">View cart</a>
                            </div>
                        </div>
                    </div>

                    <div class="{{ $card }}">
                        <div class="{{ $cardHead }}">
                            <h2 class="{{ $title }}">Profile</h2>
                            <p class="{{ $sub }}">Sellers and riders use these details to deliver your orders.</p>
                        </div>

                        <form action="{{ route('buyer.account.update') }}" method="POST" class="px-5 pt-5" data-draft-key="buyer-{{ auth()->id() }}-profile"
                            x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true">
                            @csrf
                            @method('PUT')

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="acc-name" class="{{ $label }}">Full name</label>
                                    <input id="acc-name" type="text" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required class="{{ $field }} {{ $invalid('name') }}">
                                    @error('name')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="acc-phone" class="{{ $label }}">Phone <span class="font-normal text-[#9a8a9d]">Optional</span></label>
                                    <input id="acc-phone" type="tel" inputmode="tel" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" autocomplete="tel" placeholder="09171234567" class="{{ $field }} {{ $invalid('phone_number') }}">
                                    @error('phone_number')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="mt-4 max-w-[calc(50%-8px)] max-sm:max-w-none">
                                <label for="acc-email" class="{{ $label }}">Email</label>
                                <input id="acc-email" type="email" value="{{ $user->email }}" disabled class="{{ $field }} !bg-[#f7f3f8] text-[#7a6a7e]" aria-describedby="acc-email-note">
                                <p id="acc-email-note" class="mt-1 text-[12px] text-[#7a6a7e]">Your sign-in email can't be changed.</p>
                            </div>

                            @if ($buyerDetail)
                                <div class="mt-5 border-t border-[#f1e8f2] pt-5">
                                    <h3 class="text-[13px] font-semibold text-[#402143]">Delivery address</h3>
                                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="acc-province" class="{{ $label }}">Province</label>
                                            <input id="acc-province" type="text" name="province" value="{{ old('province', $buyerDetail->province) }}" class="{{ $field }} {{ $invalid('province') }}" required>
                                            @error('province')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="acc-municipality" class="{{ $label }}">City or municipality</label>
                                            <input id="acc-municipality" type="text" name="municipality" value="{{ old('municipality', $buyerDetail->municipality) }}" class="{{ $field }} {{ $invalid('municipality') }}" required>
                                            @error('municipality')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="acc-barangay" class="{{ $label }}">Barangay</label>
                                            <input id="acc-barangay" type="text" name="barangay" value="{{ old('barangay', $buyerDetail->barangay) }}" class="{{ $field }} {{ $invalid('barangay') }}" required>
                                            @error('barangay')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="acc-street" class="{{ $label }}">Street</label>
                                            <input id="acc-street" type="text" name="street" value="{{ old('street', $buyerDetail->street) }}" autocomplete="address-line1" class="{{ $field }} {{ $invalid('street') }}" required>
                                            @error('street')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="acc-house" class="{{ $label }}">House no. <span class="font-normal text-[#9a8a9d]">Optional</span></label>
                                            <input id="acc-house" type="text" name="house_no" value="{{ old('house_no', $buyerDetail->house_no) }}" class="{{ $field }} {{ $invalid('house_no') }}">
                                            @error('house_no')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="acc-zip" class="{{ $label }}">ZIP code <span class="font-normal text-[#9a8a9d]">Optional</span></label>
                                            <input id="acc-zip" type="text" inputmode="numeric" name="zip_code" value="{{ old('zip_code', $buyerDetail->zip_code) }}" autocomplete="postal-code" class="{{ $field }} {{ $invalid('zip_code') }}">
                                            @error('zip_code')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div class="mt-4 rounded-xl border border-[#eee6ef] bg-[#fbf8fc] p-4">
                                        <h4 class="text-[13px] font-semibold text-[#402143]">Map pin</h4>
                                        <p class="mt-0.5 text-[12px] leading-5 text-[#7a6a7e]">Pin your door so riders find you faster. Checkout uses this pin.</p>
                                        <div class="mt-3">
                                            <x-address-map :lat="$buyerDetail->latitude" :lng="$buyerDetail->longitude"
                                                :address="trim(implode(', ', array_filter([$buyerDetail->house_no, $buyerDetail->street, $buyerDetail->barangay, $buyerDetail->municipality, $buyerDetail->province])))"
                                                title="Pin your delivery address"
                                                description="Search your address or move the pin. Save to keep it." />
                                            @error('latitude')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                            @error('longitude')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Stays in view while the form scrolls --}}
                            <div class="sticky bottom-0 -mx-5 mt-5 flex items-center justify-between gap-3 rounded-b-xl border-t border-[#f1e8f2] bg-white/95 px-5 py-3 backdrop-blur">
                                <p class="flex items-center gap-1.5 text-[12px] text-[#7a6a7e]" x-show="dirty" x-cloak>
                                    <span class="h-2 w-2 rounded-full bg-[#e8c874]" aria-hidden="true"></span> Unsaved changes
                                </p>
                                <span x-show="!dirty"></span>
                                <button type="submit" class="vb-btn vb-btn--primary">Save changes</button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- ===================== Password ===================== -->
                <section x-show="tab === 'security'" @if ($tab !== 'security') x-cloak @endif
                    x-transition:enter="{{ $enter }}" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $card }}" aria-labelledby="acc-security-title">
                    <div class="{{ $cardHead }}">
                        <h2 id="acc-security-title" class="{{ $title }}">Change password</h2>
                        <p class="{{ $sub }}">Use a password you don't use anywhere else.</p>
                    </div>

                    <form action="{{ route('buyer.account.password') }}" method="POST" class="max-w-[440px] space-y-4 p-5"
                        x-data="{
                            showCur: false, showNew: false, showCf: false, cur: '', pw: '', cf: '',
                            get long() { return this.pw.length >= 8; },
                            get match() { return this.cf.length > 0 && this.pw === this.cf; },
                            get strength() {
                                let s = 0;
                                if (this.pw.length >= 8) s++;
                                if (/[a-z]/.test(this.pw) && /[A-Z]/.test(this.pw)) s++;
                                if ((/\d/.test(this.pw) && /[^A-Za-z0-9]/.test(this.pw)) || this.pw.length >= 12) s++;
                                return s;
                            },
                            get ok() { return this.cur.length > 0 && this.long && this.match; },
                        }">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="acc-current" class="{{ $label }}">Current password</label>
                            <div class="relative">
                                <input id="acc-current" :type="showCur ? 'text' : 'password'" name="current_password" x-model="cur" autocomplete="current-password" required class="{{ $field }} pr-11 {{ $invalid('current_password') }}">
                                <button type="button" @click="showCur = !showCur" :aria-pressed="showCur" :aria-label="showCur ? 'Hide password' : 'Show password'"
                                    class="absolute right-1 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-[#805487] transition-colors duration-200 hover:bg-[#f3e8f5] hover:text-[#402143]">
                                    <svg x-show="!showCur" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                                    <svg x-show="showCur" x-cloak class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.5 6.6A16.7 16.7 0 0 0 2 12s3.6 7 10 7a9.8 9.8 0 0 0 4.2-.9M9.9 9.9a3 3 0 0 0 4.2 4.2" /></svg>
                                </button>
                            </div>
                            @error('current_password')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="acc-new" class="{{ $label }}">New password</label>
                            <div class="relative">
                                <input id="acc-new" :type="showNew ? 'text' : 'password'" name="password" x-model="pw" autocomplete="new-password" required minlength="8" class="{{ $field }} pr-11 {{ $invalid('password') }}" aria-describedby="acc-new-help">
                                <button type="button" @click="showNew = !showNew" :aria-pressed="showNew" :aria-label="showNew ? 'Hide password' : 'Show password'"
                                    class="absolute right-1 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-[#805487] transition-colors duration-200 hover:bg-[#f3e8f5] hover:text-[#402143]">
                                    <svg x-show="!showNew" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                                    <svg x-show="showNew" x-cloak class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.5 6.6A16.7 16.7 0 0 0 2 12s3.6 7 10 7a9.8 9.8 0 0 0 4.2-.9M9.9 9.9a3 3 0 0 0 4.2 4.2" /></svg>
                                </button>
                            </div>
                            @error('password')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror

                            <div x-show="pw.length" x-cloak class="mt-2">
                                <div class="flex gap-1" aria-hidden="true">
                                    <template x-for="n in 3" :key="n">
                                        <span class="h-1.5 flex-1 rounded-full transition-colors duration-300"
                                            :class="strength >= n ? (strength === 1 ? 'bg-[#e8a3b0]' : strength === 2 ? 'bg-[#e8c874]' : 'bg-[#8fc9a4]') : 'bg-[#eee6ef]'"></span>
                                    </template>
                                </div>
                                <p class="mt-1 text-[12px] text-[#7a6a7e]" x-text="strength <= 1 ? 'Weak. Add capital letters and numbers.' : strength === 2 ? 'Okay. Add a number and a symbol to make it strong.' : 'Strong.'"></p>
                            </div>
                            <p id="acc-new-help" class="mt-1.5 flex items-center gap-1.5 text-[12px]" :class="long ? 'text-[#2e6b46]' : 'text-[#7a6a7e]'">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path x-show="long" d="m5 12.5 4.5 4.5L19 7.5" /><circle x-show="!long" cx="12" cy="12" r="3" /></svg>
                                At least 8 characters
                            </p>
                        </div>
                        <div>
                            <label for="acc-confirm" class="{{ $label }}">Confirm new password</label>
                            <div class="relative">
                                <input id="acc-confirm" :type="showCf ? 'text' : 'password'" name="password_confirmation" x-model="cf" autocomplete="new-password" required class="{{ $field }} pr-11 {{ $invalid('password_confirmation') }}">
                                <button type="button" @click="showCf = !showCf" :aria-pressed="showCf" :aria-label="showCf ? 'Hide password' : 'Show password'"
                                    class="absolute right-1 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-[#805487] transition-colors duration-200 hover:bg-[#f3e8f5] hover:text-[#402143]">
                                    <svg x-show="!showCf" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                                    <svg x-show="showCf" x-cloak class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.5 6.6A16.7 16.7 0 0 0 2 12s3.6 7 10 7a9.8 9.8 0 0 0 4.2-.9M9.9 9.9a3 3 0 0 0 4.2 4.2" /></svg>
                                </button>
                            </div>
                            @error('password_confirmation')<p class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                            <p x-show="cf.length && !match" x-cloak class="mt-1 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}Passwords don't match. Retype the new password.</p>
                        </div>

                        <div class="pt-1">
                            <button type="submit" :disabled="!ok" class="vb-btn vb-btn--primary">Update password</button>
                            <p x-show="!ok" x-cloak class="mt-2 text-[12px] text-[#7a6a7e]">Fill in all three fields to continue.</p>
                        </div>
                    </form>
                </section>

                <!-- ===================== Appearance ===================== -->
                <section x-show="tab === 'preferences'" @if ($tab !== 'preferences') x-cloak @endif
                    x-transition:enter="{{ $enter }}" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $card }}" aria-labelledby="acc-pref-title">
                    <div class="{{ $cardHead }}">
                        <h2 id="acc-pref-title" class="{{ $title }}">Appearance</h2>
                        <p class="{{ $sub }}">Saved to your account. Changes apply right away.</p>
                    </div>

                    <div class="divide-y divide-[#f3ecf4]">
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div class="max-w-[420px]">
                                <p class="text-[13px] font-medium text-[#2b1730]">Text size</p>
                                <p class="text-[12px] leading-5 text-[#7a6a7e]">Make text larger if it's hard to read.</p>
                            </div>
                            <div class="flex rounded-lg border border-[#e5dce7] bg-white p-0.5" role="group" aria-label="Text size">
                                @foreach (['default' => 'Default', 'lg' => 'Large', 'xl' => 'Extra large'] as $size => $sizeLabel)
                                    <button type="button" @click="setPref('text', '{{ $size }}')" :aria-pressed="prefs.text === '{{ $size }}'"
                                        :class="prefs.text === '{{ $size }}' ? 'bg-[#402143] text-white' : 'text-[#5b4a60] hover:bg-[#f5ecf6]'"
                                        class="h-8 rounded-md px-3 text-[12px] font-medium transition-colors duration-200">{{ $sizeLabel }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="max-w-[420px]">
                                <p id="pref-calm" class="text-[13px] font-medium text-[#2b1730]">Reduce animations</p>
                                <p class="text-[12px] leading-5 text-[#7a6a7e]">Turns off movement and fading. Vendo also follows your device setting.</p>
                            </div>
                            <button type="button" role="switch" :aria-checked="prefs.calm" aria-labelledby="pref-calm" @click="setPref('calm', !prefs.calm)"
                                :class="prefs.calm ? 'bg-[#805487]' : 'bg-[#d9cfdc]'" class="relative h-6 w-11 flex-shrink-0 rounded-full transition-colors duration-300 ease-vendo">
                                <span :class="prefs.calm ? 'translate-x-5' : 'translate-x-0'" class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-300 ease-vendo"></span>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#f1e8f2] px-5 py-4">
                        <p class="text-[12px] text-[#7a6a7e]" x-text="confirmReset ? 'Reset text size, animations and sound to defaults?' : 'Reset text size, animations and sound.'">Reset text size, animations and sound.</p>
                        <div class="flex items-center gap-2">
                            <button type="button" x-show="confirmReset" x-cloak @click="confirmReset = false" class="vb-btn vb-btn--sm vb-btn--outline">Keep settings</button>
                            <button type="button" @click="confirmReset ? resetPrefs() : confirmReset = true"
                                :class="confirmReset ? 'vb-btn--danger' : 'vb-btn--outline'" class="vb-btn vb-btn--sm"
                                x-text="confirmReset ? 'Reset all' : 'Reset to defaults'">Reset to defaults</button>
                        </div>
                    </div>
                </section>

                <!-- ===================== Delivery ===================== -->
                <section x-show="tab === 'delivery'" @if ($tab !== 'delivery') x-cloak @endif
                    x-transition:enter="{{ $enter }}" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $card }}" aria-labelledby="acc-delivery-title">
                    <div class="{{ $cardHead }}">
                        <h2 id="acc-delivery-title" class="{{ $title }}">Delivery</h2>
                        <p class="{{ $sub }}">Checkout preselects your provider when it serves the seller and your address. Otherwise it picks the lowest fee. You can change it on any order.</p>
                    </div>

                    {{-- Saved addresses live on their own page; this card only summarizes them. --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#f1e8f2] px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold text-[#2b1730]">Saved addresses</p>
                            @if ($savedAddresses->isNotEmpty())
                                @php $defaultAddress = $savedAddresses->firstWhere('is_default', true) ?? $savedAddresses->first(); @endphp
                                <p class="mt-0.5 break-words text-[12px] leading-5 text-[#7a6a7e]">
                                    {{ $savedAddresses->count() }} saved. Default: <span class="font-medium text-[#402143]">{{ $defaultAddress->label }}</span> — {{ $defaultAddress->fullLine() }}
                                </p>
                            @else
                                <p class="mt-0.5 text-[12px] leading-5 text-[#7a6a7e]">Save the places you deliver to so checkout can reuse them.</p>
                            @endif
                        </div>
                        <a href="{{ route('buyer.addresses.index') }}" class="vb-btn vb-btn--sm vb-btn--outline">
                            {{ $savedAddresses->isEmpty() ? 'Add an address' : 'Manage addresses' }}
                        </a>
                    </div>

                    @if ($buyerDetail && $deliveryCompanies->isNotEmpty())
                        @php $selectedCompany = (string) old('preferred_delivery_company_id', $buyerDetail->preferred_delivery_company_id ?? ''); @endphp
                        <form action="{{ route('buyer.account.delivery-preference') }}" method="POST" class="p-5">
                            @csrf
                            @method('PUT')
                            <fieldset>
                                <legend class="{{ $label }}">Preferred provider</legend>
                                <div class="mt-1 grid gap-2.5 sm:grid-cols-2">
                                    @php $options = collect([['id' => '', 'name' => 'Lowest fee', 'note' => 'No preference. Checkout picks the cheapest.']])
                                        ->concat($deliveryCompanies->map(fn ($company) => ['id' => (string) $company->id, 'name' => $company->business_name, 'note' => 'Active in '.($buyerDetail->municipality ?: 'your area')])); @endphp
                                    @foreach ($options as $option)
                                        <label class="group relative flex cursor-pointer items-start gap-3 p-3.5">
                                            <input type="radio" name="preferred_delivery_company_id" value="{{ $option['id'] }}" class="peer sr-only" @checked($selectedCompany === $option['id'])>
                                            <span class="pointer-events-none absolute inset-0 rounded-xl border border-[#e5dce7] bg-white transition-all duration-200 ease-vendo group-hover:border-[#cfb2d4] peer-checked:border-[#805487] peer-checked:bg-[#f5ecf6] peer-focus-visible:ring-2 peer-focus-visible:ring-[#805487] peer-focus-visible:ring-offset-2" aria-hidden="true"></span>
                                            <span class="relative mt-0.5 h-4 w-4 flex-shrink-0 rounded-full border-2 border-[#cfc2d2] bg-white transition-all duration-200 peer-checked:border-[#805487] peer-checked:bg-[#805487] peer-checked:shadow-[inset_0_0_0_3px_#fff]" aria-hidden="true"></span>
                                            <span class="relative min-w-0">
                                                <span class="block truncate text-[13px] font-semibold text-[#2b1730]">{{ $option['name'] }}</span>
                                                <span class="block text-[12px] leading-4 text-[#7a6a7e]">{{ $option['note'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('preferred_delivery_company_id')<p class="mt-2 flex gap-1 text-[12px] text-[#a32b43]" role="alert">{!! $errorIcon !!}{{ $message }}</p>@enderror
                            </fieldset>

                            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-[#f1e8f2] pt-4">
                                <p class="text-[12px] text-[#7a6a7e]">
                                    Map pin: <span class="font-medium text-[#402143]">{{ $buyerDetail->latitude && $buyerDetail->longitude ? 'saved' : 'not set' }}</span>.
                                    <button type="button" @click="go('profile')" class="font-medium text-[#805487] underline underline-offset-2 hover:text-[#402143]">{{ $buyerDetail->latitude && $buyerDetail->longitude ? 'Edit pin' : 'Add a pin' }}</button>
                                </p>
                                <button type="submit" class="vb-btn vb-btn--primary">Save preference</button>
                            </div>
                        </form>
                    @else
                        <x-buyer.empty-state compact icon="box" title="No providers reach you yet"
                            text="Providers appear here once one opens a hub in your municipality. You can still shop.">
                            @if ($buyerDetail)
                                <button type="button" @click="go('profile')" class="vb-btn vb-btn--sm vb-btn--outline">Check my address</button>
                            @endif
                        </x-buyer.empty-state>
                    @endif
                </section>

                <!-- ===================== Notifications ===================== -->
                <section x-show="tab === 'notifications'" @if ($tab !== 'notifications') x-cloak @endif
                    x-transition:enter="{{ $enter }}" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $card }}" aria-labelledby="acc-notif-title">
                    <div class="{{ $cardHead }}">
                        <h2 id="acc-notif-title" class="{{ $title }}">Notifications</h2>
                        <p class="{{ $sub }}">Order updates and seller replies show in the bell on every page.</p>
                    </div>
                    <div class="divide-y divide-[#f3ecf4]">
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="max-w-[420px]">
                                <p id="pref-sound" class="text-[13px] font-medium text-[#2b1730]">Notification sound</p>
                                <p class="text-[12px] leading-5 text-[#7a6a7e]">Play a chime when a new notification arrives.</p>
                            </div>
                            <button type="button" role="switch" :aria-checked="prefs.sound" aria-labelledby="pref-sound" @click="setPref('sound', !prefs.sound)"
                                :class="prefs.sound ? 'bg-[#805487]' : 'bg-[#d9cfdc]'" class="relative h-6 w-11 flex-shrink-0 rounded-full transition-colors duration-300 ease-vendo">
                                <span :class="prefs.sound ? 'translate-x-5' : 'translate-x-0'" class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-300 ease-vendo"></span>
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <p class="text-[12px] leading-5 text-[#7a6a7e]">Email alerts and per-topic choices aren't available yet.</p>
                            <a href="{{ route('buyer.notifications.index') }}" class="vb-btn vb-btn--sm vb-btn--outline">View notifications</a>
                        </div>
                    </div>
                </section>

                <!-- ===================== Favorites ===================== -->
                <section x-show="tab === 'saved'" @if ($tab !== 'saved') x-cloak @endif
                    x-transition:enter="{{ $enter }}" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="{{ $card }}" aria-labelledby="acc-saved-title"
                    x-data="{ confirmClear: false, clearing: false, async clearAll() { this.clearing = true; await $store.fav.clear(); this.clearing = false; this.confirmClear = false; } }">
                    <div class="{{ $cardHead }} flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 id="acc-saved-title" class="{{ $title }}">Favorites</h2>
                            <p class="{{ $sub }}"><span x-text="$store.fav.items.length">0</span> of 60 saved. Saving never adds to your cart.</p>
                        </div>
                        <a href="{{ route('buyer.favorites') }}" x-show="$store.fav.items.length" x-cloak class="vb-btn vb-btn--sm vb-btn--primary vb-act">
                            View favorites
                            <svg class="vb-ico vb-ico--arrow h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                        </a>
                    </div>

                    <div x-show="!$store.fav.items.length" x-cloak>
                        <x-buyer.empty-state compact icon="heart" title="No favorites yet" text="Tap the heart on a product to save it.">
                            <a href="{{ route('buyer.products.index') }}" class="vb-btn vb-btn--sm vb-btn--primary">Browse products</a>
                        </x-buyer.empty-state>
                    </div>

                    <div x-show="$store.fav.items.length" x-cloak>
                        <ul class="grid grid-cols-3 gap-3 p-5 sm:grid-cols-6">
                            <template x-for="item in $store.fav.items.slice(0, 6)" :key="item.id">
                                <li>
                                    <a :href="$store.fav.href(item)" class="group block" :title="item.name">
                                        <span class="vb-img-shell block aspect-square overflow-hidden rounded-lg border border-[#eee6ef]">
                                            <img :src="item.image" alt="" loading="lazy" class="vb-img h-full w-full object-cover group-hover:scale-105"
                                                @load="$el.classList.add('is-loaded'); $el.parentElement.classList.add('is-loaded')">
                                        </span>
                                        <span x-text="item.name" class="mt-1 line-clamp-1 block text-[12px] text-[#2b1730] group-hover:text-[#805487]"></span>
                                    </a>
                                </li>
                            </template>
                        </ul>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#f1e8f2] px-5 py-4">
                            <p class="text-[12px] text-[#7a6a7e]" x-text="confirmClear ? 'Remove all favorites?' : 'Remove every favorite at once.'">Remove every favorite at once.</p>
                            <div class="flex items-center gap-2">
                                <button type="button" x-show="confirmClear" x-cloak @click="confirmClear = false" class="vb-btn vb-btn--sm vb-btn--outline">Keep favorites</button>
                                <button type="button" @click="confirmClear ? clearAll() : confirmClear = true" :disabled="clearing" :class="[confirmClear ? 'vb-btn--danger' : 'vb-btn--danger-soft', clearing && 'vb-loading']"
                                    class="vb-btn vb-btn--sm" x-text="confirmClear ? 'Remove all' : 'Clear all'">Clear all</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ===================== Privacy and terms ===================== -->
                <section x-show="tab === 'privacy'" @if ($tab !== 'privacy') x-cloak @endif
                    x-transition:enter="{{ $enter }}" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    aria-label="Privacy and terms">
                    <div class="{{ $card }} flex flex-wrap items-center justify-between gap-3 p-5">
                        <div class="max-w-[520px]">
                            <h2 class="{{ $title }}">Terms and privacy</h2>
                            <p class="{{ $sub }}">The rules that apply to every Vendo account.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('legal.terms') }}" class="vb-btn vb-btn--sm vb-btn--outline">Read terms</a>
                            <a href="{{ route('legal.privacy') }}" class="vb-btn vb-btn--sm vb-btn--outline">Read privacy policy</a>
                        </div>
                    </div>

                    <x-account-policies :policies="$policies" />

                    {{-- Account deletion: the buyer picks a reason and the admin decides. --}}
                    <div class="{{ $card }} mt-4 border-[#f0d3d9] p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="max-w-[520px]">
                                <h2 class="{{ $title }}">Delete account</h2>
                                <p class="{{ $sub }}">Close your Vendo account. The admin team reviews every request before anything changes.</p>
                            </div>
                            <button type="button" @click="delOpen = true" @disabled($pendingDeletion)
                                class="vb-btn vb-btn--sm vb-btn--danger-soft disabled:cursor-not-allowed disabled:opacity-60">
                                Request deletion
                            </button>
                        </div>
                        @if ($pendingDeletion)
                            <p class="mt-3 rounded-lg border border-[#f0d3d9] bg-[#fff7f8] px-3 py-2 text-[12px] leading-5 text-[#8a2b3c]" role="status">
                                Request sent {{ $pendingDeletion->requested_at?->format('M j, Y') }}. Waiting for admin review — you can keep using your account until then.
                            </p>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        {{-- Delete-account dialog, teleported so it dims the whole screen. --}}
        <template x-teleport="body">
            <div x-show="delOpen" x-cloak class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center sm:p-4"
                @keydown.escape.window="delOpen = false" x-effect="document.body.style.overflow = delOpen ? 'hidden' : ''">
                <div x-show="delOpen" @click="delOpen = false"
                    x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity duration-200 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-[#1b0b1e]/50"></div>

                <section x-show="delOpen" role="dialog" aria-modal="true" aria-labelledby="del-account-title"
                    x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                    x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100" x-transition:leave-end="translate-y-6 opacity-0 sm:scale-95"
                    class="relative max-h-[94dvh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:w-auto sm:min-w-[420px] sm:max-w-[560px] sm:rounded-2xl sm:p-6">

                    <h2 id="del-account-title" class="text-[16px] font-semibold leading-6 text-[#2b1730]">Delete your account</h2>
                    <p class="mt-0.5 text-[12px] leading-5 text-[#7a6a7e]">Tell us why. The Vendo admin team reviews your request and decides. You can keep using your account while it waits.</p>

                    <form method="POST" action="{{ route('buyer.account.deletion-request') }}" class="mt-4 space-y-4">
                        @csrf
                        <fieldset>
                            <legend class="text-[12px] font-medium text-[#5b4a60]">Why do you want to delete your account?</legend>
                            <div class="mt-2 space-y-1.5">
                                @foreach ($deletionReasons as $reason)
                                    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-[#eee6ef] px-3 py-2 text-[13px] text-[#3d2a42] transition-colors duration-200 hover:bg-[#faf5fb]">
                                        <input type="radio" name="reasons[]" value="{{ $reason }}" @checked(old('reasons.0') === $reason) required class="mt-0.5 accent-[#805487]">
                                        <span>{{ $reason }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('reasons')<p class="mt-1 text-[12px] text-[#a3283f]">{{ $message }}</p>@enderror
                            @error('reasons.0')<p class="mt-1 text-[12px] text-[#a3283f]">{{ $message }}</p>@enderror
                        </fieldset>

                        <label class="block">
                            <span class="{{ $label }}">Anything else for the admin? <em class="font-normal text-[#7a6a7e]">(optional)</em></span>
                            <textarea name="comments" rows="4" maxlength="500" class="vb-field w-full px-3 py-2" placeholder="Add a comment for the admin team">{{ old('comments') }}</textarea>
                            @error('comments')<p class="mt-1 text-[12px] text-[#a3283f]">{{ $message }}</p>@enderror
                        </label>

                        <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
                            <button type="button" @click="delOpen = false" class="vb-btn vb-btn--outline">Keep my account</button>
                            <button type="submit" class="vb-btn vb-btn--danger">Submit request</button>
                        </div>
                    </form>
                </section>
            </div>
        </template>
    </div>
</x-buyer.layout>
