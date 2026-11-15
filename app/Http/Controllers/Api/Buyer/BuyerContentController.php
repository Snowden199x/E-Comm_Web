<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * The Buyer app's Help (FAQ) and legal pages. The legal copy is the same
 * Markdown the web `LegalPageController` renders from `docs/legal`, parsed into
 * heading / paragraph / bullet blocks the app can lay out itself.
 */
class BuyerContentController extends Controller
{
    /** `page` in the app maps to one file under `docs/legal`. */
    private const LEGAL_PAGES = [
        'terms' => ['title' => 'Terms and Conditions', 'file' => 'terms-and-conditions.md'],
        'privacy' => ['title' => 'Privacy Policy', 'file' => 'privacy-policy.md'],
    ];

    public function help(): JsonResponse
    {
        return response()->json([
            'title' => 'Help and support',
            'intro' => 'Answers to the questions Buyers ask most. If your question is not here, message the shop or send Vendo a support ticket from your order.',
            'contact_email' => config('mail.from.address'),
            'groups' => [
                [
                    'title' => 'Orders and payments',
                    'items' => [
                        [
                            'question' => 'How do I pay for an order?',
                            'answer' => 'Checkout supports cash on delivery. The Rider collects the amount shown on the order when the parcel is handed over, and the app keeps the receipt in the order timeline.',
                        ],
                        [
                            'question' => 'Can I cancel an order?',
                            'answer' => 'You can cancel while the order has not been packed. Once the Seller has prepared it, the cancel button is replaced by the delivery status so the parcel is not lost in transit.',
                        ],
                        [
                            'question' => 'Why is my order split into several orders?',
                            'answer' => 'A checkout may hold items from more than one shop. Each shop ships its own order, so one checkout can produce several orders with their own tracking numbers.',
                        ],
                    ],
                ],
                [
                    'title' => 'Delivery',
                    'items' => [
                        [
                            'question' => 'When will my parcel arrive?',
                            'answer' => 'The order page shows the delivery window the hub assigned. Deliveries run inside the daily window, and a parcel that misses it is rescheduled for the next day instead of being marked lost.',
                        ],
                        [
                            'question' => 'What does the Rider need at the door?',
                            'answer' => 'The Rider takes a photo of the handover and the name of the person who received the parcel. For cash on delivery the exact amount is recorded with that proof.',
                        ],
                        [
                            'question' => 'What if nobody is available to receive the parcel?',
                            'answer' => 'The Rider marks the attempt as failed with a reason. You can then reschedule the delivery from the order page, and the parcel keeps the same Rider and tracking number.',
                        ],
                    ],
                ],
                [
                    'title' => 'Returns and refunds',
                    'items' => [
                        [
                            'question' => 'How do I return an item?',
                            'answer' => 'Open the completed order and request a return before the return window closes. Once the Seller approves it, a Rider collects the parcel from you and hands it back to the Seller.',
                        ],
                        [
                            'question' => 'When is a refund released?',
                            'answer' => 'The refund is recorded when the returned parcel reaches the Seller. The order timeline shows the amount and the change of state.',
                        ],
                    ],
                ],
                [
                    'title' => 'Vouchers and reviews',
                    'items' => [
                        [
                            'question' => 'Where are my vouchers?',
                            'answer' => 'My Vouchers lists every active shop voucher with its minimum spend, use limit and the reason it cannot be used, if any. Up to two vouchers from the same shop can be applied to one order.',
                        ],
                        [
                            'question' => 'When can I review a product?',
                            'answer' => 'After you confirm receipt. You can review each item once, and the Seller may reply to the review.',
                        ],
                    ],
                ],
                [
                    'title' => 'Account',
                    'items' => [
                        [
                            'question' => 'How do I change my password?',
                            'answer' => 'Account → Password asks for the current password and the new one. Forgot Password sends a code to your email address instead.',
                        ],
                        [
                            'question' => 'How do I delete my account?',
                            'answer' => 'Send a support ticket from the app. Vendo keeps the order history the marketplace is required to record and removes the rest of the account data.',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function legal(string $page): JsonResponse
    {
        $definition = self::LEGAL_PAGES[Str::lower($page)] ?? null;
        abort_unless($definition, 404, 'Unknown legal page.');

        $path = base_path('docs/legal/'.$definition['file']);
        abort_unless(File::exists($path), 404, 'That page is not published yet.');

        $blocks = $this->markdownBlocks(File::get($path));
        $heading = collect($blocks)->first(fn ($block) => $block['type'] === 'heading' && $block['level'] === 1);

        return response()->json([
            'title' => $heading['text'] ?? $definition['title'],
            'updated_label' => Carbon::createFromTimestamp(File::lastModified($path))->format('F j, Y'),
            'blocks' => $blocks,
        ]);
    }

    /**
     * Turns the legal Markdown into the block list the app renders:
     * `heading` (level 1-4), `paragraph` and `bullet`. Inline emphasis and links
     * are flattened to their text so the app never has to parse Markdown.
     *
     * @return list<array{type: string, text: string, level: int}>
     */
    private function markdownBlocks(string $markdown): array
    {
        $blocks = [];
        $paragraph = [];

        $flush = function () use (&$paragraph, &$blocks) {
            if ($paragraph !== []) {
                $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $paragraph), 'level' => 0];
                $paragraph = [];
            }
        };

        foreach (preg_split('/\R/', $markdown) as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $trimmed)) {
                $flush();
                continue;
            }

            if (preg_match('/^(#{1,4})\s+(.*)$/', $trimmed, $match)) {
                $flush();
                $blocks[] = ['type' => 'heading', 'text' => $this->plain($match[2]), 'level' => strlen($match[1])];
                continue;
            }

            if (preg_match('/^[-*+]\s+(.*)$/', $trimmed, $match)) {
                $flush();
                $blocks[] = ['type' => 'bullet', 'text' => $this->plain($match[1]), 'level' => 0];
                continue;
            }

            if (preg_match('/^\d+[.)]\s+(.*)$/', $trimmed, $match)) {
                $flush();
                $blocks[] = ['type' => 'bullet', 'text' => $this->plain($match[1]), 'level' => 0];
                continue;
            }

            $paragraph[] = $this->plain($trimmed);
        }

        $flush();

        return $blocks;
    }

    /** Strips the inline Markdown the legal docs use, keeping the words. */
    private function plain(string $text): string
    {
        $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $text);
        $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text);
        $text = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $text);
        $text = preg_replace('/(\*|_)(.*?)\1/', '$2', $text);
        $text = str_replace('`', '', $text);

        return trim($text);
    }
}
