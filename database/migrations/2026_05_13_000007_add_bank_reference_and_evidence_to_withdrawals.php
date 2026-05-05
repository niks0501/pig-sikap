<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add bank_reference (transaction/confirmation number) and
     * evidence_file_path (supporting documents like bank slips) to withdrawals.
     */
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('bank_reference')->nullable()->after('bank_account');
            $table->string('evidence_file_path')->nullable()->after('proof_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['bank_reference', 'evidence_file_path']);
        });
    }
};
