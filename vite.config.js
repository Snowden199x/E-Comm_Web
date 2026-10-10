import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Shared bootstrap (Tailwind base + Alpine init) — every role's pages load this
                "resources/css/shared/app.css",
                "resources/css/shared/legal.css",
                "resources/css/shared/marketplace-chat.css",
                "resources/css/shared/user-report.css",
                "resources/js/shared/app.js",
                "resources/js/shared/form-drafts.js",
                "resources/js/auth/google-signin.js",

                // Admin
                 "resources/css/admin/layout.css",
                "resources/css/admin/registrations.css",
                "resources/css/admin/seller-compliance.css",
                "resources/css/admin/motion.css",
                "resources/css/admin/complaints.css",
                "resources/js/admin/sidebar.js",
                "resources/js/admin/layout.js",
                "resources/js/admin/dashboard.js",
                "resources/js/admin/reports.js",

                // Buyer (public storefront / landing page)
                "resources/css/buyer/landing.css",
                "resources/js/buyer/landing.js",

                // Buyer app shell (sidebar + top bar) and dashboard
                "resources/css/buyer/layout.css",
                "resources/js/buyer/sidebar.js",

                // Seller
                "resources/css/seller/seller-dashboard.css",
                "resources/css/seller/order-management-orders.css",
                "resources/css/seller/operations.css",
                "resources/css/seller/workspace.css",
                "resources/css/seller/shipping-label.css",
                "resources/css/logistics/workspace.css",
                "resources/js/seller/operations.js",
                "resources/js/seller/order-management-orders/index.js",
                "resources/css/seller/products.css",
                "resources/js/seller/products.js",
                "resources/css/seller/shipments.css",
                "resources/css/seller/vouchers.css",
                "resources/js/seller/vouchers.js",
                "resources/css/seller/products-filters.css",
                "resources/js/seller/products-filters.js",
                "resources/css/seller/orders-enhance.css",
                "resources/css/seller/waybill-modal.css",
                "resources/js/seller/waybill-modal.js",
                "resources/js/seller/shipments.js",
                "resources/css/seller/complaints.css",
                "resources/css/seller/category-requests.css",
                "resources/js/seller/category-requests.js",

                // Logistics
                "resources/js/logistics/workspace.js"
            ],
            refresh: true,
        }),
    ],
});
