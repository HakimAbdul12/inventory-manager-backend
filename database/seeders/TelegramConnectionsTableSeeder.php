<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TelegramConnectionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('telegram_connections')->delete();
        
        \DB::table('telegram_connections')->insert(array (
            0 => 
            array (
                'id' => '019ca282-cfa5-732d-ac7d-af7ba7c0bfdf',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'telegram_chat_id' => '2064517751',
                'connected_by' => 1,
                'is_active' => true,
                'auto_away_message' => NULL,
                'agent_sla_minutes' => 5,
                'verified_at' => '2026-03-02 16:40:43',
                'created_at' => '2026-02-28 04:30:07',
                'updated_at' => '2026-05-06 15:10:45',
                'connection_code' => 'D1EXGU',
                'connection_code_expires_at' => '2026-05-07 15:10:45',
            ),
            1 => 
            array (
                'id' => '019ca27d-9266-7093-b9da-c7f3c3e933b9',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'telegram_chat_id' => '112233',
                'connected_by' => NULL,
                'is_active' => true,
                'auto_away_message' => NULL,
                'agent_sla_minutes' => 5,
                'verified_at' => '2026-02-28 04:25:37',
                'created_at' => '2026-02-28 04:24:24',
                'updated_at' => '2026-02-28 04:25:37',
                'connection_code' => NULL,
                'connection_code_expires_at' => '2026-03-01 04:24:24',
            ),
        ));
        
        
    }
}