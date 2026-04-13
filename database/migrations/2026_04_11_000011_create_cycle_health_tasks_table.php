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
        Schema::create('cycle_health_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('pig_cycles')->cascadeOnDelete();
            $table->foreignId('health_template_item_id')->nullable()->constrained('health_template_items')->nullOnDelete();
            $table->string('task_name');
            $table->string('task_type');
            $table->date('planned_start_date');
            $table->date('planned_end_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('target_count');
            $table->unsignedInteger('completed_count')->default(0);
            $table->unsignedInteger('remaining_count')->default(0);
            $table->boolean('is_optional')->default(false);
            $table->text('remarks')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('task_type');
            $table->index('status');
            $table->index('planned_start_date');
            $table->index(['batch_id', 'status']);
            $table->index(['batch_id', 'planned_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_health_tasks');
    }
};
