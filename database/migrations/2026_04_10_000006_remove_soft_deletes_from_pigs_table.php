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
        if (Schema::hasColumn('pigs', 'deleted_at')) {
            Schema::table('pigs', function (Blueprint $table): void {
                $table->dropColumn('deleted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('pigs', 'deleted_at')) {
            Schema::table('pigs', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }
};
