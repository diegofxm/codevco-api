<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/departments.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            DB::table('departments')->insert([
                'id' => intval($data['code']),
                'code' => $data['code'],
                'name' => $data['name'],
                'country_id' => $data['country_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
