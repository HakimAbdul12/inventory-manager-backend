<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LeadStatusHistoryTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lead_status_history')->delete();
        
        \DB::table('lead_status_history')->insert(array (
            0 => 
            array (
                'id' => '019e24a8-406b-70ee-97a8-b068f0a7526c',
                'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
                'from_status' => 'new',
                'to_status' => 'new',
                'changed_by' => 1,
            'notes' => 'Assigned vehicle: 2024 BMW m5cs (#019c6fce)',
                'created_at' => '2026-05-14 04:04:26',
            ),
            1 => 
            array (
                'id' => '019e24b0-f756-72fb-8381-eb32784239c8',
                'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
                'from_status' => 'new',
                'to_status' => 'new',
                'changed_by' => 1,
            'notes' => 'Assigned vehicle: 2023 BMW X7 (#019c358a)
Previously assigned primary vehicle: 2024 BMW m5cs',
            'created_at' => '2026-05-14 04:13:57',
        ),
        2 => 
        array (
            'id' => '019e298e-a389-7287-8e2c-7e2ef0a6a79e',
            'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
            'from_status' => 'new',
            'to_status' => 'new',
            'changed_by' => 1,
            'notes' => 'Email sent to abdulhakimaben@gmail.com
Subject: Push report
Hi {{lead_first_name}}, 
We want to send you your vehicle report







{{vehicle_title}}



{{vehicle_price}}



{{vehicle_vin}}',
            'created_at' => '2026-05-15 02:54:34',
        ),
        3 => 
        array (
            'id' => '019e298e-a398-72c0-b464-b39648aad665',
            'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
            'from_status' => 'new',
            'to_status' => 'contacted',
            'changed_by' => 1,
            'notes' => 'Auto-transitioned: first Email sent',
            'created_at' => '2026-05-15 02:54:34',
        ),
        4 => 
        array (
            'id' => '019e2991-3c0b-7392-ab9e-e88bb4a59574',
            'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
            'from_status' => 'contacted',
            'to_status' => 'contacted',
            'changed_by' => 1,
            'notes' => 'Email sent to abdulhakimaben@gmail.com
Subject: Send report
Hi {{lead_first_name}}, 
We want to send you your vehicle report







{{vehicle_title}}



{{vehicle_price}}



{{vehicle_vin}}',
            'created_at' => '2026-05-15 02:57:24',
        ),
        5 => 
        array (
            'id' => '019e299a-31fc-73da-9d64-f7b0ee36bd26',
            'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
            'from_status' => 'contacted',
            'to_status' => 'contacted',
            'changed_by' => 1,
            'notes' => 'Email sent to abdulhakimaben@gmail.com
Subject: Send Report
Hi {{lead_first_name}}, 
We want to send you your vehicle report





{{vehicle_title}}



{{vehicle_price}}



{{vehicle_vin}}

Thank you Mr. {{lead_first_name}} for doing business with us',
            'created_at' => '2026-05-15 03:07:11',
        ),
    ));
        
        
    }
}