<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('content_slug_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('slug');
            $table->timestamps();
            $table->unique(['content_id', 'slug']);
        });

        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('usable');
            $table->string('context')->nullable();
            $table->timestamps();
        });

        Schema::create('sitemap_entries', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('change_frequency')->nullable();
            $table->decimal('priority', 3, 2)->nullable();
            $table->timestamp('last_modified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position');
            $table->string('role')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->foreignId('content_id')->nullable()->constrained('contents')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('publish_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->timestamp('publish_at');
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index('publish_at');
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('title');
            $table->string('url');
            $table->unsignedInteger('order_column')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('page_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('template');
            $table->json('schema')->nullable();
            $table->timestamps();
        });

        Schema::create('cache_entries', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('tags')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(false);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('disk');
            $table->string('path');
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('token')->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_token_id')->nullable()->constrained('api_tokens')->nullOnDelete();
            $table->string('method', 10);
            $table->string('endpoint');
            $table->unsignedInteger('response_code');
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index('endpoint');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('auditable');
            $table->string('event');
            $table->json('properties')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('backups');
        Schema::dropIfExists('themes');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('cache_entries');
        Schema::dropIfExists('page_layouts');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('publish_queues');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('sitemap_entries');
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('content_slug_histories');
        Schema::dropIfExists('content_statuses');
    }
};
