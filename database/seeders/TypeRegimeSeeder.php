<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeRegimeSeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/type_regimes.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            DB::table('type_regimes')->insert([
                'id' => intval($data['code']),
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
