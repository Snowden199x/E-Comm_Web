{{-- Type chip. Needs $complaint. One neutral style: the type is a label, not a status, so it carries no colour. Long names wrap. --}}
<span class="inline-flex max-w-full items-center rounded-full border border-vd-line-strong bg-vd-tint px-2.5 py-0.5 text-center text-xs font-medium leading-snug text-vd-ink">{{ $complaint->type ?? 'Other' }}</span>
