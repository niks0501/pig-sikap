<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 40);
            $table->string('format', 10);
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->json('filters_json')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('status', 20)->default('generated');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['report_type', 'format']);
            $table->index('generated_at');
            $table->foreign('cycle_id')->references('id')->on('pig_cycles')->nullOnDelete();
            $table->foreign('schedule_id')->references('id')->on('report_schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
