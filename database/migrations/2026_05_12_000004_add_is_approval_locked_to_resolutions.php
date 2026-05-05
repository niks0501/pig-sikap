<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resolutions', function (Blueprint $table) {
            $table->boolean('is_approval_locked')->default(false)->after('approval_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('resolutions', function (Blueprint $table) {
            $table->dropColumn('is_approval_locked');
        });
    }
};