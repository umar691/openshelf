<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('entitlement');
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->timestamp('granted_at');
            $table->timestamps();
            $table->unique(['user_id', 'entitlement_type', 'entitlement_id']);
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status', 20)->default('reserved')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique('order_item_id');
            $table->index(['book_id', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('digital_entitlements');
    }
};
