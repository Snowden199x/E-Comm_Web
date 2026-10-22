{{-- PREVIEW ONLY: stand-in for a page that is in the spec but not built yet.
     Registered from routes/logistics_preview.php. Replace the route's view() with the real page when it exists. --}}
<x-logistics.shell :title="$title" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>{{ $title }}</h1>
                <p>{{ $text }}</p>
            </div>
            <div class="lg-page-head__actions">
                <span class="lg-pill lg-pill--gray">Not built yet</span>
            </div>
        </div>

        <section class="lg-card" aria-labelledby="soonTitle">
            <div class="lg-empty">
                <span class="lg-empty__icon"><x-logistics.icon :name="$icon" :size="28" /></span>
                <h3 id="soonTitle">This page is planned</h3>
                <p>It is described in {{ $spec }} of the backend spec. When it is built it will show:</p>
                <ul style="margin:16px auto 0;max-width:560px;padding-left:20px;list-style:disc;text-align:left;color:var(--lg-ink-2)">
                    @foreach ($shows as $line)
                        <li style="margin-bottom:6px">{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
        </section>
    </div>
</x-logistics.shell>