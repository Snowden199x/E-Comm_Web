<div class="grid grid-cols-3 gap-3 mb-3">
    <div>
        <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Province <span class="text-red-500">*</span></label>
        <select name="province" id="province-select" required
            class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
            <option value="" disabled selected>Select Province</option>
        </select>
    </div>
    <div>
        <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Municipality / City <span class="text-red-500">*</span></label>
        <select name="municipality" id="municipality-select" disabled required
            class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
            <option value="" disabled selected>Select Municipality / City</option>
        </select>
    </div>
    <div>
        <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Barangay <span class="text-red-500">*</span></label>
        <select name="barangay" id="barangay-select" disabled required
            class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
            <option value="" disabled selected>Select Barangay</option>
        </select>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const provinceSelect = document.getElementById('province-select');
    const municipalitySelect = document.getElementById('municipality-select');
    const barangaySelect = document.getElementById('barangay-select');

    fetch('https://psgc.gitlab.io/api/provinces/')
        .then(res => res.json())
        .then(provinces => {
            provinces.sort((a, b) => a.name.localeCompare(b.name));
            provinces.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.name;
                opt.textContent = p.name;
                opt.dataset.code = p.code;
                provinceSelect.appendChild(opt);
            });
        });

    provinceSelect.addEventListener('change', () => {
        const code = provinceSelect.options[provinceSelect.selectedIndex].dataset.code;
        municipalitySelect.innerHTML = '<option value="" disabled selected>Select Municipality / City</option>';
        barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
        barangaySelect.disabled = true;
        municipalitySelect.disabled = true;

        fetch(`https://psgc.gitlab.io/api/provinces/${code}/cities-municipalities/`)
            .then(res => res.json())
            .then(cities => {
                cities.sort((a, b) => a.name.localeCompare(b.name));
                cities.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.name;
                    opt.textContent = c.name;
                    opt.dataset.code = c.code;
                    municipalitySelect.appendChild(opt);
                });
                municipalitySelect.disabled = false;
            });
    });

    municipalitySelect.addEventListener('change', () => {
        const code = municipalitySelect.options[municipalitySelect.selectedIndex].dataset.code;
        barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';

        fetch(`https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays/`)
            .then(res => res.json())
            .then(barangays => {
                barangays.sort((a, b) => a.name.localeCompare(b.name));
                barangays.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.name;
                    opt.textContent = b.name;
                    barangaySelect.appendChild(opt);
                });
                barangaySelect.disabled = false;
            });
    });
});
</script>