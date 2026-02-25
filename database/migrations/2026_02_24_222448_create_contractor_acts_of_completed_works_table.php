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
        Schema::create('contractor_acts_of_completed_works', function (Blueprint $table) {
            $table->id();
            
            // Основная информация об акте
            $table->string('number')->comment('Номер акту');
            $table->date('date')->comment('Дата складання акту');
            $table->string('place_of_compilation')->nullable()->comment('Місце складання');
            
            // Связь с подрядчиком
            $table->foreignId('contractor_id')->constrained('contractors')->comment('ID підрядника');
            
            // Информация о договоре
            $table->string('agreement_number')->nullable()->comment('Номер договору');
            $table->date('agreement_date')->nullable()->comment('Дата договору');
            
            // Информация о заказчике (может быть отдельной таблицей, но пока храним в JSON)
            $table->json('customer_data')->nullable()->comment('Дані замовника (назва, директор, ЄДРПОУ, адреса, банк, IBAN, МФО)');
            
            // Финансовые итоги
            $table->decimal('total_amount', 12, 2)->default(0)->comment('Загальна сума');
            $table->decimal('vat_amount', 12, 2)->default(0)->comment('Сума ПДВ');
            $table->decimal('total_with_vat', 12, 2)->default(0)->comment('Загальна сума з ПДВ');
            $table->string('total_amount_in_words')->nullable()->comment('Сума прописом');
            
            // Дополнительные поля
            $table->text('description')->nullable()->comment('Опис / Примітки');
            $table->string('status')->default('draft')->comment('Статус акту (draft, signed, paid, cancelled)');
            
            // Файлы
            $table->json('files')->nullable()->comment('Файли акту (скан, PDF тощо)');
            
            $table->integer('sort')->default(0)->comment('Порядок сортування');
            $table->timestamps();
            
            // Индексы
            $table->index('contractor_id');
            $table->index('date');
            $table->index('number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contractor_acts_of_completed_works');
    }
};
