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
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->integer('sort')->default(0)->comment('Порядок сортування');
            $table->string('name')->comment('Назва / Ім\'я підрядника');
            $table->string('phone')->nullable()->comment('Телефон');
            $table->string('email')->nullable()->comment('Email');
            $table->string('type')->comment('Тип підрядника (fop/tov)');
            $table->string('full_name')->nullable()->comment('Повне ім\'я');
            $table->string('in_the_person_of')->nullable()->comment('В особі звернення');
            
            $table->boolean('is_active')->default(true)->comment('Чи активний');
            $table->boolean('my_company')->default(false)->comment('Чи моя компанія');
            
            $table->text('description')->nullable()->comment('Опис / Примітки');
            $table->json('dogovor')->nullable()->comment('Договір');
            $table->json('dogovor_files')->nullable()->comment('Договір файли');
            // Реквизиты (JSON): тип fop/tov, ЄДРПОУ/ІПН, директор, адреса, банк, МФО, IBAN, ПДВ.
            // Пример: { "type": "fop", "full_name": "...", "identification_code": "...", "legal_address": "...", "physical_address": "...", "bank_name": "...", "mfo": "...", "iban": "...", "vat_certificate": null, "taxation_note": "..." }
            $table->json('requisites')->nullable()->comment('Реквизити підрядника');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};
