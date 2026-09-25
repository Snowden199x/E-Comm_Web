<style>
.vendo-notice-overlay{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.5)}
.vendo-notice-dialog{width:100%;max-width:560px;max-height:85vh;overflow-y:auto;border-radius:16px;background:#fff;padding:24px;box-shadow:0 24px 60px #0003;color:#24202a}
.vendo-notice-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
.vendo-notice-title{font-size:18px;font-weight:700;margin:0}.vendo-notice-date{font-size:12px;color:#6b6470;margin:4px 0 0}
.vendo-notice-close{font-size:24px;line-height:1;color:#6b6470;text-decoration:none;padding:4px 8px;border-radius:8px}
.vendo-notice-body,.vendo-notice-policy-body{white-space:pre-line;overflow-wrap:anywhere;font-size:14px;line-height:1.6;color:#514956}
.vendo-notice-body{margin-top:20px}.vendo-notice-policy{margin-top:20px;border:1px solid #e5dce8;border-radius:12px;padding:16px}
.vendo-notice-policy-title{font-weight:600}.vendo-notice-policy-body{margin-top:12px}.vendo-notice-footer{display:flex;justify-content:flex-end;margin-top:24px}
.vendo-notice-button{background:#52245b;color:#fff;border-radius:8px;padding:9px 16px;font-size:14px;font-weight:600;text-decoration:none}
</style>
@if($selectedNotification)
    <div class="vendo-notice-overlay" role="presentation" data-notification-modal>
        <section class="vendo-notice-dialog" role="dialog" aria-modal="true" aria-labelledby="notificationDetailTitle">
            <div class="vendo-notice-heading">
                <div><h2 id="notificationDetailTitle" class="vendo-notice-title">{{ $selectedNotification->title }}</h2><p class="vendo-notice-date">{{ $selectedNotification->created_at->format('M j, Y g:i A') }}</p></div>
                <a href="{{ route($notificationSide.'.notifications.index') }}" class="vendo-notice-close" aria-label="Close notification">×</a>
            </div>
            <p class="vendo-notice-body">{{ $selectedNotification->message }}</p>
            @if($policy)
                <div class="vendo-notice-policy">
                    <h3 class="vendo-notice-policy-title">{{ $policy->name }} · Version {{ $policy->version }}</h3>
                    <div class="vendo-notice-policy-body">{{ strip_tags($policy->content ?? '') }}</div>
                </div>
            @endif
            <div class="vendo-notice-footer"><a href="{{ route($notificationSide.'.notifications.index') }}" class="vendo-notice-button">Close</a></div>
        </section>
    </div>
    <script>
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.querySelector('[data-notification-modal]')) {
            window.location.href = @json(route($notificationSide.'.notifications.index'));
        }
    });
    </script>
@endif
