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
        Schema::table('power_outage_schedules', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('power_outage_schedules', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};

