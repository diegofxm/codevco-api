<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/cities.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);
        $columnCount = count($header);
        $processedCodes = [];

        foreach ($csvData as $row) {
            // Asegurarse de que la fila tenga el número correcto de columnas
            if (count($row) !== $columnCount) {
                continue;
            }

            $data = array_combine($header, $row);
            
            // Saltar si el código ya fue procesado
            if (in_array($data['code'], $processedCodes)) {
                continue;
            }
            
            $processedCodes[] = $data['code'];
            
            DB::table('cities')->insert([
                'code' => $data['code'],
                'name' => $data['name'],
                'department_id' => $data['department_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
