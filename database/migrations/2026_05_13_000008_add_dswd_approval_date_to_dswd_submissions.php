<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add dswd_approval_date to separately track when DSWD
     * actually approved (distinct from submitted_at).
     */
    public function up(): void
    {
        Schema::table('dswd_submissions', function (Blueprint $table) {
            $table->timestamp('dswd_approval_date')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('dswd_submissions', function (Blueprint $table) {
            $table->dropColumn('dswd_approval_date');
        });
    }
};
