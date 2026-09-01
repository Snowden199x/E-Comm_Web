<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    @foreach ($cards as $card)
        <div @class([
            'rounded-2xl p-4 flex items-center gap-3 border-2',
            'bg-green-50 border-green-600' => $card['color'] === 'green',
            'bg-orange-50 border-orange-500' => $card['color'] === 'orange',
            'bg-red-50 border-red-700' => $card['color'] === 'red',
            'bg-purple-50 border-[#3b1735]' => $card['color'] === 'purple',
        ])>
            <img src="{{ asset('assets/icons/seller-compliance/' . $card['icon']) }}" alt="" class="w-10 h-10">
            <div>
                <p class="text-xs text-gray-600">{{ $card['label'] }}</p>
                <p class="text-xl font-bold text-black">{{ number_format($card['value']) }}</p>
            </div>
        </div>
    @endforeach
</div>