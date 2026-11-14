<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarm_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alarm_id')->constrained()->cascadeOnDelete();
            // The exact moment the reminder became due. Together with alarm_id it makes delivery idempotent.
            $table->dateTime('occurs_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['alarm_id', 'occurs_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarm_deliveries');
    }
};
