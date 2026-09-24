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
        Schema::create('payroll_months', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedBigInteger('salary');
            $table->unsignedSmallInteger('daily_work_minutes');
            $table->unsignedTinyInteger('salary_divisor_days');
            $table->decimal('overtime_multiplier', 4, 2);
            $table->decimal('insurance_rate_percent', 5, 2);
            $table->decimal('tax_rate_percent', 5, 2);
            $table->unsignedBigInteger('tax_exemption');
            $table->unsignedBigInteger('advance');
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_months');
    }
};
