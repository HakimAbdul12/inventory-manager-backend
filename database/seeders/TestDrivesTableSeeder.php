<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDrivesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('test_drives')->delete();
        
        \DB::table('test_drives')->insert(array (
            0 => 
            array (
                'id' => '019d139c-035e-71ef-94fb-82e8c99a9e0a',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'booking_code' => 'EA6ZT6',
                'conversation_id' => '019d1399-e93c-71ca-85c9-a43ec7c973a8',
                'vehicle_id' => NULL,
                'visitor_name' => 'Hakim',
                'visitor_email' => 'abdulhakimaben@gmail.com',
                'visitor_phone' => '+233594059330',
                'scheduled_date' => '2026-03-23',
                'scheduled_time' => '10:00:00',
                'end_time' => '10:30:00',
                'status' => 'cancelled',
                'notes' => NULL,
                'cancelled_at' => '2026-03-22 03:39:02',
                'cancellation_reason' => NULL,
                'created_at' => '2026-03-22 03:34:44',
                'updated_at' => '2026-03-22 03:39:02',
            ),
            1 => 
            array (
                'id' => '019d13a3-b632-7295-90dd-e88d5c7c5dc7',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'booking_code' => 'KS2FAF',
                'conversation_id' => '019d13a1-15b9-70ce-a393-43aec61640f8',
                'vehicle_id' => NULL,
                'visitor_name' => 'John Boamah',
                'visitor_email' => 'abdulhakimaben@gmail.com',
                'visitor_phone' => '0548605318',
                'scheduled_date' => '2026-03-23',
                'scheduled_time' => '09:00:00',
                'end_time' => '09:30:00',
                'status' => 'pending',
                'notes' => NULL,
                'cancelled_at' => NULL,
                'cancellation_reason' => NULL,
                'created_at' => '2026-03-22 03:43:09',
                'updated_at' => '2026-03-22 03:43:09',
            ),
        ));
        
        
    }
}