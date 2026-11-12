@props(['model', 'title' => null, 'titleJs' => null, 'text' => null, 'textJs' => null, 'size' => 'md', 'close' => null])

{{--
    Popup for the Logistics portals. Same look as x-admin.dialog (white panel, 1px border, no shadow, Esc and outside click close it),
    but the title and the line under it can follow Alpine state:

      <x-logistics.dialog model="open" :titleJs="&quot;editing ? 'Edit hub' : 'Create hub'&quot;" text="One line of help." size="lg"> ... </x-logistics.dialog>

    Props
      model    Alpine expression that is true while the popup is open
      close    Alpine statement that closes it (default: "<model> = false")
      title    fixed title; titleJs = Alpine expression for a title that changes
      text     fixed help line; textJs = Alpine expression for a help line that changes
      size     sm | md | lg
    Put the action buttons at the bottom of the slot: Cancel (secondary) on the left, the main action on the right.
--}}
@php
    $width = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg'][$size] ?? 'max-w-md';
    $closeJs = $close ?? ($model.' = false');
    $titleId = 'dlg-'.\Illuminate\Support\Str::random(6);
@endphp

<div x-show="{{ $model }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @keydown.escape.window="{{ $closeJs }}" @click.self="{{ $closeJs }}"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-vd-ink/50 p-4">
    <div x-show="{{ $model }}"
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-2 scale-95 opacity-0" x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0"
        {{ $attributes->class(['w-full rounded-2xl border border-vd-line-strong bg-white [font-family:Inter,ui-sans-serif,system-ui,sans-serif]', $width]) }} @click.stop>
        <div class="flex items-start justify-between gap-4 border-b border-vd-line px-6 py-4">
            <div class="min-w-0">
                <h3 id="{{ $titleId }}" class="font-display text-base font-semibold text-vd-ink" @if ($titleJs) x-text="{{ $titleJs }}" @endif>{{ $title }}</h3>
                @if ($text || $textJs)
                    <p class="pt-0.5 text-[13px] text-vd-muted" @if ($textJs) x-text="{{ $textJs }}" @endif>{{ $text }}</p>
                @endif
            </div>
            <button type="button" @click="{{ $closeJs }}" aria-label="Close"
                class="lg-ico-host -mr-2 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-vd-muted transition-colors duration-200 hover:bg-vd-lavender hover:text-vd-plum
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                <x-logistics.icon name="x" :size="16" />
            </button>
        </div>
        <div class="max-h-[70vh] overflow-y-auto px-6 py-5">{{ $slot }}</div>
    </div>
</div>
