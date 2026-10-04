<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('slug', 220)->unique();
            $table->text('description');
            $table->string('cover_image')->nullable();
            $table->json('formats');
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3)->default('PKR');
            $table->string('isbn', 20)->nullable()->unique();
            $table->string('category', 100)->index();
            $table->string('status', 24)->default('draft')->index();
            $table->boolean('digital_available')->default(true);
            $table->boolean('physical_available')->default(false);
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'category', 'published_at']);
            $table->index(['vendor_id', 'status']);
            $table->index('title');
        });

        Schema::create('book_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chapter_number');
            $table->string('title', 180);
            $table->longText('body_markdown');
            $table->boolean('is_preview')->default(false);
            $table->timestamps();
            $table->unique(['book_id', 'chapter_number']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reviewable');
            $table->unsignedTinyInteger('rating');
            $table->string('title', 180)->nullable();
            $table->text('body')->nullable();
            $table->string('status', 20)->default('published');
            $table->timestamps();
            $table->unique(['user_id', 'reviewable_type', 'reviewable_id']);
            $table->index(['reviewable_type', 'reviewable_id', 'status', 'rating']);
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('session_id')->nullable()->unique();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->morphs('purchasable');
            $table->unsignedInteger('quantity')->default(1);
            $table->json('options')->nullable();
            $table->timestamps();
            $table->unique(['cart_id', 'purchasable_type', 'purchasable_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('order_number', 32)->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->string('payment_status', 24)->default('unpaid')->index();
            $table->string('fulfillment_status', 24)->default('unfulfilled');
            $table->char('currency', 3);
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('shipping_minor')->default(0);
            $table->unsignedBigInteger('total_minor');
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->morphs('purchasable');
            $table->string('title', 180);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_minor');
            $table->char('currency', 3);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('books', fn (Blueprint $table) => $table->fullText(['title', 'description'], 'books_search_fulltext'));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('books')) {
            Schema::table('books', fn (Blueprint $table) => $table->dropFullText('books_search_fulltext'));
        }

        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('book_chapters');
        Schema::dropIfExists('books');
    }
};
