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
        Schema::table('pig_cycle_sales', function (Blueprint $table): void {
            $table->foreignId('buyer_id')->nullable()->after('batch_id');
            $table->string('sale_method')->default('live_weight')->after('sale_date');
            $table->decimal('live_weight_kg', 8, 2)->nullable()->after('sale_method');
            $table->decimal('price_per_kg', 10, 2)->nullable()->after('live_weight_kg');
            $table->decimal('price_per_head', 10, 2)->nullable()->after('price_per_kg');
            $table->string('payment_status')->default('paid')->after('price_per_head');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('payment_status');
            $table->string('receipt_reference')->nullable()->after('amount_paid');
            $table->string('receipt_path')->nullable()->after('receipt_reference');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');

            $table->index('buyer_id');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pig_cycle_sales', function (Blueprint $table): void {
            $table->dropIndex(['buyer_id']);
            $table->dropIndex(['payment_status']);

            $table->dropColumn('buyer_id');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'sale_method',
                'live_weight_kg',
                'price_per_kg',
                'price_per_head',
                'payment_status',
                'amount_paid',
                'receipt_reference',
                'receipt_path',
            ]);
        });
    }
};
