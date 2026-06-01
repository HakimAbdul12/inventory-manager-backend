<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MessageTemplatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('message_templates')->delete();
        
        \DB::table('message_templates')->insert(array (
            0 => 
            array (
                'id' => '019e298e-4559-7282-b49e-f2dfdd69c613',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'send customer report',
                'channel' => 'email',
                'subject' => NULL,
                'body' => 'Hi {{lead_first_name}}, 
We want to send you your vehicle report







{{vehicle_title}}



{{vehicle_price}}



{{vehicle_vin}}',
                'body_html' => '<p><br>Hi {{lead_first_name}}, <br>We want to send you your vehicle report</p><p></p><ol><li><p>{{vehicle_title}}</p></li><li><p>{{vehicle_price}}</p></li><li><p>{{vehicle_vin}}</p></li></ol><p></p>',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => '2026-05-15 02:54:10',
                'updated_at' => '2026-05-15 02:54:10',
                'is_system' => false,
                'system_key' => NULL,
                'required_variables' => NULL,
            ),
            1 => 
            array (
                'id' => '019e40bc-5844-729a-b70b-bfceea5aa13f',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'Reach out thanks',
                'channel' => 'email',
                'subject' => 'Thanks for getting back to us: {{tenant_name}}',
                'body' => 'Hi {{lead_first_name}},
Thanks very much for reaching out to us

{{tenant_name}}',
                'body_html' => '<p>Hi {{lead_first_name}},<br>Thanks very much for reaching out to us</p><p><strong>{{tenant_name}}</strong></p>',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => '2026-05-19 14:55:45',
                'updated_at' => '2026-05-19 14:55:45',
                'is_system' => false,
                'system_key' => NULL,
                'required_variables' => '["lead_first_name"]',
            ),
        ));
        
        
    }
}