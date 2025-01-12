<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeOperationSeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/type_operations.csv');
        $operations = array_map('str_getcsv', file($csvFile));
        $header = array_shift($operations);

        foreach ($operations as $row) {
            $data = array_combine($header, $row);
            DB::table('type_operations')->insert([
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
