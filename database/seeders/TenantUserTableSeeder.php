<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantUserTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tenant_user')->delete();
        
        \DB::table('tenant_user')->insert(array (
            0 => 
            array (
                'id' => 1,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 1,
                'role' => 'owner',
                'joined_at' => '2026-02-17 22:18:14',
                'created_at' => '2026-02-17 22:18:14',
                'updated_at' => '2026-02-17 22:18:14',
            ),
            1 => 
            array (
                'id' => 2,
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'user_id' => 3,
                'role' => 'owner',
                'joined_at' => '2026-02-17 22:28:07',
                'created_at' => '2026-02-17 22:28:07',
                'updated_at' => '2026-02-17 22:28:07',
            ),
            2 => 
            array (
                'id' => 3,
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'user_id' => 1,
                'role' => 'owner',
                'joined_at' => '2026-02-17 22:32:31',
                'created_at' => '2026-02-17 22:32:31',
                'updated_at' => '2026-02-17 22:32:31',
            ),
            3 => 
            array (
                'id' => 5,
                'tenant_id' => '019c6e30-cc43-7136-a126-afb904ab66d0',
                'user_id' => 3,
                'role' => 'owner',
                'joined_at' => '2026-02-18 00:40:17',
                'created_at' => '2026-02-18 00:40:17',
                'updated_at' => '2026-02-18 00:40:17',
            ),
            4 => 
            array (
                'id' => 6,
                'tenant_id' => '019c72ca-2d80-73fe-a06c-bf6e0437e315',
                'user_id' => 6,
                'role' => 'owner',
                'joined_at' => '2026-02-18 22:06:18',
                'created_at' => '2026-02-18 22:06:18',
                'updated_at' => '2026-02-18 22:06:18',
            ),
            5 => 
            array (
                'id' => 7,
                'tenant_id' => '019c72cc-934e-7363-82e0-fd709fc7ea51',
                'user_id' => 7,
                'role' => 'owner',
                'joined_at' => '2026-02-18 22:08:55',
                'created_at' => '2026-02-18 22:08:55',
                'updated_at' => '2026-02-18 22:08:55',
            ),
            6 => 
            array (
                'id' => 8,
                'tenant_id' => '019c730a-da51-708c-96a5-b45e96cd9166',
                'user_id' => 9,
                'role' => 'owner',
                'joined_at' => '2026-02-18 23:16:57',
                'created_at' => '2026-02-18 23:16:57',
                'updated_at' => '2026-02-18 23:16:57',
            ),
            7 => 
            array (
                'id' => 9,
                'tenant_id' => '019ca263-939a-710a-9654-18b61f121850',
                'user_id' => 10,
                'role' => 'owner',
                'joined_at' => '2026-02-28 03:56:00',
                'created_at' => '2026-02-28 03:56:00',
                'updated_at' => '2026-02-28 03:56:00',
            ),
            8 => 
            array (
                'id' => 10,
                'tenant_id' => '019dd9a8-e608-7354-b798-1525a1cf4e10',
                'user_id' => 11,
                'role' => 'owner',
                'joined_at' => '2026-04-29 14:33:38',
                'created_at' => '2026-04-29 14:33:38',
                'updated_at' => '2026-04-29 14:33:38',
            ),
            9 => 
            array (
                'id' => 13,
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'user_id' => 14,
                'role' => 'viewer',
                'joined_at' => '2026-05-05 00:05:00',
                'created_at' => '2026-05-05 00:05:00',
                'updated_at' => '2026-05-05 00:05:00',
            ),
            10 => 
            array (
                'id' => 14,
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'user_id' => 15,
                'role' => 'viewer',
                'joined_at' => '2026-05-05 00:07:53',
                'created_at' => '2026-05-05 00:07:53',
                'updated_at' => '2026-05-05 00:07:53',
            ),
            11 => 
            array (
                'id' => 18,
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'user_id' => 3,
                'role' => 'viewer',
                'joined_at' => '2026-05-06 01:01:54',
                'created_at' => '2026-05-06 01:01:54',
                'updated_at' => '2026-05-06 01:01:54',
            ),
            12 => 
            array (
                'id' => 19,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 3,
                'role' => 'viewer',
                'joined_at' => '2026-05-08 15:51:07',
                'created_at' => '2026-05-08 15:51:07',
                'updated_at' => '2026-05-08 15:51:07',
            ),
            13 => 
            array (
                'id' => 20,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 17,
                'role' => 'viewer',
                'joined_at' => '2026-05-10 02:30:39',
                'created_at' => '2026-05-10 02:30:39',
                'updated_at' => '2026-05-10 02:30:39',
            ),
            14 => 
            array (
                'id' => 21,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 18,
                'role' => 'viewer',
                'joined_at' => '2026-05-10 02:36:12',
                'created_at' => '2026-05-10 02:36:12',
                'updated_at' => '2026-05-10 02:36:12',
            ),
            15 => 
            array (
                'id' => 22,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 19,
                'role' => 'viewer',
                'joined_at' => '2026-05-10 02:38:23',
                'created_at' => '2026-05-10 02:38:23',
                'updated_at' => '2026-05-10 02:38:23',
            ),
        ));
        
        
    }
}