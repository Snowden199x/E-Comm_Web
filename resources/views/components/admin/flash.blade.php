@props(['except' => []])

{{--
    Feedback banner for the result of the last action. Render once per page, under the page heading:

      <x-admin.flash class="mb-5" />
      <x-admin.flash class="mb-5" :except="['admin_note']" />   (fields that show their own error under the input)

    Reads, in this order of priority (only the first match is shown):
      $errors            validation failed   -> red, lists each message
      session('error')   action failed       -> red
      session('warning') done, with a catch  -> amber (use it when the action worked but a side effect failed)
      session('confirmation') approved | rejected | suspended | deactivated | activated | suspension_lifted  -> green
      session('success') action worked       -> green

    Copy rules for whoever sets these messages: say what happened, then the fix, in one or two short
    sentences. "The confirmation email didn't send. Tell the applicant to sign in."
    Success banners close themselves after 6 seconds; errors and warnings stay until dismissed.
--}}
@php
    $confirmations = [
        'approved' => ['Registration approved', 'The applicant can sign in. A confirmation email was sent.'],
        'rejected' => ['Registration rejected', 'The applicant sees your reason at sign-in.'],
        'suspended' => ['Account suspended', 'The user can\'t sign in until the suspension ends or you lift it.'],
        'deactivated' => ['Account deactivated', 'The user can\'t sign in. Activate the account to restore access.'],
        'activated' => ['Account activated', 'The user can sign in again.'],
        'suspension_lifted' => ['Suspension lifted', 'The user can sign in again.'],
    ];

    $banner = null;

    $errorLines = collect($errors->getMessages())->except($except)->flatten()->all();

    if (count($errorLines) > 0) {
        $banner = [
            'tone' => 'danger',
            'icon' => 'alert-circle',
            'title' => count($errorLines) > 1 ? 'Fix these to continue' : 'Fix this to continue',
            'lines' => $errorLines,
        ];
    } elseif (session('error')) {
        $banner = ['tone' => 'danger', 'icon' => 'alert-circle', 'title' => session('error'), 'lines' => []];
    } elseif (session('warning')) {
        // Approved but the email failed: keep the headline that the approval worked, put the fix underneath.
        $confirmedWithWarning = isset($confirmations[session('confirmation')]);
        $banner = [
            'tone' => 'warning',
            'icon' => 'triangle-alert',
            'title' => $confirmedWithWarning ? $confirmations[session('confirmation')][0] : session('warning'),
            'lines' => $confirmedWithWarning ? [session('warning')] : [],
        ];
    } elseif (isset($confirmations[session('confirmation')])) {
        [$confirmTitle, $confirmText] = $confirmations[session('confirmation')];
        $banner = ['tone' => 'success', 'icon' => 'check-circle', 'title' => $confirmTitle, 'lines' => [$confirmText]];
    } elseif (session('success')) {
        $banner = ['tone' => 'success', 'icon' => 'check-circle', 'title' => session('success'), 'lines' => []];
    }

    $tones = [
        'success' => 'border-vd-success-line bg-vd-success-bg text-vd-success-ink',
        'warning' => 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink',
        'danger' => 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink',
    ];
@endphp

@if ($banner)
    <div {{ $attributes->class(['flex items-start gap-3 rounded-xl border p-4 text-sm', $tones[$banner['tone']]]) }}
        x-data="{ open: true }" x-show="open" x-cloak
        @if ($banner['tone'] === 'success') x-init="setTimeout(() => open = false, 6000)" @endif
        role="{{ $banner['tone'] === 'danger' ? 'alert' : 'status' }}">

        <x-admin.icon :name="$banner['icon']" class="mt-0.5 h-[18px] w-[18px] flex-shrink-0" />

        <div class="min-w-0 flex-1">
            <p class="font-semibold [overflow-wrap:anywhere]">{{ $banner['title'] }}</p>

            @if (count($banner['lines']) === 1)
                <p class="mt-0.5 [overflow-wrap:anywhere]">{{ $banner['lines'][0] }}</p>
            @elseif (count($banner['lines']) > 1)
                <ul class="mt-1 list-disc space-y-0.5 pl-4">
                    @foreach ($banner['lines'] as $line)
                        <li class="[overflow-wrap:anywhere]">{{ $line }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <button type="button" @click="open = false" aria-label="Dismiss"
            class="-mr-1 -mt-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg transition-colors duration-200 hover:bg-white/60
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
            <x-admin.icon name="x" class="h-4 w-4" />
        </button>
    </div>
@endif
