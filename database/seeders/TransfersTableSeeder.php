<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TransfersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('transfers')->delete();
        
        \DB::table('transfers')->insert(array (
            0 => 
            array (
                'id' => '019c2bd0-e667-73c8-8cbf-8cf81c05cf9a',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'completed',
                'created_at' => '2026-02-05 03:20:36',
                'updated_at' => '2026-02-18 08:12:29',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            1 => 
            array (
                'id' => '019c2beb-9542-7185-a47e-abea62ca77ae',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'completed',
                'created_at' => '2026-02-05 03:49:45',
                'updated_at' => '2026-02-18 08:12:29',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            2 => 
            array (
                'id' => '019c29d6-5499-7323-b10c-3b6550f04b4d',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'declined',
                'created_at' => '2026-02-04 18:07:18',
                'updated_at' => '2026-02-04 18:19:16',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            3 => 
            array (
                'id' => '019c29d6-a3a8-70f6-a4fc-79cd70b207c3',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'declined',
                'created_at' => '2026-02-04 18:07:38',
                'updated_at' => '2026-02-04 19:59:05',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            4 => 
            array (
                'id' => '019c2a3d-fa6a-72e5-93ea-7cda9d949283',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'declined',
                'created_at' => '2026-02-04 20:00:30',
                'updated_at' => '2026-02-04 20:06:54',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            5 => 
            array (
                'id' => '019c2a3e-4ecd-71b4-b070-028177952a59',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'cancelled',
                'created_at' => '2026-02-04 20:00:52',
                'updated_at' => '2026-02-04 20:11:38',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            6 => 
            array (
                'id' => '019c2a49-baf1-7057-842a-757c94fbd770',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'cancelled',
                'created_at' => '2026-02-04 20:13:21',
                'updated_at' => '2026-02-04 20:15:25',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            7 => 
            array (
                'id' => '019c2a4f-f1d5-7034-adbd-5413c7893d54',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'declined',
                'created_at' => '2026-02-04 20:20:08',
                'updated_at' => '2026-02-04 20:21:33',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            8 => 
            array (
                'id' => '019c2a50-4968-70e8-b4e3-10364d9eeda4',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'declined',
                'created_at' => '2026-02-04 20:20:30',
                'updated_at' => '2026-02-04 20:21:52',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            9 => 
            array (
                'id' => '019c2a50-717c-73cc-8f36-f3a797fc904e',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'declined',
                'created_at' => '2026-02-04 20:20:40',
                'updated_at' => '2026-02-04 20:24:42',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            10 => 
            array (
                'id' => '019c2a50-93d4-7379-9fd6-ef383a7bfad9',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'cancelled',
                'created_at' => '2026-02-04 20:20:49',
                'updated_at' => '2026-02-05 03:24:47',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            11 => 
            array (
                'id' => '019c2bd0-0820-7316-81f4-319a8615f429',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'cancelled',
                'created_at' => '2026-02-05 03:19:39',
                'updated_at' => '2026-02-05 03:31:39',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            12 => 
            array (
                'id' => '019c2bd0-4488-72e5-973b-c825afd0df5e',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'cancelled',
                'created_at' => '2026-02-05 03:19:55',
                'updated_at' => '2026-02-05 03:32:41',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            13 => 
            array (
                'id' => '019c2bd0-5d92-723e-8157-25f53814cabe',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'cancelled',
                'created_at' => '2026-02-05 03:20:01',
                'updated_at' => '2026-02-05 03:44:32',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            14 => 
            array (
                'id' => '019c2bd0-7e4d-718e-92c7-5a8a52e05499',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'cancelled',
                'created_at' => '2026-02-05 03:20:10',
                'updated_at' => '2026-02-05 03:44:46',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            15 => 
            array (
                'id' => '019c2bd0-ce18-70ec-925e-8dc760c24113',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'declined',
                'created_at' => '2026-02-05 03:20:30',
                'updated_at' => '2026-02-05 03:44:56',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            16 => 
            array (
                'id' => '019c2beb-1cad-73a2-a550-5d588c472bd1',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'pending',
                'created_at' => '2026-02-05 03:49:14',
                'updated_at' => '2026-02-05 03:49:14',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            17 => 
            array (
                'id' => '019c2beb-7997-70b5-b7e8-c60b69e32b1f',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'declined',
                'created_at' => '2026-02-05 03:49:38',
                'updated_at' => '2026-02-05 03:54:36',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            18 => 
            array (
                'id' => '019c2beb-486a-70db-927a-ee95fc51067c',
                'sender_id' => 1,
                'recipient_id' => 3,
                'inventory_ids' => '["019c20b4-1f40-7365-b248-e58b7ddf1efc"]',
                'type' => 'duplicate',
                'status' => 'completed',
                'created_at' => '2026-02-05 03:49:25',
                'updated_at' => '2026-02-05 03:58:51',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
        ));
        
        
    }
}