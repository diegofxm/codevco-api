<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $methods = [
            [
                'code' => '1',
                'name' => 'Efectivo',
                'description' => 'Pago en efectivo',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => '2',
                'name' => 'Tarjeta Crédito',
                'description' => 'Pago con tarjeta de crédito',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => '3',
                'name' => 'Tarjeta Débito',
                'description' => 'Pago con tarjeta débito',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => '4',
                'name' => 'Transferencia',
                'description' => 'Pago por transferencia bancaria',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => '5',
                'name' => 'Cheque',
                'description' => 'Pago con cheque',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('payment_methods')->insert($methods);
    }
}
