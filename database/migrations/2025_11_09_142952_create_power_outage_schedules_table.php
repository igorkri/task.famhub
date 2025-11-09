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
        Schema::create('power_outage_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('schedule_date');
            $table->text('description')->nullable();
            $table->json('periods')->nullable(); // Периоды и объемы отключений
            $table->json('schedule_data'); // Полное расписание по очередям и часам
            $table->timestamp('fetched_at');
            $table->string('hash')->unique(); // Хеш для определения изменений
            $table->timestamps();

            $table->index('schedule_date');
            $table->index('fetched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('power_outage_schedules');
    }
};
