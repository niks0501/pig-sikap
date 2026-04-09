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
        Schema::create('pig_breeders', function (Blueprint $table): void {
            $table->id();
            $table->string('breeder_code')->unique();
            $table->string('name_or_tag');
            $table->string('reproductive_status');
            $table->date('acquisition_date')->nullable();
            $table->date('expected_farrowing_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('reproductive_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pig_breeders');
    }
};
