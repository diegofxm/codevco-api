<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitMeasureSeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/unit_measures.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            DB::table('unit_measures')->insert([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
