<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the association_expenses table for expenses not tied to a
 * specific pig cycle (e.g. meeting costs, bank withdrawal fees,
 * association supplies, general emergency funds).
 *
 * Links to resolutions, withdrawals, canvasses, and suppliers for
 * structured procurement tracking and budget-vs-actual reporting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('category');
            $table->string('feed_subcategory')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date')->index();
            $table->string('receipt_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('canvass_id')->nullable();
            $table->string('fund_source')->nullable();
            $table->unsignedBigInteger('approved_resolution_id')->nullable();
            $table->unsignedBigInteger('withdrawal_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['category', 'expense_date']);
            $table->index(['fund_source', 'expense_date']);
            $table->index(['approved_resolution_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_expenses');
    }
};
