<?php

namespace App\Services;

use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductReview;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Read-only product page facts shared by the web page (buyer.products.show) and the buyer
 * mobile API, so both surfaces show the same delivery, specification and rating content.
 */
class ProductPageContent
{
    public const PAYMENT_METHOD = 'Cash on delivery';

    /** "Santa Cruz, Laguna" — where the seller ships from. */
    public function shipsFrom(Product $product): string
    {
        $detail = $product->seller?->sellerDetail;

        return implode(', ', array_filter([$detail?->municipality, $detail?->province]));
    }

    /**
     * Fee the product page shows today. Checkout quotes shipping per order, so the product
     * page only displays the rate already known before a delivery address is chosen.
     */
    public function shippingFee(Product $product): float
    {
        return 0.0;
    }

    public function shippingNote(float $fee): string
    {
        return $fee > 0
            ? 'Added to your total at checkout.'
            : 'Shipping quotes are not available yet, so no shipping fee is charged.';
    }

    /** Specification rows: base facts, then category details, then the seller's extra specs. */
    public function specifications(Product $product): Collection
    {
        $category = $product->category;

        $rows = collect([
            'Category' => collect([$category?->parent?->name, $category?->name])->filter()->implode(' / '),
            'Brand' => $product->brand,
            'Material' => $product->material,
            'Weight' => $product->weight,
            'Country of origin' => $product->country_of_origin,
            'Ships from' => $this->shipsFrom($product),
        ])->filter(fn ($value) => filled($value))
            ->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values();

        $labels = $this->attributeLabels($product);
        foreach ($product->attributeValues as $item) {
            $value = collect($item->value ?? [])->filter(fn ($v) => filled($v))->implode(', ');
            if ($value !== '') {
                $rows->push(['label' => $labels[$item->key] ?? Str::headline($item->key), 'value' => $value]);
            }
        }
        foreach ($product->specifications as $item) {
            if (filled($item->name) && filled($item->value)) {
                $rows->push(['label' => $item->name, 'value' => $item->value]);
            }
        }

        return $rows;
    }

    /** Labels for the selected category's attribute fields (config/product-attributes.php). */
    public function attributeLabels(Product $product): array
    {
        $category = $product->category;
        $schemas = config('product-attributes', []);
        $schemaKey = $category ? Str::slug(str_replace('&', 'and', $category->name)) : null;
        if ($category && ! isset($schemas[$schemaKey])) {
            $schemaKey = $category->parent
                ? Str::slug(str_replace('&', 'and', $category->parent->name))
                : null;
        }

        return collect(data_get($schemas, $schemaKey.'.fields', []))
            ->mapWithKeys(fn (array $field) => [$field['key'] => $field['label']])
            ->all();
    }

    /**
     * Shop card numbers: the seller's published ratings, approved products and when it joined.
     *
     * @return array{ratings: int, average: float|null, products: int, joined: string|null}
     */
    public function shopStats(User $seller): array
    {
        $reviews = ProductReview::query()
            ->where('seller_id', $seller->id)
            ->where('visibility', 'published');

        return [
            'ratings' => (clone $reviews)->count(),
            'average' => (clone $reviews)->avg('rating'),
            'products' => Product::query()
                ->where('seller_id', $seller->id)
                ->where('status', 'approved')
                ->count(),
            'joined' => $seller->created_at?->diffForHumans(),
        ];
    }

    /**
     * Published-review average, total and per-star counts.
     *
     * @return array{average: float, total: int, counts: array<int, int>}
     */
    public function ratings(Product $product): array
    {
        $summary = $product->publishedReviews()
            ->selectRaw('COUNT(*) AS total_reviews, AVG(rating) AS average_rating')
            ->first();
        $total = (int) ($summary->total_reviews ?? 0);

        $counts = $total
            ? $product->publishedReviews()->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating')
            : collect();

        return [
            'average' => $total ? round((float) $summary->average_rating, 1) : 0.0,
            'total' => $total,
            'counts' => collect([5, 4, 3, 2, 1])
                ->mapWithKeys(fn (int $star) => [$star => (int) ($counts[$star] ?? 0)])
                ->all(),
        ];
    }
}
