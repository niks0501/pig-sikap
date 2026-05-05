<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add liquidation_status to track the lifecycle of a liquidation report
     * (draft → submitted → reviewed → approved → returned).
     */
    public function up(): void
    {
        Schema::table('liquidation_reports', function (Blueprint $table) {
            $table->string('liquidation_status')->default('draft')->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('liquidation_reports', function (Blueprint $table) {
            $table->dropColumn('liquidation_status');
        });
    }
};
