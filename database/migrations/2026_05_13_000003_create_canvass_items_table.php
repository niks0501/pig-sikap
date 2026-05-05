<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvass_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canvass_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('description');
            $table->string('specifications')->nullable();
            $table->string('category')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 50);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total', 12, 2);
            $table->boolean('is_selected')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('canvass_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvass_items');
    }
};