<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->string('slug', 220)->unique();
            $table->text('description');
            $table->string('subject', 100)->index();
            $table->string('difficulty', 24)->default('beginner');
            $table->string('status', 24)->default('draft')->index();
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->char('currency', 3)->default('PKR');
            $table->string('cover_image')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'subject', 'published_at']);
            $table->index(['instructor_id', 'status']);
            $table->index('title');
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'position']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title', 180);
            $table->longText('content_markdown')->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->unique(['course_module_id', 'position']);
            $table->index(['is_published', 'is_preview']);
        });

        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 32)->default('private');
            $table->string('path', 1024);
            $table->string('original_name', 255);
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
            $table->index(['lesson_id', 'created_at']);
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->unsignedTinyInteger('passing_score')->default(70);
            $table->unsignedInteger('attempt_limit')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('type', 24)->default('single_choice');
            $table->text('prompt');
            $table->json('options');
            $table->json('correct_answers');
            $table->unsignedInteger('points')->default(1);
            $table->text('explanation')->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'position']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->decimal('score_percent', 5, 2)->unsigned()->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'quiz_id', 'submitted_at']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id']);
            $table->index(['user_id', 'completed_at']);
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('enrolled_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('courses', fn (Blueprint $table) => $table->fullText(['title', 'description'], 'courses_search_fulltext'));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('courses')) {
            Schema::table('courses', fn (Blueprint $table) => $table->dropFullText('courses_search_fulltext'));
        }

        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('lesson_attachments');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
    }
};
