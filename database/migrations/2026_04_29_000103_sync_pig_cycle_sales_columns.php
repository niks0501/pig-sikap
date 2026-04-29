<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'pig_cycle_sales';

        if (! Schema::hasColumn($tableName, 'sale_method')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('sale_method')->default('live_weight')->after('sale_date');
            });
        }

        if (! Schema::hasColumn($tableName, 'live_weight_kg')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('live_weight_kg', 8, 2)->nullable()->after('sale_method');
            });
        }

        if (! Schema::hasColumn($tableName, 'price_per_kg')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('price_per_kg', 10, 2)->nullable()->after('live_weight_kg');
            });
        }

        if (! Schema::hasColumn($tableName, 'price_per_head')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('price_per_head', 10, 2)->nullable()->after('price_per_kg');
            });
        }

        if (! Schema::hasColumn($tableName, 'payment_status')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('payment_status')->default('paid')->after('price_per_head');
            });
        }

        if (! Schema::hasColumn($tableName, 'amount_paid')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('amount_paid', 12, 2)->default(0)->after('payment_status');
            });
        }

        if (! Schema::hasColumn($tableName, 'receipt_reference')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('receipt_reference')->nullable()->after('amount_paid');
            });
        }

        if (! Schema::hasColumn($tableName, 'receipt_path')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('receipt_path')->nullable()->after('receipt_reference');
            });
        }

        if (! Schema::hasColumn($tableName, 'updated_by')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            });
        } elseif (! $this->hasForeignKey($tableName, 'updated_by')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('pig_buyers') && Schema::hasColumn($tableName, 'buyer_id') && ! $this->hasForeignKey($tableName, 'buyer_id')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('buyer_id')->references('id')->on('pig_buyers')->nullOnDelete();
            });
        }

        if (Schema::hasColumn($tableName, 'buyer_id') && ! $this->hasIndex($tableName, 'pig_cycle_sales_buyer_id_index')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->index('buyer_id');
            });
        }

        if (Schema::hasColumn($tableName, 'payment_status') && ! $this->hasIndex($tableName, 'pig_cycle_sales_payment_status_index')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->index('payment_status');
            });
        }
    }

    public function down(): void
    {
        $tableName = 'pig_cycle_sales';

        if ($this->hasForeignKey($tableName, 'buyer_id')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['buyer_id']);
            });
        }

        if ($this->hasForeignKey($tableName, 'updated_by')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['updated_by']);
            });
        }

        if ($this->hasIndex($tableName, 'pig_cycle_sales_buyer_id_index')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex('pig_cycle_sales_buyer_id_index');
            });
        }

        if ($this->hasIndex($tableName, 'pig_cycle_sales_payment_status_index')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex('pig_cycle_sales_payment_status_index');
            });
        }

        $columnsToDrop = [];

        foreach ([
            'sale_method',
            'live_weight_kg',
            'price_per_kg',
            'price_per_head',
            'payment_status',
            'amount_paid',
            'receipt_reference',
            'receipt_path',
            'updated_by',
        ] as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table($tableName, function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$tableName}')");

            foreach ($indexes as $index) {
                $name = $index->name ?? $index->index ?? null;

                if ($name === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $result = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$tableName, $indexName]
        );

        return $result !== null;
    }

    private function hasForeignKey(string $tableName, string $column): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $keys = DB::select("PRAGMA foreign_key_list('{$tableName}')");

            foreach ($keys as $key) {
                $from = $key->from ?? null;

                if ($from === $column) {
                    return true;
                }
            }

            return false;
        }

        $result = DB::selectOne(
            'SELECT 1 FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? AND referenced_table_name IS NOT NULL LIMIT 1',
            [$tableName, $column]
        );

        return $result !== null;
    }
};
