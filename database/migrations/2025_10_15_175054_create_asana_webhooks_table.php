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
        Schema::create('asana_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('gid')->unique()->comment('Asana Webhook GID');
            $table->string('resource_type')->comment('project, task, workspace, etc.');
            $table->string('resource_gid')->comment('GID ресурсу, до якого прив\'язаний webhook');
            $table->string('resource_name')->nullable()->comment('Назва ресурсу');
            $table->string('target')->comment('URL для webhook');
            $table->boolean('active')->default(true)->comment('Чи активний webhook');
            $table->timestamp('last_event_at')->nullable()->comment('Час останньої події');
            $table->integer('events_count')->default(0)->comment('Кількість оброблених подій');
            $table->timestamps();

            $table->index('resource_gid');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asana_webhooks');
    }
};
