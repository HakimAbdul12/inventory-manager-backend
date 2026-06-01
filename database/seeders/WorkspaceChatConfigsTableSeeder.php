<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WorkspaceChatConfigsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('workspace_chat_configs')->delete();
        
        \DB::table('workspace_chat_configs')->insert(array (
            0 => 
            array (
                'id' => '019c7d38-e8bf-70fc-86f2-2379deae7544',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'widget_api_key' => 'wk_PWLW0SWEBdSARyYp0TsYjTjxuayy72EiGqJ2fTAqHd4fq7R3HQdbTYTT0nQKt',
                'bot_name' => 'Gingo',
                'bot_personality' => 'professional',
                'greeting_message' => 'Hi there, what can I do you for?',
                'widget_settings' => '{"primary_color":"#e0d024","accent_color":"#3b82f6","position":"right","auto_open_delay":5,"logo_url":null,"dark_mode":false,"welcome_message":"Hi there, what can I do you for?","vdp_url_template":"http:\\/\\/localhost:5173\\/vehicle\\/{{system_id}}"}',
                'business_hours' => NULL,
                'auto_human_handoff' => true,
                'fallback_timeout_minutes' => 5,
                'ai_aggressiveness' => 'balanced',
                'is_active' => true,
                'allowed_domains' => NULL,
                'created_at' => '2026-02-20 22:43:27',
                'updated_at' => '2026-03-25 01:13:13',
                'external_api_config' => NULL,
            ),
        ));
        
        
    }
}