<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 40);
            $table->string('format', 10);
            $table->string('frequency', 20);
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->time('run_at')->nullable();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->json('filters_json')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['report_type', 'status']);
            $table->index('next_run_at');
            $table->foreign('cycle_id')->references('id')->on('pig_cycles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
