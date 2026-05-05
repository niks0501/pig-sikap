<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('contact_person', 255)->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('name');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};