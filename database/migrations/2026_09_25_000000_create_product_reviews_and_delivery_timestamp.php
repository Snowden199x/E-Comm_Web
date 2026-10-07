<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->index();
        });

        DB::table('order_status_events')
            ->where('to_status', 'delivered')
            ->select('order_id')
            ->selectRaw('MIN(created_at) AS delivered_at')
            ->groupBy('order_id')
            ->orderBy('order_id')
            ->chunk(500, function ($events) {
                foreach ($events as $event) {
                    DB::table('orders')->where('id', $event->order_id)->update(['delivered_at' => $event->delivered_at]);
                }
            });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->string('visibility', 20)->default('published');
            $table->unsignedBigInteger('moderated_by')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderation_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['seller_id', 'visibility', 'created_at']);
            $table->index(['product_id', 'visibility']);
        });

        Schema::create('product_review_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_review_id')->unique()->constrained('product_reviews')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('product_review_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('reason', 500);
            $table->string('status', 20)->default('open');
            $table->text('resolution_note')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['product_review_id', 'seller_id']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('product_review_moderation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->unsignedBigInteger('admin_id');
            $table->string('from_visibility', 20);
            $table->string('to_visibility', 20);
            $table->string('reason', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_moderation_events');
        Schema::dropIfExists('product_review_reports');
        Schema::dropIfExists('product_review_replies');
        Schema::dropIfExists('product_reviews');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivered_at']);
            $table->dropColumn('delivered_at');
        });
    }
};
