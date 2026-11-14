<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // A class session or an assignment.
            $table->morphs('alarmable');
            // How many minutes before the class/deadline the message is sent.
            $table->unsignedInteger('minutes_before');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['alarmable_type', 'alarmable_id', 'minutes_before']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarms');
    }
};
