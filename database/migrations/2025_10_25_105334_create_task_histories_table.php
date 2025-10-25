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
        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('event_type'); // created, updated, deleted, status_changed, assigned, etc.
            $table->string('source')->default('local'); // local, asana_webhook, asana_sync
            $table->string('field_name')->nullable(); // назва поля, яке змінилось
            $table->text('old_value')->nullable(); // старе значення (JSON для складних типів)
            $table->text('new_value')->nullable(); // нове значення (JSON для складних типів)
            $table->json('changes')->nullable(); // повний список змін для batch updates
            $table->json('metadata')->nullable(); // додаткові дані (asana_gid, IP, user_agent, тощо)
            $table->text('description')->nullable(); // опис зміни
            $table->timestamp('event_at')->useCurrent(); // коли сталася подія
            $table->timestamps();

            $table->index(['task_id', 'event_at']);
            $table->index(['task_id', 'event_type']);
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_histories');
    }
};
