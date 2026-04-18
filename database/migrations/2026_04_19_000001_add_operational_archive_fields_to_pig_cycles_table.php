<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pig_cycles', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('last_reviewed_at');
            $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('archived_by');
            $table->foreignId('reopened_by')->nullable()->after('reopened_at')->constrained('users')->nullOnDelete();

            $table->index('archived_at', 'pig_cycles_archived_at_idx');
            $table->index(['status', 'stage'], 'pig_cycles_status_stage_idx');
        });

        DB::table('pig_cycles')
            ->whereNull('archived_at')
            ->where(function ($query): void {
                $query->where('stage', 'Completed')
                    ->orWhereIn('status', ['Sold', 'Closed']);
            })
            ->update([
                'archived_at' => DB::raw('COALESCE(updated_at, created_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pig_cycles', function (Blueprint $table): void {
            $table->dropIndex('pig_cycles_archived_at_idx');
            $table->dropIndex('pig_cycles_status_stage_idx');

            $table->dropConstrainedForeignId('archived_by');
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropColumn(['archived_at', 'reopened_at']);
        });
    }
};
