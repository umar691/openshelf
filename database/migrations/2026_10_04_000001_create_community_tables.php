<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24)->index();
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('body');
            $table->string('topic', 100)->nullable()->index();
            $table->json('tags')->nullable();
            $table->string('cover_image', 2048)->nullable();
            $table->string('status', 16)->default('published')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'content_id']);
        });

        Schema::create('content_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'content_id']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['content_id', 'created_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['sender_id', 'recipient_id', 'created_at']);
            $table->index(['recipient_id', 'sender_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('content_reactions');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('contents');
    }
};
