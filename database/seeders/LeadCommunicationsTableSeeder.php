<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LeadCommunicationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lead_communications')->delete();
        
        \DB::table('lead_communications')->insert(array (
            0 => 
            array (
                'id' => '019e298e-a379-724d-beca-ef1e38cc6c61',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
                'channel' => 'email',
                'direction' => 'outbound',
                'from_address' => NULL,
                'to_address' => 'abdulhakimaben@gmail.com',
                'subject' => 'Push report',
                'body' => 'Hi {{lead_first_name}}, 
We want to send you your vehicle report







{{vehicle_title}}



{{vehicle_price}}



{{vehicle_vin}}',
                'body_html' => '<p><br>Hi {{lead_first_name}}, <br>We want to send you your vehicle report</p><p></p><ol><li><p>{{vehicle_title}}</p></li><li><p>{{vehicle_price}}</p></li><li><p>{{vehicle_vin}}</p></li></ol><p></p>',
                'status' => 'sent',
                'metadata' => NULL,
                'sent_by' => 1,
                'sent_at' => '2026-05-15 02:54:34',
                'created_at' => '2026-05-15 02:54:34',
                'updated_at' => '2026-05-15 02:54:34',
            ),
            1 => 
            array (
                'id' => '019e2991-3bfd-73bf-9cf6-1ccbfe90062d',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
                'channel' => 'email',
                'direction' => 'outbound',
                'from_address' => NULL,
                'to_address' => 'abdulhakimaben@gmail.com',
                'subject' => 'Send report',
                'body' => 'Hi {{lead_first_name}}, 
We want to send you your vehicle report







{{vehicle_title}}



{{vehicle_price}}



{{vehicle_vin}}',
                'body_html' => '<p><br>Hi {{lead_first_name}}, <br>We want to send you your vehicle report</p><p></p><ol><li><p>{{vehicle_title}}</p></li><li><p>{{vehicle_price}}</p></li><li><p>{{vehicle_vin}}</p></li></ol><p></p>',
                'status' => 'sent',
                'metadata' => NULL,
                'sent_by' => 1,
                'sent_at' => '2026-05-15 02:57:24',
                'created_at' => '2026-05-15 02:57:24',
                'updated_at' => '2026-05-15 02:57:24',
            ),
            2 => 
            array (
                'id' => '019e299a-31b7-7108-b1a3-45fa6e2b39c7',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
                'channel' => 'email',
                'direction' => 'outbound',
                'from_address' => 'info@classmasta.com',
                'to_address' => 'abdulhakimaben@gmail.com',
                'subject' => 'Send Report',
                'body' => 'Hi Hakim, 
We want to send you your vehicle report





2024 BMW m5cs



$69,500



XYZ123

Thank you Mr. Hakim for doing business with us',
                'body_html' => '<p><br>Hi Hakim, <br>We want to send you your vehicle report</p><ol><li><p>2024 BMW m5cs</p></li><li><p>$69,500</p></li><li><p>XYZ123</p></li></ol><p>Thank you Mr. Hakim for doing business with us<br></p>Moris Autos',
                'status' => 'sent',
                'metadata' => NULL,
                'sent_by' => 1,
                'sent_at' => '2026-05-15 03:07:11',
                'created_at' => '2026-05-15 03:07:11',
                'updated_at' => '2026-05-15 03:07:11',
            ),
        ));
        
        
    }
}