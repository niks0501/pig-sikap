<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolution_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained('resolutions')->cascadeOnDelete();
            $table->string('category');
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('pc');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('resolution_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_line_items');
    }
};
