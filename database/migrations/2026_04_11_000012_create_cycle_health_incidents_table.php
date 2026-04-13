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
        Schema::create('cycle_health_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('pig_cycles')->cascadeOnDelete();
            $table->string('incident_type');
            $table->date('date_reported');
            $table->unsignedInteger('affected_count');
            $table->text('suspected_cause')->nullable();
            $table->text('treatment_given')->nullable();
            $table->text('remarks')->nullable();
            $table->string('media_path')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('incident_type');
            $table->index('date_reported');
            $table->index(['batch_id', 'incident_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_health_incidents');
    }
};
