<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChatMessageReactionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('chat_message_reactions')->delete();
        
        \DB::table('chat_message_reactions')->insert(array (
            0 => 
            array (
                'id' => 1,
                'chat_message_id' => 18,
                'user_id' => 3,
                'emoji' => '❤️',
                'created_at' => '2026-02-16 23:35:46',
            ),
            1 => 
            array (
                'id' => 2,
                'chat_message_id' => 21,
                'user_id' => 1,
                'emoji' => '🔥',
                'created_at' => '2026-02-16 23:44:20',
            ),
            2 => 
            array (
                'id' => 3,
                'chat_message_id' => 21,
                'user_id' => 1,
                'emoji' => '🎉',
                'created_at' => '2026-02-16 23:44:29',
            ),
            3 => 
            array (
                'id' => 4,
                'chat_message_id' => 19,
                'user_id' => 1,
                'emoji' => '👋',
                'created_at' => '2026-02-16 23:44:42',
            ),
        ));
        
        
    }
}