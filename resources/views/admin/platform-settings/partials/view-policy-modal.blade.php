<div x-show="viewPolicyId === {{ $policy->id }}" x-cloak
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="viewPolicyId = null">
    <div class="bg-white rounded-2xl p-6 w-full max-w-2xl max-h-[85vh] overflow-y-auto relative" @click.stop>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-lg text-gray-900">{{ $policy->name }}</h3>
                <p class="text-xs text-gray-400">Version {{ $policy->version }} · Last updated {{ $policy->updated_at->format('M j, Y') }}</p>
            </div>
            <button type="button" @click="viewPolicyId = null" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <div class="prose prose-sm max-w-none text-gray-800">
            @if ($policy->content)
                {!! $policy->content !!}
            @else
                <p class="text-gray-400 italic">No content yet.</p>
            @endif
        </div>
    </div>
</div>