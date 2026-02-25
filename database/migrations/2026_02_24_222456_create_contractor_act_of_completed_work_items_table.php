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
        Schema::create('contractor_act_of_completed_work_items', function (Blueprint $table) {
            $table->id();
            
            // Связь с актом (используем короткое имя для внешнего ключа)
            $table->unsignedBigInteger('contractor_act_of_completed_work_id')
                ->comment('ID акту виконаних робіт');
            
            $table->foreign('contractor_act_of_completed_work_id', 'fk_caocwi_act_id')
                ->references('id')
                ->on('contractor_acts_of_completed_works')
                ->onDelete('cascade');
            
            // Порядковый номер позиции
            $table->integer('sequence_number')->comment('№ п/п');
            
            // Описание услуги/работы
            $table->text('service_description')->comment('Опис послуги/роботи');
            
            // Единица измерения
            $table->string('unit')->default('Послуга')->comment('Одиниця виміру (Послуга, год, шт тощо)');
            
            // Количество
            $table->decimal('quantity', 10, 2)->default(1)->comment('Кількість');
            
            // Цена за единицу
            $table->decimal('unit_price', 12, 2)->default(0)->comment('Ціна за одиницю');
            
            // Сумма по позиции
            $table->decimal('amount', 12, 2)->default(0)->comment('Сума по позиції');
            
            $table->integer('sort')->default(0)->comment('Порядок сортування');
            $table->timestamps();
            
            // Индексы (с короткими именами)
            $table->index('contractor_act_of_completed_work_id', 'idx_caocwi_act_id');
            $table->index('sequence_number', 'idx_caocwi_seq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contractor_act_of_completed_work_items');
    }
};
