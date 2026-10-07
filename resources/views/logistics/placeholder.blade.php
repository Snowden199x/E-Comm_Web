<x-logistics.layout :title="$title">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>{{ $title }}</h1>
                <p>This section is not available in the Logistics portal yet.</p>
            </div>
        </div>

        <div class="lg-card">
            <div class="lg-empty">
                <span class="lg-empty__icon"><x-logistics.icon :name="$title === 'Messages' ? 'message' : 'info'" :size="30" /></span>
                <h3>{{ $title }} is coming later</h3>
                <p>No workflow is connected to this section yet, so there is nothing to show. Parcel and rider work continues in the sections on the left.</p>
                <a href="{{ route('logistics.dashboard') }}" class="lg-btn lg-btn--outline" style="margin-top: 12px;">Back to dashboard</a>
            </div>
        </div>
    </div>
</x-logistics.layout>