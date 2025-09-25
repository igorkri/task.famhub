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
        Schema::create('times', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id')->constrained('tasks')->nullOnDelete()->comment('Task ID');
            $table->integer('user_id')->constrained('users')->nullOnDelete()->comment('User ID');
            $table->string('title')->nullable()->comment('Title');
            $table->text('description')->nullable()->comment('Description');
            // коефіцієнт для підрахунку вартості години
            $table->decimal('coefficient', 8, 2)->default(0)->comment('Coefficient');
            // seconds
            $table->integer('duration')->default(0)->comment('Duration in seconds');
            // статус задачі: in_progress, completed, on_hold, cancelled
            $table->string('status')->default('in_progress')->comment('In progress status');
            // статус отчета
            $table->string('report_status')->default('not_submitted')->comment('Not submitted status');
            $table->boolean('is_archived')->default(false)->comment('Is archived');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('times');
    }
};
