<x-buyer.layout title="New support request — Vendo">
    @vite(['resources/css/buyer/support-theme.css', 'resources/js/buyer/support.js'])

    <div class="sp-page sp-page--narrow" x-data x-init="
        @if ($errors->any()) $store.banner.show({ type: 'error', title: 'Request not sent', text: 'Fix the highlighted fields, then select Send again.' }); @endif
    ">
        <nav class="sp-crumb" aria-label="Breadcrumb">
            <a href="{{ route('buyer.support.index') }}">Support</a><span>/</span><span>New request</span>
        </nav>

        <form action="{{ route('buyer.support.store') }}" method="POST" enctype="multipart/form-data" class="sp-card sp-form" data-sp-validate novalidate>
            @csrf
            <div>
                <h1 style="margin:0;font-size:24px;font-weight:600;letter-spacing:-.4px">New support request</h1>
                <p style="margin:4px 0 0;color:var(--sp-muted)">Keep it to one problem so replies stay clear.</p>
            </div>

            <div class="sp-grid">
                <div class="sp-field {{ $errors->has('category') ? 'has-error' : '' }}">
                    <label for="category">Topic</label>
                    <select id="category" name="category" required data-sp-select>
                        @foreach (\App\Models\Support\SupportTicket::CATEGORIES as $key => $text)
                            <option value="{{ $key }}" @selected(old('category', 'order') === $key)>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="sp-error">{{ $message }}</p>@enderror
                </div>
                <div class="sp-field {{ $errors->has('order_id') ? 'has-error' : '' }}">
                    <label for="order_id">Order <small>(optional)</small></label>
                    <select id="order_id" name="order_id" data-sp-select>
                        <option value="">Not about one order</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}" @selected((string) old('order_id', request('order_id')) === (string) $order->id)>
                                {{ $order->number }} — {{ $order->created_at?->format('M j, Y') }}
                            </option>
                        @endforeach
                    </select>
                    @error('order_id')<p class="sp-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="sp-field {{ $errors->has('subject') ? 'has-error' : '' }}">
                <label for="subject">Subject</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" required maxlength="140" autocomplete="off"
                    placeholder="Example: Rider could not find my address"
                    data-sp-min="3" data-sp-empty="Subject is empty. Add a short subject, like “Wrong item delivered”."
                    data-sp-short="Subject is too short. Use at least 3 characters.">
                @error('subject')<p class="sp-error">{{ $message }}</p>@enderror
            </div>

            <div class="sp-field {{ $errors->has('message') ? 'has-error' : '' }}">
                <label for="message">What do you need help with?</label>
                <textarea id="message" name="message" rows="6" required maxlength="5000" data-sp-count="msgCount"
                    placeholder="Include the order number, the date and what you already tried."
                    data-sp-min="10" data-sp-empty="Message is empty. Describe the problem so Vendo can help."
                    data-sp-short="Message is too short. Add at least 10 characters, such as what happened and when.">{{ old('message') }}</textarea>
                <div class="sp-hint"><span></span><span id="msgCount" aria-live="off">0 / 5000</span></div>
                @error('message')<p class="sp-error">{{ $message }}</p>@enderror
            </div>

            <div class="sp-field {{ $errors->has('attachments') || $errors->has('attachments.*') ? 'has-error' : '' }}" data-sp-files>
                <span class="sp-label">Attachments <small>(optional)</small></span>
                <label class="sp-drop">
                    <span class="sp-drop__icon">
                        <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 12-8.6 8.6a5 5 0 0 1-7-7l8.6-8.6a3.3 3.3 0 0 1 4.7 4.7l-8.6 8.6a1.7 1.7 0 0 1-2.4-2.4l7.9-7.9" /></svg>
                    </span>
                    <span><strong>Add a photo or PDF</strong><small>Up to 2 files, 5 MB each. JPG, PNG, WebP or PDF.</small></span>
                    <input type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf">
                </label>
                <ul class="sp-files" aria-live="polite"></ul>
                <p class="sp-error" data-sp-files-error @if (! $errors->has('attachments') && ! $errors->has('attachments.*')) hidden @endif>
                    {{ $errors->first('attachments') ?: $errors->first('attachments.*') }}
                </p>
            </div>

            <div class="sp-actions">
                <button type="submit" class="sp-btn sp-btn--primary">
                    <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z" /></svg>
                    Send request
                </button>
                <a href="{{ route('buyer.support.index') }}" class="sp-btn sp-btn--soft">Cancel</a>
            </div>
        </form>
    </div>
</x-buyer.layout>
