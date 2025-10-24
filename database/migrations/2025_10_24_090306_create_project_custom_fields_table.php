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
        Schema::create('project_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('asana_gid')->comment('Asana custom field GID');
            $table->string('name')->comment('Назва поля');
            $table->string('type')->comment('Тип: text, number, enum, date, multi_enum, people');
            $table->text('description')->nullable()->comment('Опис поля');

            // Для enum полів - можливі варіанти (JSON)
            $table->json('enum_options')->nullable()->comment('Варіанти для enum: [{"gid":"...", "name":"..."}]');

            // Налаштування поля
            $table->boolean('is_required')->default(false)->comment('Обов\'язкове поле');
            $table->integer('precision')->nullable()->comment('Точність для number полів');

            $table->timestamps();

            // Індекси
            $table->index(['project_id', 'asana_gid']);
            $table->unique(['project_id', 'asana_gid'], 'project_custom_field_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_custom_fields');
    }
};
