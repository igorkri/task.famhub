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
        Schema::table('contractor_acts_of_completed_works', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('contractor_id')->comment('ID замовника (контрагент з contractors)');
            $table->foreign('customer_id', 'fk_caocw_customer_id')->references('id')->on('contractors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contractor_acts_of_completed_works', function (Blueprint $table) {
            $table->dropForeign('fk_caocw_customer_id');
            $table->dropColumn('customer_id');
        });
    }
};
