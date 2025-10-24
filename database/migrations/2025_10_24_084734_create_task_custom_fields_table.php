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
        Schema::create('task_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_custom_field_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('asana_gid')->comment('Asana custom field GID (для backward compatibility)');
            $table->string('name')->comment('Назва поля (кешована з project_custom_field)');
            $table->string('type')->comment('Тип поля: text, number, enum, date, multi_enum');

            // Значення залежно від типу
            $table->text('text_value')->nullable()->comment('Текстове значення');
            $table->decimal('number_value', 15, 2)->nullable()->comment('Числове значення');
            $table->date('date_value')->nullable()->comment('Дата');

            // Для enum полів
            $table->string('enum_value_gid')->nullable()->comment('Asana enum value GID');
            $table->string('enum_value_name')->nullable()->comment('Назва enum значення');

            // Для multi_enum полів (зберігаємо як JSON)
            $table->json('multi_enum_values')->nullable()->comment('Множинні enum значення');

            $table->timestamps();

            // Індекси
            $table->index(['task_id', 'asana_gid']);
            $table->unique(['task_id', 'asana_gid'], 'task_custom_field_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_custom_fields');
    }
};
