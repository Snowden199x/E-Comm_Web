{{--
    Result banner for Account Management pages (index and show). Render once, under the page heading.
    Turns this section's `confirmation` keys into short messages, then hands over to <x-admin.flash>.
    Field errors show under their own field, so they are left out of the banner.
--}}
@php
    $adminNotices = [
        'created' => 'Admin account created. An invite was sent to their email.',
        'admin-updated' => 'Admin details saved.',
        'suspended' => 'Admin suspended. They can\'t sign in until you reactivate them.',
        'reactivated' => 'Admin reactivated. They can sign in again.',
        'deactivated' => 'Admin deactivated.',
        'reset-link-sent' => 'Password reset link sent to the admin.',
        'profile-updated' => 'Profile saved.',
        'password-updated' => 'Password changed.',
        'admin-deleted' => 'Admin deleted for good.',
        'admin-archived' => 'Admin archived. Restore them from the Archived filter.',
        'admin-restored' => 'Admin restored. They can sign in again.',
    ];

    if (session('confirmation') && isset($adminNotices[session('confirmation')])) {
        session()->now('success', $adminNotices[session('confirmation')]);
        session()->forget('confirmation');
    }
@endphp

<x-admin.flash class="mb-5" :except="['first_name', 'last_name', 'middle_initial', 'phone_number', 'email_username', 'email', 'recovery_email', 'profile_picture', 'current_password', 'password', 'password_confirmation']" />
