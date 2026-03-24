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
        Schema::create('development_prices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Назва послуги/роботи');
            $table->decimal('price_frontend', 12, 2)->comment('Ціна');
            $table->decimal('avg_hours_frontend', 8, 2)->nullable()->comment('Середня кількість годин');
            $table->decimal('price_backend', 12, 2)->comment('Ціна');
            $table->decimal('avg_hours_backend', 8, 2)->nullable()->comment('Середня кількість годин');
            $table->string('currency', 10)->default('USD')->comment('Валюта');
            $table->text('description')->nullable()->comment('Опис');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('development_prices');
    }
};

