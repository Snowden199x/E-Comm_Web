<div x-show="editRateOpen" x-cloak
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="editRateOpen = false">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm relative" @click.stop>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-gray-900">Edit Commission Rate</h3>
            <button type="button" @click="editRateOpen = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('commission.update-rate') }}">
            @csrf
            <label class="text-sm font-medium text-gray-900 mb-1 block">Commission Rate (%)</label>
            <input type="number" name="rate" step="0.01" min="0" max="100" value="{{ $rate }}" required
                class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm mb-4 focus:outline-none focus:ring-2 focus:ring-[#3b1735]">

            <div class="flex gap-3">
                <button type="button" @click="editRateOpen = false" class="flex-1 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">Save</button>
            </div>
        </form>
    </div>
</div>