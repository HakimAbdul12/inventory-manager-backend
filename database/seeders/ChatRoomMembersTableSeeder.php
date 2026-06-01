<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChatRoomMembersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('chat_room_members')->delete();
        
        \DB::table('chat_room_members')->insert(array (
            0 => 
            array (
                'id' => 2,
                'chat_room_id' => 1,
                'user_id' => 3,
                'role' => 'member',
                'last_read_at' => '2026-02-17 00:55:14',
                'joined_at' => '2026-02-16 22:10:43',
                'created_at' => '2026-02-16 22:10:43',
                'updated_at' => '2026-02-17 00:55:14',
                'is_favorite' => false,
                'is_pinned' => false,
            ),
            1 => 
            array (
                'id' => 4,
                'chat_room_id' => 2,
                'user_id' => 19,
                'role' => 'member',
                'last_read_at' => NULL,
                'joined_at' => '2026-05-10 04:25:05',
                'created_at' => '2026-05-10 04:25:05',
                'updated_at' => '2026-05-10 04:25:05',
                'is_favorite' => false,
                'is_pinned' => false,
            ),
            2 => 
            array (
                'id' => 3,
                'chat_room_id' => 2,
                'user_id' => 1,
                'role' => 'member',
                'last_read_at' => '2026-05-10 04:25:06',
                'joined_at' => '2026-05-10 04:25:05',
                'created_at' => '2026-05-10 04:25:05',
                'updated_at' => '2026-05-10 04:25:06',
                'is_favorite' => false,
                'is_pinned' => false,
            ),
            3 => 
            array (
                'id' => 6,
                'chat_room_id' => 3,
                'user_id' => 1,
                'role' => 'member',
                'last_read_at' => '2026-05-11 04:34:56',
                'joined_at' => '2026-05-10 15:00:53',
                'created_at' => '2026-05-10 15:00:53',
                'updated_at' => '2026-05-11 04:34:56',
                'is_favorite' => false,
                'is_pinned' => false,
            ),
            4 => 
            array (
                'id' => 7,
                'chat_room_id' => 4,
                'user_id' => 1,
                'role' => 'admin',
                'last_read_at' => '2026-05-11 04:40:30',
                'joined_at' => '2026-05-10 15:17:19',
                'created_at' => '2026-05-10 15:17:19',
                'updated_at' => '2026-05-11 04:40:30',
                'is_favorite' => false,
                'is_pinned' => false,
            ),
            5 => 
            array (
                'id' => 8,
                'chat_room_id' => 4,
                'user_id' => 17,
                'role' => 'member',
                'last_read_at' => NULL,
                'joined_at' => '2026-05-11 04:40:53',
                'created_at' => '2026-05-11 04:40:53',
                'updated_at' => '2026-05-11 04:40:53',
                'is_favorite' => false,
                'is_pinned' => false,
            ),
            6 => 
            array (
                'id' => 5,
                'chat_room_id' => 3,
                'user_id' => 17,
                'role' => 'member',
                'last_read_at' => '2026-05-11 05:04:17',
                'joined_at' => '2026-05-10 15:00:53',
                'created_at' => '2026-05-10 15:00:53',
                'updated_at' => '2026-05-11 05:04:17',
                'is_favorite' => false,
                'is_pinned' => false,
            ),
            7 => 
            array (
                'id' => 1,
                'chat_room_id' => 1,
                'user_id' => 1,
                'role' => 'member',
                'last_read_at' => '2026-05-13 00:07:21',
                'joined_at' => '2026-02-16 22:10:43',
                'created_at' => '2026-02-16 22:10:43',
                'updated_at' => '2026-05-13 00:07:21',
                'is_favorite' => false,
                'is_pinned' => false,
            ),
        ));
        
        
    }
}