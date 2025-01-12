<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/countries.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            DB::table('countries')->insert([
                'code' => $data['code'],
                'name' => $data['name'],
                'iso_code' => $data['iso_code']
            ]);
        }
    }
}
