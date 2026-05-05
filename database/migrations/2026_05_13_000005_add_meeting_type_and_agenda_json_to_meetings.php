<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add meeting_type and structured agenda_json to meetings table.
     * Supports pig_production, monthly_association, general meeting types
     * with type-specific default agenda fields.
     */
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('meeting_type')->default('pig_production')->after('status');
            $table->json('agenda_json')->nullable()->after('meeting_type');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['meeting_type', 'agenda_json']);
        });
    }
};
