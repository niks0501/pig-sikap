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
        Schema::create('health_template_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('health_template_id')->constrained('health_templates')->cascadeOnDelete();
            $table->string('task_name');
            $table->string('task_type');
            $table->unsignedSmallInteger('day_offset_start');
            $table->unsignedSmallInteger('day_offset_end')->nullable();
            $table->boolean('is_optional')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('default_notes')->nullable();
            $table->timestamps();

            $table->index('task_type');
            $table->index('sort_order');
            $table->index(['health_template_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_template_items');
    }
};
