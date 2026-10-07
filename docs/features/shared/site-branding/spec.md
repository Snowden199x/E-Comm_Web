# Shared Site Branding

**Status:** Shared browser tab icon implemented; owner verification pending.
**Reviewed:** 4 October 2026

The public Vendo pages, standalone account pages, and Buyer, Seller, Logistics Center, and Admin layouts include the shared transparent PNG site icon from `public/assets/branding/Web_Logo.png` through `resources/views/shared/site-icon.blade.php`. The asset is served from Laravel's public directory; no build step or database change is needed.

## Product media

Seller product submissions may include a video stored on the configured public product-media disk. The Buyer product detail page renders it with native browser controls when `products.video_path` is present. The feature is unavailable if the stored media URL is inaccessible or the browser cannot decode the uploaded format.

## Source evidence

`resources/views/shared/site-icon.blade.php`, `resources/views/components/`, and `resources/views/buyer/products/show.blade.php`.
