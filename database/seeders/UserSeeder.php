<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $csvFile = database_path('seeders/catalogs/users.csv');
        $csvData = array_map('str_getcsv', file($csvFile));
        $header = array_shift($csvData);

        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            
            DB::table('users')->insert([
                'type_document_id' => $data['document_type_id'],
                'document_number' => $data['document_number'],
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make(str_replace('bcrypt$', '', $data['password'])),
                'role_id' => $data['role_id'],
                'email_verified_at' => $data['created_at'] ? $data['created_at'] : now(),
                'status' => filter_var($data['status'], FILTER_VALIDATE_BOOLEAN),
                'created_at' => $data['created_at'] ? $data['created_at'] : now(),
                'updated_at' => $data['updated_at'] ? $data['updated_at'] : now()
            ]);
        }
    }
}
