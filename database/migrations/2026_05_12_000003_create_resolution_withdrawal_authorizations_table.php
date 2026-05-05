<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolution_withdrawal_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained('resolutions');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('designated_by')->constrained('users');
            $table->timestamp('designated_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['resolution_id', 'user_id'], 'res_withdraw_auth_unique');
            $table->index('resolution_id', 'res_withdraw_auth_res_idx');
            $table->index('user_id', 'res_withdraw_auth_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_withdrawal_authorizations');
    }
};