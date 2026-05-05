<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolution_member_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained('resolutions')->unique('res_member_snap_unique');
            $table->json('snapshot_data');
            $table->integer('eligible_count');
            $table->integer('required_approvals');
            $table->timestamp('snapshot_taken_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_member_snapshots');
    }
};