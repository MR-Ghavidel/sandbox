<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url', 2048);
            // Lower-cased host without "www.", used to find duplicates.
            $table->string('host')->unique();
            $table->string('description', 500)->nullable();
            $table->boolean('is_pinned')->default(false);
            // How many times the site was opened from this app.
            $table->unsignedInteger('open_count')->default(0);
            // Visits reported by the browser when the site was imported from its history.
            $table->unsignedInteger('browser_visit_count')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
