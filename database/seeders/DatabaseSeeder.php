<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeders para tablas base sin dependencias
        $this->call([
            RoleSeeder::class,
            CountrySeeder::class,
            DepartmentSeeder::class,
            CitySeeder::class,
            TypeDocumentSeeder::class,
            TypeOrganizationSeeder::class,
            TypeRegimeSeeder::class,
            TypeLiabilitySeeder::class,
            EconomicActivitySeeder::class,
            UnitMeasureSeeder::class,
            CurrencySeeder::class,
            TypeEventSeeder::class,
            TaxSeeder::class,
            PaymentMethodSeeder::class,
            TypeOperationSeeder::class,
            UserSeeder::class,
        ]);
    }
}
