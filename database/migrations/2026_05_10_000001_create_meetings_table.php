<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('date');
            $table->string('location')->nullable();
            $table->text('agenda')->nullable();
            $table->text('minutes_summary')->nullable();
            $table->string('minutes_file_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->timestamps();

            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
