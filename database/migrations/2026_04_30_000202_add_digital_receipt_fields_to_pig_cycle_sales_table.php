<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pig_cycle_sales')) {
            return;
        }

        Schema::table('pig_cycle_sales', function (Blueprint $table): void {
            if (! Schema::hasColumn('pig_cycle_sales', 'digital_receipt_number')) {
                $table->string('digital_receipt_number')->nullable()->after('receipt_path');
            }

            if (! Schema::hasColumn('pig_cycle_sales', 'digital_receipt_path')) {
                $table->string('digital_receipt_path')->nullable()->after('digital_receipt_number');
            }

            if (! Schema::hasColumn('pig_cycle_sales', 'digital_receipt_email')) {
                $table->string('digital_receipt_email')->nullable()->after('digital_receipt_path');
            }

            if (! Schema::hasColumn('pig_cycle_sales', 'digital_receipt_status')) {
                $table->string('digital_receipt_status')->default('not_sent')->after('digital_receipt_email');
            }

            if (! Schema::hasColumn('pig_cycle_sales', 'digital_receipt_sent_at')) {
                $table->timestamp('digital_receipt_sent_at')->nullable()->after('digital_receipt_status');
            }

            if (! Schema::hasColumn('pig_cycle_sales', 'digital_receipt_error')) {
                $table->text('digital_receipt_error')->nullable()->after('digital_receipt_sent_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pig_cycle_sales')) {
            return;
        }

        Schema::table('pig_cycle_sales', function (Blueprint $table): void {
            $columns = [];

            foreach ([
                'digital_receipt_number',
                'digital_receipt_path',
                'digital_receipt_email',
                'digital_receipt_status',
                'digital_receipt_sent_at',
                'digital_receipt_error',
            ] as $column) {
                if (Schema::hasColumn('pig_cycle_sales', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
