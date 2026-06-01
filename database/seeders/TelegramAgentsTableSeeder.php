<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TelegramAgentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('telegram_agents')->delete();
        
        \DB::table('telegram_agents')->insert(array (
            0 => 
            array (
                'id' => '019ca6db-6a59-712f-b03d-abe3b9cf2267',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'telegram_chat_id' => '112233',
                'first_name' => 'Legacy Agent',
                'username' => NULL,
                'is_active' => true,
                'created_at' => '2026-03-01 00:45:23',
                'updated_at' => '2026-03-01 00:45:23',
                'custom_name' => NULL,
            ),
            1 => 
            array (
                'id' => '019ca6db-6a69-739a-b2e0-8e78aca927d2',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'telegram_chat_id' => '2064517751',
                'first_name' => 'Hakim Abdul',
                'username' => NULL,
                'is_active' => true,
                'created_at' => '2026-03-01 00:45:23',
                'updated_at' => '2026-03-01 00:47:51',
                'custom_name' => NULL,
            ),
            2 => 
            array (
                'id' => '019ca799-3802-7241-8f97-4dd7d93a3f9c',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'telegram_chat_id' => '1632984469',
                'first_name' => 'K',
                'username' => NULL,
                'is_active' => true,
                'created_at' => '2026-03-01 04:12:42',
                'updated_at' => '2026-03-01 04:19:32',
                'custom_name' => 'Halius',
            ),
            3 => 
            array (
                'id' => '019caf6c-67e1-70b2-a394-2664b8bb361c',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'telegram_chat_id' => '8234086660',
                'first_name' => 'Pamela',
                'username' => NULL,
                'is_active' => true,
                'created_at' => '2026-03-02 16:40:43',
                'updated_at' => '2026-03-02 16:40:43',
                'custom_name' => NULL,
            ),
        ));
        
        
    }
}