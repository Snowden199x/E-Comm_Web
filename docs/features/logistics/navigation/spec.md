# Logistics Navigation

**Status:** Sidebar and scoped sections implemented; several modules are placeholders  
**Updated:** 26 September 2026

The protected Logistics pages share a responsive Vendo sidebar. It lists Rider Management, Incoming Parcel Management, Parcel Sorting, Delivery (Delivery assignments and Delivery Monitoring), Reports, Messages, Account Management, and Logout. The structure was informed by the public [Aisley logistics navigation](https://github.com/ZieksQ/aisley/blob/main/src/logistics/src/components/LogisticsSidebarNav.tsx), while Vendo keeps its Laravel/Blade routes and styling.

Rider Management is the existing center-scoped applications and approve/reject screen; there is no duplicate empty section. Incoming Parcel Management, Parcel Sorting, and Delivery assignments filter the existing center-scoped dispatch operations by stage. Delivery assignments keeps scanned `out_for_delivery` parcels visible to their destination center. The original `/logistics/dispatch` route remains available for existing links and center notifications. Delivery Monitoring, Reports, and Messages have explicit placeholder pages; they perform no action or claim live data. All routes use the approved active logistics-center middleware. The shell follows Vendo's Seller workspace proportions and plum sidebar: icon rail on the dashboard, hover or pin to expand on desktop, and a slide-out menu on mobile. Delivery expands to its two child links.
