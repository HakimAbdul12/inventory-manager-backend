<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChatRoomsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('chat_rooms')->delete();
        
        \DB::table('chat_rooms')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => NULL,
                'type' => 'direct',
                'created_by' => 1,
                'avatar' => NULL,
                'created_at' => '2026-02-16 22:10:43',
                'updated_at' => '2026-02-16 22:10:43',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => NULL,
                'type' => 'direct',
                'created_by' => 1,
                'avatar' => NULL,
                'created_at' => '2026-05-10 04:25:05',
                'updated_at' => '2026-05-10 04:25:05',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => NULL,
                'type' => 'direct',
                'created_by' => 17,
                'avatar' => NULL,
                'created_at' => '2026-05-10 15:00:53',
                'updated_at' => '2026-05-10 15:00:53',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Sales Group',
                'type' => 'group',
                'created_by' => 1,
                'avatar' => NULL,
                'created_at' => '2026-05-10 15:17:19',
                'updated_at' => '2026-05-10 15:17:19',
            ),
        ));
        
        
    }
}