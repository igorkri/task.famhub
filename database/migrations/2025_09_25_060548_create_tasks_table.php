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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('gid')->nullable()->unique();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete()->comment('Батьківське завдання');
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('title')->comment('Назва');
            $table->text('description')->nullable()->comment('Опис');
            $table->boolean('is_completed')->default(false)->comment('Чи виконано');
            $table->string('status')->default('new')->comment('Статус');
            $table->string('priority')->nullable()->comment('Пріоритет');
            $table->date('deadline')->nullable()->comment('Дедлайн');
            $table->integer('budget')->nullable()->comment('Бюджет (години)');
            $table->integer('spent')->default(0)->comment('Витрачено (години)');
            $table->integer('progress')->default(0)->comment('Прогрес (%)');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
