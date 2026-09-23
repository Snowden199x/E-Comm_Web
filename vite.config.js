import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Shared bootstrap (Tailwind base + Alpine init) — every role's pages load this
                "resources/css/shared/app.css",
                "resources/js/shared/app.js",

                // Admin
                "resources/js/admin/sidebar.js",

                // Buyer (public storefront / landing page)
                "resources/css/buyer/landing.css",
                "resources/js/buyer/landing.js",

                // Seller
                "resources/css/seller/seller-dashboard.css",
                "resources/css/seller/order-management-orders.css",
                "resources/js/seller/order-management-orders/index.js",
            ],
            refresh: true,
        }),
    ],
});