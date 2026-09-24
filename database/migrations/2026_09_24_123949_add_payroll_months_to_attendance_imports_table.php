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
        Schema::table('attendance_imports', function (Blueprint $table) {
            // Monthly salary settings found in imported Excel files; saved only for months that have none yet.
            $table->json('payroll_months')->nullable()->after('days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_imports', function (Blueprint $table) {
            $table->dropColumn('payroll_months');
        });
    }
};
