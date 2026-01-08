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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->default(400)->comment('Тариф за годину');
            $table->string('currency', 10)->default('UAH')->comment('Валюта');
            $table->decimal('rate_coefficient', 5, 2)->default(1.00)->comment('Коефіцієнт до тарифу');
            $table->integer('monthly_hours_goal')->default(160)->comment('Місячна ціль годин');
            $table->decimal('monthly_earnings_goal', 12, 2)->default(64000)->comment('Місячна ціль заробітку');
            $table->integer('weekly_tasks_goal')->default(10)->comment('Тижнева ціль завдань');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'hourly_rate',
                'currency',
                'rate_coefficient',
                'monthly_hours_goal',
                'monthly_earnings_goal',
                'weekly_tasks_goal',
            ]);
        });
    }
};
