<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('meeting_id')->constrained('meetings');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->string('reason')->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users');
            $table->timestamp('waived_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('user_id');
            $table->index('meeting_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_penalties');
    }
};