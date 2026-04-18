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
        Schema::table('audit_trails', function (Blueprint $table): void {
            $table->json('context_json')->nullable()->after('description');
            $table->index(['module', 'action', 'created_at'], 'audit_trails_module_action_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_trails', function (Blueprint $table): void {
            $table->dropIndex('audit_trails_module_action_created_at_idx');
            $table->dropColumn('context_json');
        });
    }
};
