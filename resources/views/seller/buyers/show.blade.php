<x-seller.layout :title="$buyer->name.' | Buyer Profile'">
    @vite('resources/css/seller/workspace.css')
    <div class="sw-page sw-buyer-profile-page">
        <a class="sw-link sw-buyer-profile__back" href="{{ route('seller.messages.index') }}">← Back to messages</a>
        <section class="sw-card sw-buyer-profile" aria-labelledby="buyerProfileName">
            <div class="sw-buyer-profile__banner" @if($buyer->buyerDetail?->banner_path) style="background-image: url('{{ \Illuminate\Support\Facades\Storage::disk('public')->url($buyer->buyerDetail->banner_path) }}')" @endif></div>
            <div class="sw-buyer-profile__body">
                @if($buyer->profile_picture)
                    <img class="sw-buyer-profile__avatar" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($buyer->profile_picture) }}" alt="">
                @else
                    <span class="sw-buyer-profile__avatar sw-buyer-profile__avatar--initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($buyer->name, 0, 1)) }}</span>
                @endif
                <div class="sw-buyer-profile__details">
                    <span class="sw-buyer-profile__eyebrow">Buyer profile</span>
                    <h1 id="buyerProfileName">{{ $buyer->name }}</h1>
                    <dl class="sw-buyer-profile__facts">
                        <div><dt>Member since</dt><dd>{{ $buyer->created_at?->format('F Y') ?? '—' }}</dd></div>
                        <div><dt>Orders with your shop</dt><dd>{{ $orderCount }}</dd></div>
                    </dl>
                </div>
            </div>
        </section>
    </div>
</x-seller.layout>
