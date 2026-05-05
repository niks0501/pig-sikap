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
        Schema::create('member_share_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profitability_snapshot_id')
                ->constrained('profitability_snapshots')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->decimal('allocation_percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // One distribution per member per snapshot
            $table->unique(['profitability_snapshot_id', 'user_id'], 'msd_snapshot_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_share_distributions');
    }
};
