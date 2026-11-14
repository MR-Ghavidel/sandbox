<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            // 0 = Saturday … 6 = Friday (the Persian week starts on Saturday).
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at')->nullable();
            // A class can meet on odd weeks, even weeks, both, or both at different times
            // (two rows sharing a course, one for odd weeks and one for even weeks).
            $table->boolean('meets_odd_weeks')->default(true);
            $table->boolean('meets_even_weeks')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
