<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('gid')->nullable()->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps(); // лучше добавить, чтобы были created_at/updated_at
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('gid')->nullable()->unique();
            $table->string('icon')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->integer('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // сначала удаляем зависимые таблицы
        Schema::dropIfExists('projects');
        Schema::dropIfExists('workspaces');
    }
};

