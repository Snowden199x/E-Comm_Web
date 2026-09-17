import Alpine from 'alpinejs';
import ajax from '@imacrayon/alpine-ajax';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        // "collapsed" is the pinned state — what the burger button controls
        collapsed: localStorage.getItem('vendo_sidebar_collapsed') === 'true',
        // "hovering" is the temporary peek when the cursor is over the rail
        hovering: false,
        mobileOpen: false,

        // The width the sidebar should actually render at right now
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
            localStorage.setItem('vendo_sidebar_collapsed', this.collapsed);
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

Alpine.plugin(ajax);
Alpine.start();