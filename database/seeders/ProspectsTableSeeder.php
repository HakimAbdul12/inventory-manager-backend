<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProspectsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('prospects')->delete();
        
        \DB::table('prospects')->insert(array (
            0 => 
            array (
                'id' => '019e38cc-3846-7056-a3c0-97a0e7440a3a',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'first_name' => 'Hakim',
                'last_name' => NULL,
                'email' => 'abdulhakimaben@gmail.com',
                'phone' => '0593821578',
                'assigned_to' => NULL,
                'notes' => NULL,
                'created_at' => '2026-05-18 01:56:08',
                'updated_at' => '2026-05-18 01:56:08',
            ),
            1 => 
            array (
                'id' => '019e38cc-3863-7261-b804-0577d7f39891',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'first_name' => 'Pamela',
                'last_name' => 'Fongo',
                'email' => 'pamelafg2002@gmail.com',
                'phone' => '0548605318',
                'assigned_to' => NULL,
                'notes' => NULL,
                'created_at' => '2026-05-18 01:56:08',
                'updated_at' => '2026-05-18 01:56:08',
            ),
            2 => 
            array (
                'id' => '019e38cc-387a-72bd-8480-2bb015146b74',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone' => '1234567890',
                'assigned_to' => NULL,
                'notes' => NULL,
                'created_at' => '2026-05-18 01:56:08',
                'updated_at' => '2026-05-18 01:56:08',
            ),
        ));
        
        
    }
}