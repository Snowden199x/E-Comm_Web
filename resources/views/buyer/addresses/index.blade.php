@php
    // Inline edit forms reopen the one that failed validation; the add form opens on its own error.
    $editingId = old('address_id') ? (int) old('address_id') : null;
    $addingOpen = $errors->any() && ! $editingId;

    $card = 'rounded-xl border border-[#eee6ef] bg-white';
    $cardHead = 'border-b border-[#f1e8f2] px-5 py-4';
    $title = 'text-[15px] font-semibold text-[#402143]';
    $sub = 'mt-0.5 text-[12px] leading-5 text-[#7a6a7e]';
    $primary = 'inline-flex h-10 items-center justify-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-[0.97]';
    $outline = 'inline-flex h-10 items-center justify-center rounded-md border border-[#805487] px-5 text-[13px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]';
    $ghost = 'inline-flex h-9 items-center rounded-md px-3 text-[12px] font-medium text-[#805487] transition-colors duration-200 hover:bg-[#f5ecf6]';
@endphp

<x-buyer.layout title="Saved addresses — Vendo">
    @if (session('success'))
        <span x-data x-init="$store.banner.show(@js(['type' => 'success', 'title' => session('success')]))"></span>
    @elseif ($errors->any())
        <span x-data x-init="$store.banner.show(@js(['type' => 'error', 'title' => "Couldn't save", 'text' => 'Fix the highlighted fields, then try again.']))"></span>
    @endif

    <div class="mx-auto max-w-[1040px] px-3 pb-10 pt-4 sm:px-4"
        x-data="{ editing: {{ $editingId ?? 'null' }}, adding: @js($addingOpen) }">

        <div class="vb-reveal flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-[20px] font-semibold text-[#2b1730]">Saved addresses</h1>
                <p class="mt-0.5 max-w-[660px] text-[13px] leading-5 text-[#7a6a7e]">
                    Keep the places you deliver to. Checkout lists these addresses, with your default picked first.
                </p>
            </div>
            <a href="{{ route('buyer.account.index', ['tab' => 'delivery']) }}" class="{{ $outline }}">Back to account</a>
        </div>

        <section class="vb-reveal mt-4" style="--i: 1" aria-labelledby="saved-addresses-title">
            <h2 id="saved-addresses-title" class="sr-only">Your saved addresses</h2>

            <div class="{{ $card }}">
                <div class="{{ $cardHead }} flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="{{ $title }}">Your addresses</p>
                        <p class="{{ $sub }}">
                            {{ $addresses->count() }} of {{ $maxAddresses }} used. Changing one here does not change your registered address.
                        </p>
                    </div>
                    <button type="button" @click="adding = true; editing = null" class="{{ $primary }}"
                        x-show="{{ $addresses->count() < $maxAddresses ? 'true' : 'false' }}">
                        Add a saved address
                    </button>
                </div>

                @if ($addresses->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-[#f3e8f5] text-[#805487]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z" /><circle cx="12" cy="10" r="2.6" /></svg>
                        </span>
                        <p class="mt-3 text-[14px] font-semibold text-[#2b1730]">No saved addresses yet</p>
                        <p class="mx-auto mt-1 max-w-[420px] text-[13px] leading-5 text-[#7a6a7e]">
                            Checkout still uses your registered address. Save another place here to reuse it next time.
                        </p>
                        <button type="button" @click="adding = true" class="mt-4 {{ $outline }}">Add a saved address</button>
                    </div>
                @else
                    <ul class="divide-y divide-[#f3ecf4]">
                        @foreach ($addresses as $address)
                            <li class="px-5 py-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="flex flex-wrap items-center gap-2 text-[13px] font-semibold text-[#2b1730]">
                                            {{ $address->label }}
                                            @if ($address->is_default)
                                                <span class="rounded-full bg-[#f5ecf6] px-2 py-0.5 text-[11px] font-semibold text-[#52245b]">Default</span>
                                            @endif
                                        </p>
                                        <p class="mt-0.5 break-words text-[12px] leading-5 text-[#7a6a7e]">{{ $address->fullLine() }}</p>
                                        <p class="mt-0.5 text-[11px] text-[#9a8a9d]">
                                            {{ $address->latitude && $address->longitude ? 'Map pin saved' : 'No map pin yet' }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <button type="button" @click="editing = editing === {{ $address->id }} ? null : {{ $address->id }}; adding = false"
                                            class="{{ $ghost }}" :aria-expanded="editing === {{ $address->id }}">
                                            {{-- Text stays server-rendered; the toggle only shows or hides the form. --}}
                                            <span x-text="editing === {{ $address->id }} ? 'Close' : 'Edit'">Edit</span>
                                        </button>

                                        @unless ($address->is_default)
                                            <form method="POST" action="{{ route('buyer.addresses.default', $address) }}">
                                                @csrf
                                                <button type="submit" class="{{ $ghost }}">Make default</button>
                                            </form>
                                        @endunless

                                        <form method="POST" action="{{ route('buyer.addresses.destroy', $address) }}"
                                            x-data="{ sure: false }"
                                            @submit="if (!sure) { $event.preventDefault(); sure = true; }">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="{{ $ghost }} !text-[#a32b43] hover:!bg-[#fdf1f3]"
                                                x-text="sure ? 'Confirm delete' : 'Delete'">Delete</button>
                                            <button type="button" x-show="sure" x-cloak @click="sure = false" class="{{ $ghost }}">Cancel</button>
                                        </form>
                                    </div>
                                </div>

                                <form x-show="editing === {{ $address->id }}" x-cloak method="POST"
                                    action="{{ route('buyer.addresses.update', $address) }}" class="mt-4 border-t border-[#f1e8f2] pt-4">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="address_id" value="{{ $address->id }}">
                                    <p class="mb-3 text-[12px] font-semibold text-[#402143]">Edit “{{ $address->label }}”</p>
                                    @include('buyer.addresses.partials.fields', ['address' => $address, 'prefix' => 'edit-' . $address->id])
                                    <div class="mt-4 flex flex-wrap justify-end gap-2">
                                        <button type="button" @click="editing = null" class="{{ $outline }}">Cancel</button>
                                        <button type="submit" class="{{ $primary }}">Save address</button>
                                    </div>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($addresses->count() >= $maxAddresses)
                    <p class="border-t border-[#f1e8f2] px-5 py-3 text-[12px] text-[#7a6a7e]">
                        You have reached {{ $maxAddresses }} saved addresses. Delete one to add another.
                    </p>
                @endif
            </div>
        </section>

        <section x-show="adding" x-cloak class="vb-reveal mt-4" aria-labelledby="add-address-title">
            <div class="{{ $card }}">
                <div class="{{ $cardHead }}">
                    <h2 id="add-address-title" class="{{ $title }}">Add a saved address</h2>
                    <p class="{{ $sub }}">It appears at checkout right away. You can still type a different address there for one order.</p>
                </div>
                <form method="POST" action="{{ route('buyer.addresses.store') }}" class="p-5">
                    @csrf
                    @include('buyer.addresses.partials.fields', ['address' => null, 'prefix' => 'new'])
                    <div class="mt-4 flex flex-wrap justify-end gap-2">
                        <button type="button" @click="adding = false" class="{{ $outline }}">Cancel</button>
                        <button type="submit" class="{{ $primary }}">Save address</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-buyer.layout>
