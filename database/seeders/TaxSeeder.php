<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/taxes.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            DB::table('taxes')->insert([
                'id' => intval($data['code']),
                'code' => $data['code'],
                'name' => $data['name'],
                'rate' => floatval($data['rate']),
                'type' => $data['type'],
                'status' => filter_var($data['status'], FILTER_VALIDATE_BOOLEAN),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
