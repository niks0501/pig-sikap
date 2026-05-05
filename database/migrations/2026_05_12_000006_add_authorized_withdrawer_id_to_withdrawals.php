<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->foreignId('authorized_withdrawer_id')
                ->nullable()
                ->constrained('users')
                ->after('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropForeign(['authorized_withdrawer_id']);
            $table->dropColumn('authorized_withdrawer_id');
        });
    }
};