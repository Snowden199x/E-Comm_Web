# Shared Site Branding

**Status:** Shared browser tab icon and branded error pages implemented; owner verification pending.
**Reviewed:** 16 November 2026

The public Vendo pages, standalone account pages, and Buyer, Seller, Logistics Center, and Admin layouts include the shared transparent PNG site icon from `public/assets/branding/Web_Logo.png` through `resources/views/shared/site-icon.blade.php`. The asset is served from Laravel's public directory; no build step or database change is needed.

## Error pages

Laravel 404 and 500 responses use `resources/views/errors/404.blade.php` and `500.blade.php`, which share `errors/branded.blade.php`. The page uses the Vendo plum background, the white-on-purple `assets/branding/log-in-logo.svg`, the HTTP status code, and links back to Vendo and sign-in. The 500 page does not show exception details and points to the public support email. Styles are inline so the page does not depend on the Vite manifest; the site icon is linked directly. The 404 page is also what `/admin` now returns after the Admin console moved to `ADMIN_PATH`.

## Product media

Seller product submissions may include a video stored on the configured public product-media disk. The Buyer product detail page renders it with native browser controls when `products.video_path` is present. The feature is unavailable if the stored media URL is inaccessible or the browser cannot decode the uploaded format.

## Source evidence

`resources/views/shared/site-icon.blade.php`, `resources/views/errors/`, `resources/views/components/`, and `resources/views/buyer/products/show.blade.php`.
