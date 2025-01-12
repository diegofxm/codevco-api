<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EconomicActivitySeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/economic_activities.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            DB::table('economic_activities')->insert([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
