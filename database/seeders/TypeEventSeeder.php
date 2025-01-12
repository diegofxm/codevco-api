<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeEventSeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/type_events.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            DB::table('type_events')->insert([
                'id' => intval($data['code']),
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'],
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
