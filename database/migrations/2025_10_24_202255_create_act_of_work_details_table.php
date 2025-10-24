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
        Schema::create('act_of_work_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('act_of_work_id')->constrained('act_of_works')->onDelete('cascade')->comment('ID акту ремонту');
            $table->integer('time_id')->nullable()->comment('ID часу');
            $table->string('task_gid')->nullable()->comment('ID завдання');
            $table->string('project_gid')->nullable()->comment('ID проекту');
            $table->string('project')->nullable()->comment('Проект');
            $table->string('task')->nullable()->comment('Завдання');
            $table->text('description')->nullable()->comment('Опис');
            $table->decimal('amount', 10, 2)->default(0)->comment('Сума');
            $table->decimal('hours', 10, 2)->default(0)->comment('Години');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('act_of_work_details');
    }
};
