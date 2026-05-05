<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add focal_person_name to resolutions – the designated point person
     * responsible for the resolution's implementation.
     */
    public function up(): void
    {
        Schema::table('resolutions', function (Blueprint $table) {
            $table->string('focal_person_name')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('resolutions', function (Blueprint $table) {
            $table->dropColumn('focal_person_name');
        });
    }
};
