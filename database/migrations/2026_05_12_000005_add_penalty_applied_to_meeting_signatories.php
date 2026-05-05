<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_signatories', function (Blueprint $table) {
            $table->boolean('penalty_applied')->default(false)->after('attendance_status');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_signatories', function (Blueprint $table) {
            $table->dropColumn('penalty_applied');
        });
    }
};