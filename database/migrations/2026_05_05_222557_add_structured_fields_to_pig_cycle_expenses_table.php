<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds structured procurement and tracking fields to pig_cycle_expenses:
 * - item_name: structured item description for each expense line
 * - supplier_id: links to the supplier who provided goods/services
 * - receipt_reference: invoice or official receipt number
 * - feed_subcategory: pre_starter, starter, grower, finisher
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pig_cycle_expenses', function (Blueprint $table) {
            $table->string('item_name')->nullable()->after('withdrawal_id');
            $table->foreignId('supplier_id')->nullable()->after('item_name')->constrained('suppliers')->nullOnDelete();
            $table->string('receipt_reference')->nullable()->after('supplier_id');
            $table->string('feed_subcategory')->nullable()->after('receipt_reference');
        });
    }

    public function down(): void
    {
        Schema::table('pig_cycle_expenses', function (Blueprint $table) {
            $table->dropColumn(['feed_subcategory', 'receipt_reference']);
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn('item_name');
        });
    }
};
