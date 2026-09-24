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
        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->boolean('is_work_day');
            $table->string('note')->nullable();

            // Up to four arrive/leave pairs per day, stored as "HH:MM".
            foreach (range(1, 4) as $pair) {
                $table->string("arrive_{$pair}", 5)->nullable();
                $table->string("leave_{$pair}", 5)->nullable();
            }

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
    }
};
