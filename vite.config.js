import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/css/vendo.css",
                "resources/js/vendo.js",
                'resources/css/seller/seller-dashboard.css',
                'resources/css/seller/order-management-orders.css',
                'resources/js/seller/order-management-orders/index.js',
            ],
            refresh: true,
        }),
    ],
});