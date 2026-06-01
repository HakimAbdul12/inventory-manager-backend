<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ServiceRecordsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('service_records')->delete();
        
        \DB::table('service_records')->insert(array (
            0 => 
            array (
                'id' => '019e2d60-738b-726d-ba1f-89b5e6b86c20',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'service_type' => 'Oil Change',
                'date' => '2026-05-15',
                'cost' => '40.50',
                'vendor' => 'Julius Auto Repairs',
                'notes' => NULL,
                'created_at' => '2026-05-15 20:42:36',
                'updated_at' => '2026-05-15 20:42:36',
            ),
            1 => 
            array (
                'id' => '019e2e2c-1a53-72d6-a526-b9c0550e8a4f',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'service_type' => 'Tire Rotation',
                'date' => '2026-05-16',
                'cost' => '500.00',
                'vendor' => 'Julius Auto Repairs',
                'notes' => NULL,
                'created_at' => '2026-05-16 00:25:02',
                'updated_at' => '2026-05-16 00:25:02',
            ),
        ));
        
        
    }
}