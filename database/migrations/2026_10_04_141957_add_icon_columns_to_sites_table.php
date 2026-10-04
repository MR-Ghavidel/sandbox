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
        Schema::table('sites', function (Blueprint $table) {
            // The site's logo downloaded to the "local" disk; null when none was found.
            $table->string('icon_path')->nullable()->after('description');
            // When the logo was last looked for; null means it has not been looked for yet.
            $table->timestamp('icon_checked_at')->nullable()->after('icon_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['icon_path', 'icon_checked_at']);
        });
    }
};
