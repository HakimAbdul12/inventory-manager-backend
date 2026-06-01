<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CreditApplicationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('credit_applications')->delete();
        
        \DB::table('credit_applications')->insert(array (
            0 => 
            array (
                'id' => '019e3971-2fee-730e-b410-fdfe2feb987e',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
                'token' => 'Mb0RpR4Ywcc1hQTJ5hhTxRFeIChtKTPQgpXlOE7cKXySwrWW6lYgByTFcVcfjuMa',
                'status' => 'opened',
                'is_active' => true,
                'application_data' => '{"personal_info": {"email": "abdulhakimaben@gmail.com", "phone": "0593821578", "last_name": "", "first_name": "Hakim"}}',
                'sent_at' => NULL,
                'opened_at' => '2026-05-18 04:56:49',
                'submitted_at' => NULL,
                'esignature_name' => NULL,
                'esignature_date' => NULL,
                'created_by' => 1,
                'reactivated_by' => NULL,
                'reactivated_at' => NULL,
                'created_at' => '2026-05-18 04:56:19',
                'updated_at' => '2026-05-18 04:56:49',
            ),
        ));
        
        
    }
}