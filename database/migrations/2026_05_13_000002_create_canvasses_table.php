<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvasses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->nullable()->constrained('resolutions')->nullOnDelete();
            $table->foreignId('meeting_id')->nullable()->constrained('meetings')->nullOnDelete();
            $table->string('title');
            $table->date('canvass_date');
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('resolution_id');
            $table->index('meeting_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvasses');
    }
};