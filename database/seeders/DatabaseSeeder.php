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
            DemoCycleSeeder::class,
            PigSikapOwnerRecordSeeder::class,
            DemoExpenseSeeder::class,
            DemoSaleSeeder::class,
            DemoHealthSeeder::class,
            DemoWorkflowSeeder::class,
            DemoCanvassingSeeder::class,
            DemoReportsSeeder::class,
        ]);
    }
}
