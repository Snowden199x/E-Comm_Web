<script>
    // ---------------------------------------------------------------
    // CSRF helper for fetch() calls
    // ---------------------------------------------------------------
    function vendoCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    // ---------------------------------------------------------------
    // Simple global error toast, usable from anywhere via vendoToast(msg)
    // ---------------------------------------------------------------
    function vendoToast(message) {
        window.dispatchEvent(new CustomEvent('vendo-toast', {
            detail: {
                message
            }
        }));
    }

    // ---------------------------------------------------------------
    // Step gate: blocks moving to the next step while any [required]
    // field inside the given step container is empty/unchecked/unset,
    // and surfaces a toast so the person knows what's missing.
    // ---------------------------------------------------------------
    function vendoValidateStep(container) {
        if (!container) return true;

        const fields = container.querySelectorAll('[required]');
        for (const field of fields) {
            if (field.type === 'checkbox' && !field.checked) {
                vendoToast('Please agree to the Terms and Conditions and Privacy Policy.');
                field.focus();
                return false;
            }
            if (field.type === 'file' && field.files.length === 0) {
                const labels = {
                    business_permit: 'your business permit',
                    drivers_license: "your driver's license",
                    or_cr: 'your OR/CR (Official Receipt / Certificate of Registration)',
                    valid_id: 'a valid ID',
                };
                vendoToast('Please upload ' + (labels[field.name] || 'a valid ID') + '.');
                field.focus();
                return false;
            }
            if (field.tagName === 'SELECT' && !field.value) {
                vendoToast('Please fill in all required fields before continuing.');
                field.focus();
                return false;
            }
            if (field.type !== 'checkbox' && field.type !== 'file' && field.tagName !== 'SELECT' && !String(field
                    .value || '').trim()) {
                vendoToast('Please fill in all required fields before continuing.');
                field.focus();
                return false;
            }
        }

        // Password strength + match check (only present on step 1).
        const password = container.querySelector('[name="password"]');
        const confirmPassword = container.querySelector('[name="password_confirmation"]');
        if (password && confirmPassword) {
            const pw = password.value;
            if (pw.length < 8) {
                vendoToast('Password must be at least 8 characters long.');
                password.focus();
                return false;
            }
            if (!/[A-Z]/.test(pw)) {
                vendoToast('Password must contain at least one capital letter.');
                password.focus();
                return false;
            }
            if (!/[0-9]/.test(pw)) {
                vendoToast('Password must contain at least one number.');
                password.focus();
                return false;
            }
            if (!/[^A-Za-z0-9]/.test(pw)) {
                vendoToast('Password must contain at least one special character.');
                password.focus();
                return false;
            }
            if (pw !== confirmPassword.value) {
                vendoToast('Passwords do not match.');
                confirmPassword.focus();
                return false;
            }
        }

        // Email must be verified before leaving the step that contains it.
        const emailField = container.querySelector('[name="email"]');
        if (emailField && !Alpine.store('registration').otpVerified) {
            vendoToast('Please verify your email address before continuing.');
            emailField.focus();
            return false;
        }

        // At least one category must be selected on the seller's
        // Business Information step (the checkboxes themselves are
        // NOT individually required — only one of them has to be).
        const categoryBoxes = container.querySelectorAll('.category-checkbox');
        if (categoryBoxes.length && ![...categoryBoxes].some((cb) => cb.checked)) {
            vendoToast('Please select at least one category.');
            return false;
        }

        return true;
    }
    window.vendoValidateStep = vendoValidateStep;

    // ---------------------------------------------------------------
    // Populate the Review & Submit step by actually reading the form
    // at the moment "Next: Review & Submit" is clicked. (x-text bound
    // to document.querySelector(...) does NOT update automatically —
    // Alpine only re-runs an expression when a *reactive* property it
    // read changes, and a raw DOM read isn't reactive to it. So this
    // runs imperatively instead of relying on Alpine's reactivity.)
    // ---------------------------------------------------------------
    function vendoFieldValue(name) {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el) return '';
        return el.value || '';
    }

    function vendoSelectText(name) {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el || el.selectedIndex < 0) return '';
        const opt = el.options[el.selectedIndex];
        return (opt && !opt.disabled) ? opt.text : '';
    }

    function vendoSetText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value || '—';
    }

    function vendoRefreshRegistrationReview() {
        const first = vendoFieldValue('first_name');
        const middle = vendoFieldValue('middle_initial');
        const last = vendoFieldValue('last_name');
        const fullName = [first, middle ? middle + '.' : '', last].filter(Boolean).join(' ');

        vendoSetText('review-fullname', fullName);
        vendoSetText('review-phone', vendoFieldValue('contact_number'));
        vendoSetText('review-province', vendoFieldValue('province'));
        vendoSetText('review-street', vendoFieldValue('street'));
        vendoSetText('review-sex', vendoFieldValue('sex') ? vendoFieldValue('sex').replace(/^\w/, c => c
            .toUpperCase()) : '');
        vendoSetText('review-birthday', vendoFieldValue('birthday'));
        vendoSetText('review-municipality', vendoFieldValue('municipality'));
        vendoSetText('review-zip', vendoFieldValue('zip_code'));
        vendoSetText('review-email', vendoFieldValue('email'));
        vendoSetText('review-age', vendoFieldValue('age'));
        vendoSetText('review-barangay', vendoFieldValue('barangay'));
        vendoSetText('review-logistics-center', vendoSelectText('logistics_center_id'));

        const validIdLabel = document.getElementById('valid-id-label')?.textContent;
        vendoSetText('review-valid-id', (validIdLabel && validIdLabel !== 'Upload Valid ID here') ? validIdLabel : '');

        // Seller-only fields (no-ops on the buyer form, since these elements won't exist there)
        vendoSetText('review-business-name', vendoFieldValue('business_name'));
        const permitLabel = document.getElementById('business-permit-label')?.textContent;
        vendoSetText('review-business-permit', (permitLabel && permitLabel !== 'Upload business permit here') ?
            permitLabel : '');
        const checkedCategories = Array.from(document.querySelectorAll('.category-checkbox:checked'))
            .map((el) => el.nextElementSibling?.textContent?.trim())
            .filter(Boolean);
        vendoSetText('review-categories', checkedCategories.join(', '));

        // Courier/logistics-only fields (no-ops on the buyer/seller forms)
        vendoSetText('review-vehicle-type', vendoSelectText('vehicle_type'));
        vendoSetText('review-plate-number', vendoFieldValue('plate_number'));
        const licenseLabel = document.getElementById('drivers-license-label')?.textContent;
        vendoSetText('review-drivers-license', (licenseLabel && licenseLabel !== "Upload driver's license here") ?
            licenseLabel : '');
        const orCrLabel = document.getElementById('or-cr-label')?.textContent;
        vendoSetText('review-or-cr', (orCrLabel && orCrLabel !== 'Upload OR/CR here') ? orCrLabel : '');
    }
    window.vendoRefreshRegistrationReview = vendoRefreshRegistrationReview;

    function vendoRestoreSellerDraft(component) {
        const key = 'vendo.seller.registration.draft';
        const form = component.$el.querySelector('form');
        const registration = Alpine.store('registration');
        const address = Alpine.store('address');

        const verifiedEmail = registration.otpVerified ?
            registration.email :
            '';

        const allowedNames = new Set([
            'last_name', 'first_name', 'middle_initial', 'sex',
            'email', 'birthday', 'contact_number', 'street',
            'zip_code', 'business_name', 'id_category', 'id_type',
            'id_type_1', 'id_type_2', 'categories[]',
        ]);

        const addressKeys = [
            'provinceQuery', 'provinceCode',
            'municipalityQuery', 'municipalityCode',
            'barangayQuery',
        ];

        let draft = null;

        try {
            draft = JSON.parse(sessionStorage.getItem(key) || 'null');
        } catch (_) {
            // Registration still works when browser storage is unavailable.
        }

        if (draft && typeof draft === 'object') {
            component.idCategory = draft.idCategory === 'secondary' ?
                'secondary' :
                'primary';

            for (const field of form.elements) {
                if (!allowedNames.has(field.name)) continue;

                const value = draft.fields?.[field.name];

                if (field.type === 'checkbox') {
                    field.checked = Array.isArray(value) &&
                        value.includes(field.value);
                } else if (typeof value === 'string') {
                    field.value = value;
                }
            }

            for (const name of addressKeys) {
                if (typeof draft.address?.[name] === 'string') {
                    address[name] = draft.address[name];
                }
            }

            component.step = [1, 2, 3].includes(draft.step) ?
                draft.step :
                1;
        }

        const emailField = form.querySelector('[name="email"]');

        if (emailField && !emailField.value && verifiedEmail) {
            emailField.value = verifiedEmail;
        }

        registration.email = (emailField?.value || '').trim().toLowerCase();
        registration.otpVerified = Boolean(
            verifiedEmail &&
            registration.email === verifiedEmail
        );

        // An expired verification must be completed again.
        if (!registration.otpVerified) {
            component.step = 1;
        }

        form.querySelector('[name="birthday"]')
            ?.dispatchEvent(new Event('change', {
                bubbles: true
            }));

        // Restore option lists without clearing the saved address.
        const loadOptions = async (path, property) => {
            try {
                const response = await fetch(
                    `https://psgc.gitlab.io/api/${path}/`
                );

                if (!response.ok) throw new Error('Address request failed');

                const list = await response.json();

                address[property] = list.sort(
                    (a, b) => a.name.localeCompare(b.name)
                );
            } catch (_) {
                vendoToast('Could not reload address options. Please try again.');
            }
        };

        if (address.provinceCode) {
            loadOptions(
                `provinces/${encodeURIComponent(address.provinceCode)}/cities-municipalities`,
                'municipalities'
            );
        }

        if (address.municipalityCode) {
            loadOptions(
                `cities-municipalities/${encodeURIComponent(address.municipalityCode)}/barangays`,
                'barangays'
            );
        }

        const save = () => {
            if (component.showSuccessModal) return;

            const fields = {};

            for (const field of form.elements) {
                if (!allowedNames.has(field.name)) continue;

                if (field.type === 'checkbox') {
                    fields[field.name] ??= [];

                    if (field.checked) {
                        fields[field.name].push(field.value);
                    }
                } else {
                    fields[field.name] = field.value;
                }
            }

            try {
                sessionStorage.setItem(key, JSON.stringify({
                    step: component.step,
                    idCategory: component.idCategory,
                    fields,
                    address: Object.fromEntries(
                        addressKeys.map(name => [name, address[name]])
                    ),
                }));
            } catch (_) {
                // Do not prevent submission when storage is unavailable.
            }
        };

        // Save after Alpine has updated its bound values.
        const scheduleSave = () => component.$nextTick(save);

        form.addEventListener('input', scheduleSave);
        form.addEventListener('change', scheduleSave);
        window.addEventListener('otp-verified', scheduleSave);
        window.addEventListener('pagehide', save);

        component.$watch('step', scheduleSave);
        component.$watch('idCategory', scheduleSave);

        for (const name of addressKeys) {
            component.$watch(`$store.address.${name}`, scheduleSave);
        }

        component.$watch('showSuccessModal', success => {
            if (!success) return;

            try {
                sessionStorage.removeItem(key);
            } catch (_) {}
        });

        component.$nextTick(() => {
            vendoRefreshRegistrationReview();
            save();
        });
    }

    document.addEventListener('alpine:init', () => {

        Alpine.store('toast', {
            visible: false,
            message: '',
            timer: null,
        });

        window.addEventListener('vendo-toast', (e) => {
            const toast = Alpine.store('toast');
            toast.message = e.detail.message;
            toast.visible = true;
            clearTimeout(toast.timer);
            toast.timer = setTimeout(() => {
                toast.visible = false;
            }, 5000);
        });

        // -------------------------------------------------------------
        // Global store: tracks whether the entered email has been
        // verified via the 6-digit OTP flow.
        // -------------------------------------------------------------
        @php
            $registrationVerification = session('registration_verification', []);
            $registrationEmail = $registrationVerification['email'] ?? '';

            $registrationVerified = $registrationEmail !== '' && ($registrationVerification['expires_at'] ?? 0) > now()->timestamp && \Illuminate\Support\Facades\Cache::get('otp_verified:' . $registrationEmail, false);
        @endphp

        Alpine.store('registration', {
            otpVerified: @json((bool) $registrationVerified),
            email: @json($registrationVerified ? $registrationEmail : ''),
        });

        // -------------------------------------------------------------
        // PH address type-to-filter dropdowns (Province -> City/
        // Municipality -> Barangay), backed by the public PSGC API
        // (psgc.gitlab.io). The person can type to filter the list, or
        // just click a result. Selecting a province loads its cities/
        // municipalities, and selecting one of those loads its barangays.
        // -------------------------------------------------------------
        const PSGC_BASE = 'https://psgc.gitlab.io/api';

        Alpine.store('address', {
            provinces: [],
            municipalities: [],
            barangays: [],

            provinceQuery: '',
            municipalityQuery: '',
            barangayQuery: '',

            provinceCode: '',
            municipalityCode: '',

            provinceOpen: false,
            municipalityOpen: false,
            barangayOpen: false,

            loadingProvinces: false,
            loadingMunicipalities: false,
            loadingBarangays: false,

            init() {
                if (this.provinces.length || this.loadingProvinces) return;
                this.loadingProvinces = true;
                fetch(`${PSGC_BASE}/provinces/`)
                    .then((r) => r.json())
                    .then((list) => {
                        list.sort((a, b) => a.name.localeCompare(b.name));
                        this.provinces = list;
                    })
                    .catch(() => {
                        this.provinces = [];
                    })
                    .finally(() => {
                        this.loadingProvinces = false;
                    });
            },

            filteredProvinces() {
                const q = this.provinceQuery.trim().toLowerCase();
                const list = q ? this.provinces.filter((p) => p.name.toLowerCase().includes(q)) : this
                    .provinces;
                return list.slice(0, 60);
            },
            filteredMunicipalities() {
                const q = this.municipalityQuery.trim().toLowerCase();
                const list = q ? this.municipalities.filter((m) => m.name.toLowerCase().includes(q)) :
                    this.municipalities;
                return list.slice(0, 60);
            },
            filteredBarangays() {
                const q = this.barangayQuery.trim().toLowerCase();
                const list = q ? this.barangays.filter((b) => b.name.toLowerCase().includes(q)) : this
                    .barangays;
                return list.slice(0, 60);
            },

            selectProvince(item) {
                this.provinceQuery = item.name;
                this.provinceCode = item.code;
                this.provinceOpen = false;

                this.municipalityQuery = '';
                this.municipalityCode = '';
                this.municipalities = [];
                this.barangayQuery = '';
                this.barangays = [];

                this.loadingMunicipalities = true;
                fetch(`${PSGC_BASE}/provinces/${item.code}/cities-municipalities/`)
                    .then((r) => r.json())
                    .then((list) => {
                        list.sort((a, b) => a.name.localeCompare(b.name));
                        this.municipalities = list;
                    })
                    .catch(() => {
                        this.municipalities = [];
                    })
                    .finally(() => {
                        this.loadingMunicipalities = false;
                    });
            },

            selectMunicipality(item) {
                this.municipalityQuery = item.name;
                this.municipalityCode = item.code;
                this.municipalityOpen = false;

                this.barangayQuery = '';
                this.barangays = [];

                this.loadingBarangays = true;
                fetch(`${PSGC_BASE}/cities-municipalities/${item.code}/barangays/`)
                    .then((r) => r.json())
                    .then((list) => {
                        list.sort((a, b) => a.name.localeCompare(b.name));
                        this.barangays = list;
                    })
                    .catch(() => {
                        this.barangays = [];
                    })
                    .finally(() => {
                        this.loadingBarangays = false;
                    });
            },

            selectBarangay(item) {
                this.barangayQuery = item.name;
                this.barangayOpen = false;
            },

            // Called on blur: if what was typed doesn't exactly match a
            // real option, clear it so an invalid free-text value can
            // never be submitted. Also cascades the clear downstream.
            commit(kind) {
                if (kind === 'province') {
                    const match = this.provinces.find((p) => p.name.toLowerCase() === this.provinceQuery
                        .trim().toLowerCase());
                    if (match) {
                        this.provinceQuery = match.name;
                        this.provinceCode = match.code;
                    } else {
                        this.provinceQuery = '';
                        this.provinceCode = '';
                        this.municipalityQuery = '';
                        this.municipalityCode = '';
                        this.municipalities = [];
                        this.barangayQuery = '';
                        this.barangays = [];
                    }
                } else if (kind === 'municipality') {
                    const match = this.municipalities.find((m) => m.name.toLowerCase() === this
                        .municipalityQuery.trim().toLowerCase());
                    if (match) {
                        this.municipalityQuery = match.name;
                        this.municipalityCode = match.code;
                    } else {
                        this.municipalityQuery = '';
                        this.municipalityCode = '';
                        this.barangayQuery = '';
                        this.barangays = [];
                    }
                } else if (kind === 'barangay') {
                    const match = this.barangays.find((b) => b.name.toLowerCase() === this.barangayQuery
                        .trim().toLowerCase());
                    this.barangayQuery = match ? match.name : '';
                }
            },
        });

        // -------------------------------------------------------------
        // OTP modal component (send code / verify code / resend)
        // -------------------------------------------------------------
        Alpine.data('otpInput', () => ({
            digits: ['', '', '', '', '', ''],
            sending: false,
            verifying: false,
            error: '',

            init() {
                window.addEventListener('otp-open', () => {
                    this.digits = ['', '', '', '', '', ''];
                    this.error = '';
                    this.sendCode();
                });
            },

            async sendCode() {
                this.sending = true;
                this.error = '';
                try {
                    const res = await fetch('{{ route('email-otp.send') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': vendoCsrfToken(),
                        },
                        body: JSON.stringify({
                            email: Alpine.store('registration').email
                        }),
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        this.error = data.message || 'Could not send code.';
                        vendoToast(this.error);
                    }
                } catch (e) {
                    this.error = 'Network error. Please try again.';
                    vendoToast(this.error);
                }
                this.sending = false;
            },

            async verifyCode() {
                const code = this.digits.join('');
                if (code.length < 6) {
                    this.error = 'Enter the full 6-digit code.';
                    return;
                }
                this.verifying = true;
                this.error = '';
                try {
                    const res = await fetch('{{ route('email-otp.verify') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': vendoCsrfToken(),
                        },
                        body: JSON.stringify({
                            email: Alpine.store('registration').email,
                            code
                        }),
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        Alpine.store('registration').otpVerified = true;
                        window.dispatchEvent(new CustomEvent('otp-verified'));
                    } else {
                        this.error = data.message || 'Invalid code.';
                        vendoToast(this.error);
                    }
                } catch (e) {
                    this.error = 'Network error. Please try again.';
                    vendoToast(this.error);
                }
                this.verifying = false;
            },

            onInput(index, event) {
                const val = event.target.value.replace(/\D/g, '');
                this.digits[index] = val ? val[0] : '';
                if (val && index < 5) this.focusAt(index + 1);
            },
            onBackspace(index) {
                if (!this.digits[index] && index > 0) this.focusAt(index - 1);
            },
            focusAt(index) {
                if (index >= 0 && index <= 5) document.getElementById('otp-' + index)?.focus();
            },
            handlePaste(event) {
                const text = (event.clipboardData || window.clipboardData).getData('text').replace(
                    /\D/g, '').slice(0, 6);
                text.split('').forEach((char, i) => {
                    this.digits[i] = char;
                });
                this.focusAt(Math.min(text.length, 5));
            },
        }));
    });

    // ---------------------------------------------------------------
    // Age auto-calculation from Birthday field
    // ---------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        const birthday = document.querySelector('[name="birthday"]');
        const age = document.querySelector('[name="age"]');
        if (birthday && age) {
            birthday.addEventListener('change', () => {
                if (!birthday.value) {
                    age.value = '';
                    return;
                }
                const b = new Date(birthday.value);
                const today = new Date();
                let years = today.getFullYear() - b.getFullYear();
                const m = today.getMonth() - b.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < b.getDate())) years--;
                age.value = years >= 0 ? years : '';
            });
        }

        // -------------------------------------------------------------
        // Contact number: digits only, max 11 (local PH mobile format).
        // -------------------------------------------------------------
        document.querySelectorAll('input[name="contact_number"]').forEach((input) => {
            input.setAttribute('inputmode', 'numeric');
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 11);
            });
        });
    });
</script>
