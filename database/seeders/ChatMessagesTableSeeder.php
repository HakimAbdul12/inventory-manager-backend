<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChatMessagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('chat_messages')->delete();
        
        \DB::table('chat_messages')->insert(array (
            0 => 
            array (
                'id' => 1,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => 'hi there',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:10:56',
                'updated_at' => '2026-02-16 22:10:56',
            ),
            1 => 
            array (
                'id' => 2,
                'chat_room_id' => 1,
                'sender_id' => 3,
                'body' => 'hi there, how are you',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:11:20',
                'updated_at' => '2026-02-16 22:11:20',
            ),
            2 => 
            array (
                'id' => 3,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => 'I am doing great sir, you',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:25:07',
                'updated_at' => '2026-02-16 22:25:07',
            ),
            3 => 
            array (
                'id' => 4,
                'chat_room_id' => 1,
                'sender_id' => 3,
                'body' => 'I am also doing well',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:26:01',
                'updated_at' => '2026-02-16 22:26:01',
            ),
            4 => 
            array (
                'id' => 5,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => 'Great, I thought we should get to know each other',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:29:41',
                'updated_at' => '2026-02-16 22:29:41',
            ),
            5 => 
            array (
                'id' => 6,
                'chat_room_id' => 1,
                'sender_id' => 3,
                'body' => 'Sounds great to me',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:34:16',
                'updated_at' => '2026-02-16 22:34:16',
            ),
            6 => 
            array (
                'id' => 7,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => 'NIce one',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:34:46',
                'updated_at' => '2026-02-16 22:34:46',
            ),
            7 => 
            array (
                'id' => 8,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => 'So when do you plan on transfering the inventory',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:35:18',
                'updated_at' => '2026-02-16 22:35:18',
            ),
            8 => 
            array (
                'id' => 9,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => 'Perhaps 2pm today',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:38:28',
                'updated_at' => '2026-02-16 22:38:28',
            ),
            9 => 
            array (
                'id' => 10,
                'chat_room_id' => 1,
                'sender_id' => 3,
                'body' => 'sounds good to me',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 22:42:23',
                'updated_at' => '2026-02-16 22:42:23',
            ),
            10 => 
            array (
                'id' => 11,
                'chat_room_id' => 1,
                'sender_id' => 3,
                'body' => '🎤 Voice message',
                'type' => 'voice',
                'metadata' => '{"file_url":"http:\\/\\/localhost:8000\\/storage\\/chat-attachments\\/1\\/lLvWgL4uNXImFYAFlSEbY7n9ImANJVevPPT4vRgD.webm","file_name":"voice-note.webm","file_size":118971,"mime_type":"video\\/webm","duration":7}',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:01:00',
                'updated_at' => '2026-02-16 23:01:00',
            ),
            11 => 
            array (
                'id' => 12,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => 'That is very true',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => 11,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:01:44',
                'updated_at' => '2026-02-16 23:01:44',
            ),
            12 => 
            array (
                'id' => 13,
                'chat_room_id' => 1,
                'sender_id' => 3,
                'body' => '🎤 Voice message',
                'type' => 'voice',
                'metadata' => '{"file_url":"http:\\/\\/localhost:8000\\/storage\\/chat-attachments\\/1\\/fK96z265n3Nwl5kjBfxfeQVp9SlrMM3P9kZoitGQ.webm","file_name":"voice-note.webm","file_size":185203,"mime_type":"video\\/webm","duration":11}',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:02:39',
                'updated_at' => '2026-02-16 23:02:39',
            ),
            13 => 
            array (
                'id' => 14,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => '🎤 Voice message',
                'type' => 'voice',
                'metadata' => '{"file_url":"http:\\/\\/localhost:8000\\/storage\\/chat-attachments\\/1\\/AGpiJRoMKbC1YHyPhFJpYTnnAfNKkyyt7uCVkjWJ.webm","file_name":"voice-note.webm","file_size":123849,"mime_type":"video\\/webm","duration":7}',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:03:50',
                'updated_at' => '2026-02-16 23:03:50',
            ),
            14 => 
            array (
                'id' => 15,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => '🎤 Voice message',
                'type' => 'voice',
                'metadata' => '{"file_url":"http:\\/\\/localhost:8000\\/storage\\/chat-attachments\\/1\\/40C1HEw7IxemcOYPoSdWs16Dyi3gbRdjKump2etr.webm","file_name":"voice-note.webm","file_size":131641,"mime_type":"video\\/webm","duration":8}',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:04:03',
                'updated_at' => '2026-02-16 23:04:03',
            ),
            15 => 
            array (
                'id' => 16,
                'chat_room_id' => 1,
                'sender_id' => 3,
                'body' => 'Funny',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => 15,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:04:39',
                'updated_at' => '2026-02-16 23:04:39',
            ),
            16 => 
            array (
                'id' => 17,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => '📷 Photo',
                'type' => 'image',
                'metadata' => '{"file_url":"http:\\/\\/localhost:8000\\/storage\\/chat-attachments\\/1\\/xL7XvFe6uKbdLVA3nkc0CgFcCqyOZkOwFUdRwbyE.jpg","file_name":"745D3F5F12B7A5EEB6BD12996B2F6B.jpg","file_size":221138,"mime_type":"image\\/jpeg"}',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:08:28',
                'updated_at' => '2026-02-16 23:08:28',
            ),
            17 => 
            array (
                'id' => 18,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => 'cool',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => 17,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:23:41',
                'updated_at' => '2026-02-16 23:23:41',
            ),
            18 => 
            array (
                'id' => 19,
                'chat_room_id' => 1,
                'sender_id' => 3,
                'body' => 'Okay sure',
                'type' => 'text',
                'metadata' => NULL,
                'reply_to_id' => 15,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:32:47',
                'updated_at' => '2026-02-16 23:32:47',
            ),
            19 => 
            array (
                'id' => 20,
                'chat_room_id' => 1,
                'sender_id' => 1,
                'body' => '🎤 Voice message',
                'type' => 'voice',
                'metadata' => '{"file_url":"http:\\/\\/localhost:8000\\/storage\\/chat-attachments\\/1\\/fkzFl8mSNXr4IMMPIveFjyJpAagfLnVZj60vLvOt.webm","file_name":"voice-note.webm","file_size":101439,"mime_type":"video\\/webm","duration":6}',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:39:45',
                'updated_at' => '2026-02-16 23:39:45',
            ),
            20 => 
            array (
                'id' => 21,
                'chat_room_id' => 1,
                'sender_id' => 3,
                'body' => '🎤 Voice message',
                'type' => 'voice',
                'metadata' => '{"file_url":"http:\\/\\/localhost:8000\\/storage\\/chat-attachments\\/1\\/nxDqZ4YxbSDUUurCe6vSgbiplwDdlhktxeDS3z2c.webm","file_name":"voice-note.webm","file_size":249487,"mime_type":"video\\/webm","duration":15}',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-02-16 23:43:32',
                'updated_at' => '2026-02-16 23:43:32',
            ),
            21 => 
            array (
                'id' => 22,
                'chat_room_id' => 2,
                'sender_id' => 1,
                'body' => 'hi',
                'type' => 'text',
                'metadata' => '[]',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-05-10 04:25:06',
                'updated_at' => '2026-05-10 04:25:06',
            ),
            22 => 
            array (
                'id' => 23,
                'chat_room_id' => 3,
                'sender_id' => 17,
                'body' => 'oh hi there?',
                'type' => 'text',
                'metadata' => '[]',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-05-10 15:00:54',
                'updated_at' => '2026-05-10 15:00:54',
            ),
            23 => 
            array (
                'id' => 24,
                'chat_room_id' => 3,
                'sender_id' => 17,
                'body' => 'hi',
                'type' => 'text',
                'metadata' => '[]',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-05-10 15:05:36',
                'updated_at' => '2026-05-10 15:05:36',
            ),
            24 => 
            array (
                'id' => 25,
                'chat_room_id' => 3,
                'sender_id' => 1,
                'body' => 'hi there too',
                'type' => 'text',
                'metadata' => '[]',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-05-10 15:06:23',
                'updated_at' => '2026-05-10 15:06:23',
            ),
            25 => 
            array (
                'id' => 26,
                'chat_room_id' => 3,
                'sender_id' => 1,
                'body' => 'https://www.crazygames.com/',
                'type' => 'text',
                'metadata' => '{"link_previews":[{"url":"https:\\/\\/www.crazygames.com\\/","title":"Crazygames.com","description":null,"image":null}]}',
                'reply_to_id' => NULL,
                'is_pinned' => false,
                'deleted_at' => NULL,
                'created_at' => '2026-05-10 15:22:47',
                'updated_at' => '2026-05-10 15:22:47',
            ),
        ));
        
        
    }
}