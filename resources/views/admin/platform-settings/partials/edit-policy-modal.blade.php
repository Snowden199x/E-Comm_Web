<div x-show="editPolicyId === {{ $policy->id }}" x-cloak
    x-data="{
        content: @js($policy->content ?? ''),
        init() {
            this.$nextTick(() => {
                const quill = new Quill(this.$refs.editor, {
                    theme: 'snow',
                    modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']] },
                });
                quill.root.innerHTML = this.content;
                quill.on('text-change', () => { this.content = quill.root.innerHTML; });
            });
        }
    }"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="editPolicyId = null">
    <div class="bg-white rounded-2xl p-6 w-full max-w-2xl relative" @click.stop>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-gray-900">Edit {{ $policy->name }}</h3>
            <button type="button" @click="editPolicyId = null" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('platform-settings.policies.update', $policy) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="text-sm font-medium text-gray-900 mb-1 block">Version</label>
                <input type="text" name="version" value="{{ $policy->version }}" required
                    class="w-40 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
            </div>

            <label class="text-sm font-medium text-gray-900 mb-1 block">Content</label>
            <div x-ref="editor" style="min-height: 220px;" class="bg-white rounded-lg border border-gray-200 mb-4"></div>
            <input type="hidden" name="content" x-model="content">

            <div class="flex gap-3">
                <button type="button" @click="editPolicyId = null" class="flex-1 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">Save Changes</button>
            </div>
        </form>
    </div>
</div>