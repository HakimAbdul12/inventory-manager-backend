<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDriveConfigsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('test_drive_configs')->delete();
        
        \DB::table('test_drive_configs')->insert(array (
            0 => 
            array (
                'id' => '019d1391-d3a4-7271-979d-fca388dc3087',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'max_per_day' => NULL,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'duration_minutes' => 30,
                'buffer_minutes' => 0,
                'max_concurrent' => 1,
                'available_days' => '[1,2,3,4,5]',
                'blocked_dates' => '[]',
                'is_active' => true,
                'created_at' => '2026-03-22 03:23:37',
                'updated_at' => '2026-03-22 03:31:14',
            ),
            1 => 
            array (
                'id' => '019d138a-1a60-72ee-9e4e-04e8060b5477',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'max_per_day' => NULL,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'duration_minutes' => 60,
                'buffer_minutes' => 15,
                'max_concurrent' => 4,
                'available_days' => '[1,2,3,4,5,6]',
                'blocked_dates' => '[]',
                'is_active' => true,
                'created_at' => '2026-03-22 03:15:11',
                'updated_at' => '2026-05-03 23:18:24',
            ),
        ));
        
        
    }
}