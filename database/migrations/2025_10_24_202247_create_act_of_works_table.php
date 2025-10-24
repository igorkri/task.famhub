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
        Schema::create('act_of_works', function (Blueprint $table) {
            $table->id();
            $table->string('number')->comment('Номер акту');
            $table->string('status')->default('pending')->comment('Статус');
            $table->json('period')->nullable()->comment('Період');
            $table->string('period_type')->nullable()->comment('Період тип');
            $table->string('period_year')->nullable()->comment('Рік періоду');
            $table->string('period_month')->nullable()->comment('Місяць періоду');
            $table->foreignId('user_id')->constrained()->comment('ID користувача');
            $table->date('date')->comment('Дата складання акту');
            $table->text('description')->nullable()->comment('Опис робіт');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('Загальна сума');
            $table->decimal('paid_amount', 10, 2)->default(0)->comment('Сума, вже сплачена');
            $table->string('file_excel')->nullable()->comment('Файл Excel');
            $table->integer('sort')->default(0)->comment('Порядок сортування');
            $table->string('telegram_status')->default('pending')->comment('Telegram status');
            $table->string('type')->default('')->comment('Тип запису(акт, надходження, новий проєкт)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('act_of_works');
    }
};
