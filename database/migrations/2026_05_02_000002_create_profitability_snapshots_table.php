<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profitability_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pig_cycle_id')->constrained('pig_cycles')->cascadeOnDelete();
            $table->decimal('gross_income', 14, 2)->default(0);
            $table->decimal('total_expenses', 14, 2)->default(0);
            $table->decimal('net_profit_or_loss', 14, 2)->default(0);
            $table->decimal('distributable_profit', 14, 2)->default(0);
            $table->decimal('caretaker_share', 14, 2)->default(0);
            $table->decimal('member_share', 14, 2)->default(0);
            $table->decimal('association_share', 14, 2)->default(0);
            $table->json('expense_breakdown_json');
            $table->json('share_rule_json');
            $table->timestamp('finalized_at');
            $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('computation_version')->default('2026-05-cycle-profitability-v1');
            $table->timestamps();

            $table->unique('pig_cycle_id');
            $table->index('finalized_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profitability_snapshots');
    }
};
