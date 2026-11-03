@props(['model', 'close' => null, 'title', 'text' => null, 'action', 'method' => 'POST', 'confirm', 'tone' => 'danger', 'ajax' => false])

{{--
    Confirm popup for one action: a title, one line that says what happens, Cancel and the action button.

      <x-admin.confirm-dialog model="confirmAction === 'delete'" close="confirmAction = null" title="Delete this admin?"
          text="They lose access now." :action="route(...)" method="DELETE" confirm="Delete admin" tone="danger" ajax />

    tone    danger | warning | success   (pastel fill with a border, like every Approve / Reject button)
    ajax    true swaps #main-content (x-target.push) instead of a full page load
    The confirm label starts with a verb. The button shows a spinner on submit (button-loading.js).
--}}
@php
    $tones = [
        'danger' => 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink hover:border-vd-danger-ink',
        'warning' => 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink hover:border-vd-warning-ink',
        'success' => 'border-vd-success-line bg-vd-success-bg text-vd-success-ink hover:border-vd-success-ink',
    ][$tone] ?? '';
@endphp

<x-admin.dialog :model="$model" :close="$close" :title="$title" size="sm">
    @if ($text)
        <p class="text-sm text-vd-muted">{{ $text }}</p>
    @endif

    <div class="mt-5 flex items-center justify-end gap-3">
        <button type="button" @click="{{ $close ?? ($model . ' = false') }}"
            class="inline-flex h-10 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-4 text-sm font-medium text-vd-ink transition-colors duration-200 hover:bg-vd-tint
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
            Cancel
        </button>
        <form method="POST" action="{{ $action }}" @if ($ajax) x-target.push="main-content" @endif>
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif
            <button type="submit"
                class="inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40 {{ $tones }}">
                {{ $confirm }}
            </button>
        </form>
    </div>
</x-admin.dialog>
