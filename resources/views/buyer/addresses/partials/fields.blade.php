{{--
    Address fields shared by the "Add a saved address" form and each inline edit form.
    $address  App\Models\BuyerAddress|null (null for the add form)
    $prefix   unique id prefix so labels match their inputs
--}}
@php
    $value = fn (string $name) => old($name, $address?->{$name});
    $field = 'vb-field h-10 w-full px-3';
    $label = 'mb-1 block text-[12px] font-medium text-[#5b4a60]';
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="{{ $prefix }}-label" class="{{ $label }}">Name this address</label>
        <input id="{{ $prefix }}-label" type="text" name="label" value="{{ $value('label') }}" maxlength="40"
            placeholder="Home, Office, Parents' house" required class="{{ $field }}">
        @error('label')<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $prefix }}-province" class="{{ $label }}">Province</label>
        <input id="{{ $prefix }}-province" type="text" name="province" value="{{ $value('province') }}" required class="{{ $field }}">
        @error('province')<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $prefix }}-municipality" class="{{ $label }}">City or municipality</label>
        <input id="{{ $prefix }}-municipality" type="text" name="municipality" value="{{ $value('municipality') }}" required class="{{ $field }}">
        @error('municipality')<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $prefix }}-barangay" class="{{ $label }}">Barangay</label>
        <input id="{{ $prefix }}-barangay" type="text" name="barangay" value="{{ $value('barangay') }}" required class="{{ $field }}">
        @error('barangay')<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $prefix }}-street" class="{{ $label }}">Street</label>
        <input id="{{ $prefix }}-street" type="text" name="street" value="{{ $value('street') }}" autocomplete="address-line1" required class="{{ $field }}">
        @error('street')<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $prefix }}-house" class="{{ $label }}">House no. <span class="font-normal text-[#9a8a9d]">Optional</span></label>
        <input id="{{ $prefix }}-house" type="text" name="house_no" value="{{ $value('house_no') }}" class="{{ $field }}">
        @error('house_no')<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $prefix }}-zip" class="{{ $label }}">ZIP code <span class="font-normal text-[#9a8a9d]">Optional</span></label>
        <input id="{{ $prefix }}-zip" type="text" inputmode="numeric" name="zip_code" value="{{ $value('zip_code') }}" autocomplete="postal-code" class="{{ $field }}">
        @error('zip_code')<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-4 rounded-xl border border-[#eee6ef] bg-[#fbf8fc] p-4">
    <h4 class="text-[13px] font-semibold text-[#402143]">Map pin <span class="font-normal text-[#7a6a7e]">Optional</span></h4>
    <p class="mt-0.5 text-[12px] leading-5 text-[#7a6a7e]">Pin this address so the rider finds it faster. It is shared only with the rider assigned to the order.</p>
    <div class="mt-3">
        <x-address-map :lat="$address?->latitude" :lng="$address?->longitude"
            :address="$address?->fullLine() ?? ''"
            title="Pin this address"
            description="Search the address or move the pin. The fields above are filled from the map and stay editable." />
        @error('latitude')<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
        @error('longitude')<p class="mt-1 text-[12px] text-[#a32b43]" role="alert">{{ $message }}</p>@enderror
    </div>
</div>
