<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SystemAdminSeeder::class,
            HealthTemplateSeeder::class,
            AssociationPolicySettingsSeeder::class,
            DemoUserSeeder::class,
            DemoBulkUsersSeeder::class,
            DemoCycleSeeder::class,
            PigSikapOwnerRecordSeeder::class,
            DemoBulkCyclesSeeder::class,
            DemoExpenseSeeder::class,
            DemoSaleSeeder::class,
            DemoHealthSeeder::class,
            DemoWorkflowSeeder::class,
            DemoBulkWorkflowSeeder::class,
            DemoCanvassingSeeder::class,
            DemoBulkOperationsSeeder::class,
            DemoReportsSeeder::class,
        ]);
    }
}
