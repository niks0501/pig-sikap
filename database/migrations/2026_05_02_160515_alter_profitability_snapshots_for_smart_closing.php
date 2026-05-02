<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profitability_snapshots', function (Blueprint $table): void {
            $table->dropForeign(['pig_cycle_id']);
            $table->dropIndex('profitability_snapshots_pig_cycle_id_unique');

            $table->unsignedInteger('snapshot_number')->nullable()->after('id');
            $table->unsignedInteger('version_number')->default(1)->after('pig_cycle_id');
            $table->decimal('total_collected', 14, 2)->default(0)->after('gross_income');
            $table->decimal('receivables', 14, 2)->default(0)->after('total_collected');
            $table->string('source_hash', 64)->nullable()->after('computation_version');
            $table->boolean('is_current')->default(true)->after('source_hash');
            $table->foreignId('supersedes_snapshot_id')->nullable()->after('is_current');
            $table->string('re_finalize_reason_code')->nullable()->after('notes');
            $table->text('re_finalize_reason_notes')->nullable()->after('re_finalize_reason_code');
            $table->json('sales_summary_json')->nullable()->after('share_rule_json');
            $table->json('validation_warnings_json')->nullable()->after('sales_summary_json');
        });

        Schema::table('profitability_snapshots', function (Blueprint $table): void {
            $table->unique(['pig_cycle_id', 'version_number'], 'profitability_snapshots_cycle_version_unique');
            $table->index('snapshot_number');
            $table->index('source_hash');
            $table->index(['pig_cycle_id', 'is_current'], 'profitability_snapshots_cycle_current_index');
            $table->foreign('pig_cycle_id')->references('id')->on('pig_cycles')->cascadeOnDelete();
            $table->foreign('supersedes_snapshot_id')->references('id')->on('profitability_snapshots')->nullOnDelete();
        });

        }

    public function down(): void
    {
        Schema::table('profitability_snapshots', function (Blueprint $table): void {
            $table->dropForeign(['supersedes_snapshot_id']);
            $table->dropForeign(['pig_cycle_id']);
            $table->dropIndex('profitability_snapshots_cycle_current_index');
            $table->dropIndex('profitability_snapshots_source_hash_index');
            $table->dropIndex('profitability_snapshots_snapshot_number_index');
            $table->dropUnique('profitability_snapshots_cycle_version_unique');

            $table->dropColumn([
                'snapshot_number',
                'version_number',
                'total_collected',
                'receivables',
                'source_hash',
                'is_current',
                'supersedes_snapshot_id',
                're_finalize_reason_code',
                're_finalize_reason_notes',
                'sales_summary_json',
                'validation_warnings_json',
            ]);
        });

        Schema::table('profitability_snapshots', function (Blueprint $table): void {
            $table->unique('pig_cycle_id');
            $table->foreign('pig_cycle_id')->references('id')->on('pig_cycles')->cascadeOnDelete();
        });
    }
};