<script>
document.addEventListener('alpine:init', () => {
    const draftKey = 'vendo.buyer.registration.draft';
    const savedFields = ['last_name', 'first_name', 'middle_initial', 'sex', 'email',
        'birthday', 'street', 'zip_code', 'contact_number',
        'id_type', 'id_type_1', 'id_type_2'];
    const normalizeEmail = value => String(value || '').trim().toLowerCase();

    Alpine.data('buyerRegistration', () => ({
        step: 1, showPassword: false, showConfirmPassword: false,
        showVerifyModal: false, showSuccessModal: false, submitting: false,
        sending: false, verifying: false, otpSent: false, otpError: '',
        idCategory: 'primary', email: '', emailVerified: false, verifiedEmail: '',
        verifiedUntil: 0, agreeTerms: false, formError: '',
        step1Error: '', step2Error: '', step3Error: '',
        digits: ['', '', '', '', '', ''], draftRestored: false,
        reviewVersion: 1, reviewFiles: '—', addressLoading: false,
        restoring: true, addressVersions: { municipality: 0, barangay: 0 },
        expiryTimer: null, pagehideHandler: null,

        init() {
            this.$nextTick(() => this.restoreDraft());
        },
        destroy() {
            clearTimeout(this.expiryTimer);
            window.removeEventListener('pagehide', this.pagehideHandler);
        },
        field(name) { return this.$refs.form.elements.namedItem(name); },
        async restoreDraft() {
            const proof = JSON.parse(document.getElementById('buyer-registration-config').textContent);
            let draft = null;
            try { draft = JSON.parse(sessionStorage.getItem(draftKey) || 'null'); } catch (_) {}
            if (draft && typeof draft === 'object' && draft.savedAt > Date.now() - 7200000) {
                this.idCategory = draft.idCategory === 'secondary' ? 'secondary' : 'primary';
                this.email = String(draft.fields?.email || '');
                await this.$nextTick();
                for (const name of savedFields) {
                    if (typeof draft.fields?.[name] === 'string') this.field(name).value = draft.fields[name];
                }
                // Upgrade drafts saved before the address fields were combined.
                if (typeof draft.fields?.house_no === 'string' && draft.fields.house_no.trim()) {
                    this.field('street').value = [draft.fields.house_no.trim(), this.field('street').value].filter(Boolean).join(' ');
                }
                this.step = [1, 2, 3].includes(draft.step) ? draft.step : 1;
                this.draftRestored = true;
            } else { draft = null; }
            if (!this.email && proof.email) this.email = proof.email;
            this.verifiedEmail = proof.email || '';
            this.verifiedUntil = proof.expires_at || 0;
            this.refreshVerification();
            this.field('birthday').dispatchEvent(new Event('change', { bubbles: true }));
            this.$watch('email', () => {
                // Any edit requires verification again; restored proof is applied only on load.
                this.emailVerified = false;
                this.saveDraft();
            });
            this.$watch('step', () => { this.refreshReview(); this.saveDraft(); });
            this.$watch('idCategory', () => { this.refreshReview(); this.saveDraft(); });
            this.$refs.form.addEventListener('input', () => this.$nextTick(() => {
                this.refreshReview(); this.saveDraft();
            }));
            this.$refs.form.addEventListener('change', () => this.$nextTick(() => {
                this.refreshReview(); this.saveDraft();
            }));
            this.pagehideHandler = () => this.saveDraft();
            window.addEventListener('pagehide', this.pagehideHandler);
            this.field('province').addEventListener('change', () => this.changeProvince());
            this.field('municipality').addEventListener('change', () => this.changeMunicipality());
            // Inject the saved selections first so a failed address API cannot erase them.
            for (const name of ['province', 'municipality', 'barangay']) {
                const item = draft?.address?.[name];
                if (item?.name) {
                    const option = new Option(item.name, item.name, true, true);
                    option.dataset.code = item.code || '';
                    this.field(name).append(option);
                    this.field(name).disabled = false;
                }
            }
            this.restoring = false;
            this.refreshReview();
            this.addressLoading = true;
            try {
                await this.loadOptions('provinces', 'province', draft?.address?.province?.name);
                const province = this.selectedAddress('province');
                if (province.code) await this.loadOptions(`provinces/${province.code}/cities-municipalities`, 'municipality', draft?.address?.municipality?.name);
                const municipality = this.selectedAddress('municipality');
                if (municipality.code) await this.loadOptions(`cities-municipalities/${municipality.code}/barangays`, 'barangay', draft?.address?.barangay?.name);
            } catch (_) {
                this.formError = 'Address options could not load. Refresh to try again; your saved details are retained.';
            } finally {
                this.addressLoading = false;
                this.refreshReview();
                this.saveDraft();
            }
        },
        refreshVerification() {
            this.emailVerified = Boolean(this.verifiedEmail &&
                normalizeEmail(this.email) === this.verifiedEmail && this.verifiedUntil * 1000 > Date.now());
            clearTimeout(this.expiryTimer);
            if (this.emailVerified) {
                this.expiryTimer = setTimeout(() => {
                    this.emailVerified = false;
                    this.formError = 'Email verification expired. Please verify your email again.';
                }, Math.min(this.verifiedUntil * 1000 - Date.now(), 2147483647));
            }
        },
        selectedAddress(name) {
            const field = this.field(name);
            return { name: field.value, code: field.selectedOptions[0]?.dataset.code || '' };
        },
        saveDraft() {
            if (this.restoring || this.showSuccessModal) return;
            try {
                sessionStorage.setItem(draftKey, JSON.stringify({
                    savedAt: Date.now(), step: this.step, idCategory: this.idCategory,
                    fields: Object.fromEntries(savedFields.map(name => [name, this.field(name).value])),
                    address: Object.fromEntries(['province', 'municipality', 'barangay'].map(name => [name, this.selectedAddress(name)])),
                }));
            } catch (_) {}
        },
        refreshReview() {
            this.reviewVersion++;
            this.reviewFiles = Array.from(this.$refs.form.querySelectorAll('input[type=file]'))
                .filter(field => !field.disabled).flatMap(field => Array.from(field.files).map(file => file.name)).join(', ') || '—';
        },
        resetOptions(name) {
            this.addressVersions[name] = (this.addressVersions[name] || 0) + 1;
            const field = this.field(name);
            field.replaceChildren(new Option('Select ' + name, '', true, true));
            field.options[0].disabled = true;
            field.disabled = true;
        },
        async loadOptions(path, name, selected = '') {
            const version = this.addressVersions[name] = (this.addressVersions[name] || 0) + 1;
            const response = await fetch(`https://psgc.gitlab.io/api/${path}/`);
            if (!response.ok) throw new Error('Address request failed');
            const items = await response.json();
            if (version !== this.addressVersions[name]) return;
            const field = this.field(name);
            field.replaceChildren(new Option('Select ' + name, '', true, !selected));
            field.options[0].disabled = true;
            items.sort((a, b) => a.name.localeCompare(b.name)).forEach(item => {
                const option = new Option(item.name, item.name, false, item.name === selected);
                option.dataset.code = item.code;
                field.append(option);
            });
            field.disabled = false;
        },
        async changeProvince() {
            this.resetOptions('municipality'); this.resetOptions('barangay'); this.saveDraft();
            const { code } = this.selectedAddress('province');
            if (!code) return;
            try { await this.loadOptions(`provinces/${code}/cities-municipalities`, 'municipality'); }
            catch (_) { this.formError = 'Could not load municipalities. Select the province again to retry.'; }
            this.refreshReview();
        },
        async changeMunicipality() {
            this.resetOptions('barangay'); this.saveDraft();
            const { code } = this.selectedAddress('municipality');
            if (!code) return;
            try { await this.loadOptions(`cities-municipalities/${code}/barangays`, 'barangay'); }
            catch (_) { this.formError = 'Could not load barangays. Select the municipality again to retry.'; }
            this.refreshReview();
        },
        async showFieldError(field, message, step) {
            this.step = step; this.formError = message;
            await this.$nextTick();
            const target = field?.getClientRects().length ? field : field?.closest('label');
            target?.scrollIntoView({ block: 'center', behavior: 'smooth' });
            if (field?.getClientRects().length) field.focus();
        },
        validateStep(number) {
            const container = this.$refs['step' + number];
            const invalid = Array.from(container.querySelectorAll('input, select')).find(field =>
                (!field.disabled && field.willValidate && !field.validity.valid) ||
                (field.required && !field.disabled && field.type !== 'checkbox' && field.type !== 'file' && !field.value.trim())
            );
            if (invalid) {
                const name = invalid.name.replaceAll('_', ' ');
                this.showFieldError(invalid, invalid.type === 'file'
                    ? `Please select your ${name}. Files must be selected again after refresh.`
                    : `Please check ${name}: ${invalid.validationMessage || 'This field is required.'}`, number);
                return false;
            }
            if (number === 1) {
                if (!this.emailVerified || normalizeEmail(this.email) !== this.verifiedEmail || this.verifiedUntil * 1000 <= Date.now()) {
                    this.showFieldError(this.field('email'), 'Please verify your email before continuing.', 1); return false;
                }
                const password = this.field('password');
                if (!/[A-Z]/.test(password.value) || !/[a-z]/.test(password.value) || !/[0-9]/.test(password.value) || !/[^A-Za-z0-9]/.test(password.value)) {
                    this.showFieldError(password, 'Use uppercase and lowercase letters, a number, and a symbol in your password.', 1); return false;
                }
                if (password.value !== this.field('password_confirmation').value) {
                    this.showFieldError(this.field('password_confirmation'), 'Passwords do not match.', 1); return false;
                }
            }
            if (number === 2 && ['province', 'municipality', 'barangay'].some(name => !this.field(name).value)) {
                this.showFieldError(this.field('province'), 'Please complete your province, municipality, and barangay.', 2); return false;
            }
            return true;
        },
        goNext(number) {
            this.formError = '';
            if (this.validateStep(number)) { this.refreshReview(); this.step = number + 1; }
        },
        async request(url, payload) {
            const response = await fetch(url, {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'Request failed. Please try again.');
            return data;
        },
        openOtp() {
            if (!this.field('email').checkValidity()) { this.field('email').reportValidity(); return; }
            this.digits = ['', '', '', '', '', '']; this.otpError = ''; this.showVerifyModal = true; this.sendOtp();
        },
        async sendOtp() {
            if (this.sending || this.verifying) return;
            this.sending = true; this.otpError = ''; this.otpSent = false;
            const email = normalizeEmail(this.email);
            try {
                await this.request('/buyer/otp/send', { email });
                if (normalizeEmail(this.email) === email) { this.otpSent = true; this.emailVerified = false; this.verifiedUntil = 0; }
            } catch (error) { this.otpError = error.message || 'Could not send code.'; }
            finally { this.sending = false; }
        },
        async verifyOtp() {
            if (this.sending || this.verifying) return;
            const otp_code = this.digits.join('');
            if (!/^\d{6}$/.test(otp_code)) { this.otpError = 'Enter the full 6-digit code.'; return; }
            this.verifying = true; this.otpError = '';
            const email = normalizeEmail(this.email);
            try {
                const data = await this.request('/buyer/otp/verify', { email, otp_code });
                if (normalizeEmail(this.email) !== email) return;
                this.verifiedEmail = email; this.verifiedUntil = data.expires_at; this.refreshVerification();
                this.showVerifyModal = false; this.saveDraft();
            } catch (error) { this.otpError = error.message || 'Could not verify code.'; }
            finally { this.verifying = false; }
        },
        onInput(index, event) {
            this.digits[index] = event.target.value.replace(/\D/g, '').slice(0, 1);
            if (this.digits[index] && index < 5) this.focusAt(index + 1);
        },
        onBackspace(index) { if (!this.digits[index] && index > 0) this.focusAt(index - 1); },
        focusAt(index) { if (index >= 0 && index < 6) document.getElementById('otp-' + index)?.focus(); },
        handlePaste(event) {
            this.digits = ['', '', '', '', '', ''];
            const text = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
            text.split('').forEach((char, index) => { this.digits[index] = char; });
            this.focusAt(Math.min(text.length, 5));
        },
        async submitForm(form) {
            if (this.submitting) return;
            this.formError = '';
            for (const number of [1, 2, 3]) if (!this.validateStep(number)) return;
            this.submitting = true;
            try {
                const response = await fetch(form.action, { method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: new FormData(form),
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok && data.success) {
                    this.showSuccessModal = true;
                    clearTimeout(this.expiryTimer);
                    try { sessionStorage.removeItem(draftKey); } catch (_) {}
                } else {
                    const name = Object.keys(data.errors || {})[0];
                    const message = data.errors?.[name]?.[0] || data.message || 'Registration failed. Please try again.';
                    const field = Array.from(form.elements).find(field => field.name === name && !field.disabled);
                    const step = field ? [1, 2, 3].find(number => this.$refs['step' + number].contains(field)) : this.step;
                    await this.showFieldError(field, message, step);
                }
            } catch (_) { this.formError = 'Network error. Your progress is saved. Please try again.'; }
            finally { this.submitting = false; }
        },
    }));
});
</script>
