{{-- Province / Municipality / Barangay — type-to-filter dropdowns, backed by Alpine.store('address') --}}
<div class="grid grid-cols-3 gap-3 mb-3" x-data x-init="$store.address.init()">

    {{-- Province --}}
    <div>
        <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Province <span class="text-red-500">*</span></label>
        <div class="relative"
            @click.outside="$store.address.provinceOpen = false">
            <input type="text" name="province" required autocomplete="off"
                x-model="$store.address.provinceQuery"
                @focus="$store.address.provinceOpen = true"
                @input="$store.address.provinceOpen = true"
                @blur="setTimeout(() => $store.address.commit('province'), 250)"        
                @keydown.escape="$store.address.provinceOpen = false"
                placeholder="Type or select province"
                class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
            <button
                type="button"
                tabindex="-1"
                @mousedown.prevent
                @click="$store.address.provinceOpen = !$store.address.provinceOpen"
                class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-gray-400 hover:text-gray-600"
            >
                <svg
                    class="w-4 h-4 transition-transform duration-200"
                    :class="$store.address.provinceOpen ? 'rotate-180' : ''"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>
            <div x-show="$store.address.provinceOpen" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="absolute z-30 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg py-1">
                <template x-if="$store.address.loadingProvinces">
                    <div class="px-3 py-2 text-[0.78rem] text-gray-400">Loading provinces&hellip;</div>
                </template>
                <template x-for="item in $store.address.filteredProvinces()" :key="item.code">
                    <button type="button" @mousedown.prevent="$store.address.selectProvince(item)"
                        class="w-full text-left px-3 py-2 text-[0.82rem] text-gray-700 hover:bg-[#f3edf7] hover:text-[#3b1735] transition-colors duration-150"
                        x-text="item.name"></button>
                </template>
                <template x-if="!$store.address.loadingProvinces && !$store.address.filteredProvinces().length">
                    <div class="px-3 py-2 text-[0.78rem] text-gray-400">No matches found</div>
                </template>
            </div>
        </div>
    </div>

    {{-- Municipality / City --}}
    <div>
        <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Municipality / City <span class="text-red-500">*</span></label>
        <div class="relative"
            @click.outside="$store.address.municipalityOpen = false">
            <input type="text" name="municipality" required autocomplete="off"
                x-model="$store.address.municipalityQuery"
                :disabled="!$store.address.provinceCode"
                @focus="if ($store.address.provinceCode) $store.address.municipalityOpen = true"
                @input="$store.address.municipalityOpen = true"
                @blur="setTimeout(() => $store.address.commit('municipality'), 250)"
                @keydown.escape="$store.address.municipalityOpen = false"
                :placeholder="$store.address.provinceCode ? 'Type or select municipality / city' : 'Select province first'"
                class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200 disabled:bg-gray-50 disabled:cursor-not-allowed">
            <button
                type="button"
                tabindex="-1"
                @mousedown.prevent
                @click="$store.address.provinceOpen = !$store.address.provinceOpen"
                class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-gray-400 hover:text-gray-600"
            >
                <svg
                    class="w-4 h-4 transition-transform duration-200"
                    :class="$store.address.provinceOpen ? 'rotate-180' : ''"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>
            <div x-show="$store.address.municipalityOpen" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="absolute z-30 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg py-1">
                <template x-if="$store.address.loadingMunicipalities">
                    <div class="px-3 py-2 text-[0.78rem] text-gray-400">Loading&hellip;</div>
                </template>
                <template x-for="item in $store.address.filteredMunicipalities()" :key="item.code">
                    <button type="button" @mousedown.prevent="$store.address.selectMunicipality(item)"
                        class="w-full text-left px-3 py-2 text-[0.82rem] text-gray-700 hover:bg-[#f3edf7] hover:text-[#3b1735] transition-colors duration-150"
                        x-text="item.name"></button>
                </template>
                <template x-if="!$store.address.loadingMunicipalities && !$store.address.filteredMunicipalities().length">
                    <div class="px-3 py-2 text-[0.78rem] text-gray-400">No matches found</div>
                </template>
            </div>
        </div>
    </div>

    {{-- Barangay --}}
    <div>
        <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Barangay <span class="text-red-500">*</span></label>
        <div class="relative"
            @click.outside="$store.address.barangayOpen = false">
            <input type="text" name="barangay" required autocomplete="off"
                x-model="$store.address.barangayQuery"
                :disabled="!$store.address.municipalityCode"
                @focus="if ($store.address.municipalityCode) $store.address.barangayOpen = true"
                @input="$store.address.barangayOpen = true"
                @blur="setTimeout(() => $store.address.commit('barangay'), 250)"
                @keydown.escape="$store.address.barangayOpen = false"
                :placeholder="$store.address.municipalityCode ? 'Type or select barangay' : 'Select municipality first'"
                class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200 disabled:bg-gray-50 disabled:cursor-not-allowed">
            <button
                type="button"
                tabindex="-1"
                @mousedown.prevent
                @click="$store.address.provinceOpen = !$store.address.provinceOpen"
                class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-gray-400 hover:text-gray-600"
            >
                <svg
                    class="w-4 h-4 transition-transform duration-200"
                    :class="$store.address.provinceOpen ? 'rotate-180' : ''"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>
            <div x-show="$store.address.barangayOpen" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="absolute z-30 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg py-1">
                <template x-if="$store.address.loadingBarangays">
                    <div class="px-3 py-2 text-[0.78rem] text-gray-400">Loading&hellip;</div>
                </template>
                <template x-for="item in $store.address.filteredBarangays()" :key="item.code">
                    <button type="button" @mousedown.prevent="$store.address.selectBarangay(item)"
                        class="w-full text-left px-3 py-2 text-[0.82rem] text-gray-700 hover:bg-[#f3edf7] hover:text-[#3b1735] transition-colors duration-150"
                        x-text="item.name"></button>
                </template>
                <template x-if="!$store.address.loadingBarangays && !$store.address.filteredBarangays().length">
                    <div class="px-3 py-2 text-[0.78rem] text-gray-400">No matches found</div>
                </template>
            </div>
        </div>
    </div>

</div>