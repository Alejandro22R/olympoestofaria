<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            BranchSeeder::class,
            CatalogSeeder::class,
            CheckinCatalogSeeder::class,
            MessageTemplateSeeder::class,
            SilhouetteSeeder::class,
            ProductionSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([UserSeeder::class, DemoOperationsSeeder::class, DemoProductionSeeder::class, DemoPortalSeeder::class]);
        }
    }
}
