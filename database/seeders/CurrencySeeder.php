<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/currencies.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            DB::table('currencies')->insert([
                'code' => $data['code'],
                'name' => $data['name'],
                'symbol' => $data['symbol'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
