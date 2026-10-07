// Buyer sidebar state — used by components/buyer/layout.blade.php
// and buyer/partials/sidebar.blade.php only.
document.addEventListener('alpine:init', () => {
    Alpine.store('buyerSidebar', {
        // "collapsed" is the pinned state — what the burger button controls
        collapsed: localStorage.getItem('vendo_buyer_sidebar_collapsed') === 'true',
        // "hovering" is the temporary peek while the cursor is over the rail
        hovering: false,
        mobileOpen: false,

        // Whether the labels should be visible right now
        get expanded() {
            return !this.collapsed || this.hovering;
        },

        toggle() {
            if (window.innerWidth < 1024) {
                this.mobileOpen = !this.mobileOpen;
                return;
            }
            this.collapsed = !this.collapsed;
            this.hovering = false;
            localStorage.setItem('vendo_buyer_sidebar_collapsed', this.collapsed);
        },

        close() {
            this.mobileOpen = false;
        },

        hoverOn() {
            if (window.innerWidth >= 1024 && window.matchMedia('(hover: hover)').matches) {
                this.hovering = true;
            }
        },

        hoverOff() {
            this.hovering = false;
        },
    });
});