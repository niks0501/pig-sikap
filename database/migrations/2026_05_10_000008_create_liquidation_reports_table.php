<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withdrawal_id')->constrained('withdrawals')->cascadeOnDelete();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->string('report_file_path')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index('withdrawal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidation_reports');
    }
};
