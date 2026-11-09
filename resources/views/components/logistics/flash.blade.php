{{--
    Result of the last action, shown as a top banner (resources/js/logistics/banner.js).
    Render once per page:  <x-logistics.flash />

    Reads, in this order (only the first match is shown):
      $errors            validation failed  -> error banner, one sentence per problem
      session('error')   action failed      -> error banner
      session('warning') done, with a catch -> warning banner
      session('success') action worked      -> success banner (closes itself after 6 seconds)

    Copy rule for whoever sets these messages: say what happened, then how to fix it, in one or two short sentences.
--}}
@php
    $errorLines = collect($errors->getMessages())->flatten()->all();
    $flash = null;

    if (count($errorLines) > 0) {
        $flash = ['error', count($errorLines) > 1 ? 'Fix these to continue' : 'Fix this to continue', implode(' ', $errorLines)];
    } elseif (session('error')) {
        $flash = ['error', session('error'), ''];
    } elseif (session('warning')) {
        $flash = ['warning', session('warning'), ''];
    } elseif (session('success')) {
        $flash = ['success', session('success'), ''];
    }
@endphp

@if ($flash)
    <div hidden data-lg-flash data-type="{{ $flash[0] }}" data-title="{{ $flash[1] }}" data-message="{{ $flash[2] }}"></div>
    <noscript><p class="lgb-inline lgb-inline--{{ $flash[0] }}" role="status">{{ trim($flash[1].' '.$flash[2]) }}</p></noscript>
@endif
