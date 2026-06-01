<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MessageRecipientsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('message_recipients')->delete();
        
        \DB::table('message_recipients')->insert(array (
            0 => 
            array (
                'id' => 3,
                'message_id' => 1,
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-02-07 01:40:17',
                'updated_at' => '2026-02-07 01:40:17',
                'deleted_at' => NULL,
            ),
            1 => 
            array (
                'id' => 1,
                'message_id' => 1,
                'user_id' => 1,
                'read_at' => '2026-02-07 01:40:52',
                'created_at' => '2026-02-07 01:40:17',
                'updated_at' => '2026-02-07 01:40:52',
                'deleted_at' => NULL,
            ),
            2 => 
            array (
                'id' => 6,
                'message_id' => 2,
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-02-07 01:44:07',
                'updated_at' => '2026-02-07 01:44:07',
                'deleted_at' => NULL,
            ),
            3 => 
            array (
                'id' => 9,
                'message_id' => 5,
                'user_id' => 1,
                'read_at' => NULL,
                'created_at' => '2026-02-07 01:52:10',
                'updated_at' => '2026-02-07 01:52:10',
                'deleted_at' => NULL,
            ),
            4 => 
            array (
                'id' => 11,
                'message_id' => 5,
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-02-07 01:52:10',
                'updated_at' => '2026-02-07 01:52:10',
                'deleted_at' => NULL,
            ),
            5 => 
            array (
                'id' => 12,
                'message_id' => 6,
                'user_id' => 1,
                'read_at' => NULL,
                'created_at' => '2026-02-07 01:55:44',
                'updated_at' => '2026-02-07 01:55:44',
                'deleted_at' => NULL,
            ),
            6 => 
            array (
                'id' => 14,
                'message_id' => 6,
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-02-07 01:55:44',
                'updated_at' => '2026-02-07 01:55:44',
                'deleted_at' => NULL,
            ),
            7 => 
            array (
                'id' => 15,
                'message_id' => 7,
                'user_id' => 1,
                'read_at' => NULL,
                'created_at' => '2026-02-07 02:01:38',
                'updated_at' => '2026-02-07 02:01:38',
                'deleted_at' => NULL,
            ),
            8 => 
            array (
                'id' => 18,
                'message_id' => 8,
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-02-07 02:19:32',
                'updated_at' => '2026-02-07 02:19:32',
                'deleted_at' => NULL,
            ),
            9 => 
            array (
                'id' => 21,
                'message_id' => 9,
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-02-07 02:26:42',
                'updated_at' => '2026-02-07 02:26:42',
                'deleted_at' => NULL,
            ),
            10 => 
            array (
                'id' => 24,
                'message_id' => 10,
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-02-07 02:35:59',
                'updated_at' => '2026-02-07 02:35:59',
                'deleted_at' => NULL,
            ),
            11 => 
            array (
                'id' => 22,
                'message_id' => 10,
                'user_id' => 1,
                'read_at' => '2026-02-07 02:36:48',
                'created_at' => '2026-02-07 02:35:59',
                'updated_at' => '2026-02-07 02:36:51',
                'deleted_at' => '2026-02-07 02:36:51',
            ),
            12 => 
            array (
                'id' => 19,
                'message_id' => 9,
                'user_id' => 1,
                'read_at' => '2026-02-07 02:36:52',
                'created_at' => '2026-02-07 02:26:42',
                'updated_at' => '2026-02-07 02:36:55',
                'deleted_at' => '2026-02-07 02:36:55',
            ),
            13 => 
            array (
                'id' => 16,
                'message_id' => 8,
                'user_id' => 1,
                'read_at' => '2026-02-07 02:36:57',
                'created_at' => '2026-02-07 02:19:32',
                'updated_at' => '2026-02-07 02:37:00',
                'deleted_at' => '2026-02-07 02:37:00',
            ),
            14 => 
            array (
                'id' => 27,
                'message_id' => 11,
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-02-07 02:37:36',
                'updated_at' => '2026-02-07 02:37:36',
                'deleted_at' => NULL,
            ),
            15 => 
            array (
                'id' => 30,
                'message_id' => 12,
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-02-07 02:39:02',
                'updated_at' => '2026-02-07 02:39:02',
                'deleted_at' => NULL,
            ),
            16 => 
            array (
                'id' => 4,
                'message_id' => 2,
                'user_id' => 1,
                'read_at' => '2026-02-07 02:40:10',
                'created_at' => '2026-02-07 01:44:07',
                'updated_at' => '2026-02-07 02:40:10',
                'deleted_at' => NULL,
            ),
            17 => 
            array (
                'id' => 28,
                'message_id' => 12,
                'user_id' => 1,
                'read_at' => '2026-02-15 03:46:51',
                'created_at' => '2026-02-07 02:39:02',
                'updated_at' => '2026-02-15 03:46:51',
                'deleted_at' => NULL,
            ),
            18 => 
            array (
                'id' => 25,
                'message_id' => 11,
                'user_id' => 1,
                'read_at' => '2026-05-21 11:11:26',
                'created_at' => '2026-02-07 02:37:36',
                'updated_at' => '2026-05-21 11:11:26',
                'deleted_at' => NULL,
            ),
        ));
        
        
    }
}