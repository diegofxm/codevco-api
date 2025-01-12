<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/roles.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            DB::table('roles')->insert([
                'name' => $data['name'],
                'description' => $data['description'],
                'created_at' => $data['created_at'],
                'updated_at' => now()
            ]);
        }
    }
}
