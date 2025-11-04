<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->index(['type', 'status', 'published_at'], 'contents_publication_index');
            $table->index('is_sticky');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropIndex('contents_publication_index');
            $table->dropIndex('contents_is_sticky_index');
        });
    }
};
