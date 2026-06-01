<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryPublishingStatusesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventory_publishing_statuses')->delete();
        
        \DB::table('inventory_publishing_statuses')->insert(array (
            0 => 
            array (
                'id' => '019e00fd-4161-71eb-a095-3e8308f2bc3e',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c3611-9b67-7241-9179-cc992c48fcee',
                'platform_name' => 'facebook',
                'status' => 'removed',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:50:57',
                'updated_at' => '2026-05-07 05:50:57',
            ),
            1 => 
            array (
                'id' => '019e00fd-4186-7061-8e4e-da27b9013239',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c3611-9b67-7241-9179-cc992c48fcee',
                'platform_name' => 'autotrader',
                'status' => 'synced',
                'last_sync_at' => '2026-05-05 13:50:57',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:50:57',
                'updated_at' => '2026-05-07 05:50:57',
            ),
            2 => 
            array (
                'id' => '019e00fd-417d-70b5-89fd-1548867837ee',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c3611-9b67-7241-9179-cc992c48fcee',
                'platform_name' => 'instagram',
                'status' => 'synced',
                'last_sync_at' => '2026-05-06 03:52:29',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:50:57',
                'updated_at' => '2026-05-07 05:52:29',
            ),
            3 => 
            array (
                'id' => '019e00fd-418f-71a7-ba25-21e9eca207f3',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c3611-9b67-7241-9179-cc992c48fcee',
                'platform_name' => 'cars_com',
                'status' => 'synced',
                'last_sync_at' => '2026-05-05 22:52:29',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:50:57',
                'updated_at' => '2026-05-07 05:52:29',
            ),
            4 => 
            array (
                'id' => '019e00fe-a8e0-72a8-818e-6d2ad58a0a7e',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'inventory_item_id' => '019df137-0281-7151-9b75-9c19e571c119',
                'platform_name' => 'facebook',
                'status' => 'removed',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:29',
                'updated_at' => '2026-05-07 05:52:29',
            ),
            5 => 
            array (
                'id' => '019e00fe-a8ec-71cc-b0e4-c1b98bf77ad4',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'inventory_item_id' => '019df137-0281-7151-9b75-9c19e571c119',
                'platform_name' => 'tiktok',
                'status' => 'synced',
                'last_sync_at' => '2026-05-05 23:52:29',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:29',
                'updated_at' => '2026-05-07 05:52:29',
            ),
            6 => 
            array (
                'id' => '019e00fe-a907-7165-aeae-de4d8040fe12',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c358a-3c11-705f-bb5a-826c3517bb21',
                'platform_name' => 'instagram',
                'status' => 'removed',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:29',
                'updated_at' => '2026-05-07 05:52:29',
            ),
            7 => 
            array (
                'id' => '019e00fe-a910-7170-be1a-8ae43914af15',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c358a-3c11-705f-bb5a-826c3517bb21',
                'platform_name' => 'tiktok',
                'status' => 'synced',
                'last_sync_at' => '2026-05-06 03:52:29',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:29',
                'updated_at' => '2026-05-07 05:52:29',
            ),
            8 => 
            array (
                'id' => '019e00fe-a919-70a4-8bd4-70d99082f8c8',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c358a-3c11-705f-bb5a-826c3517bb21',
                'platform_name' => 'cargurus',
                'status' => 'pending',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:29',
                'updated_at' => '2026-05-07 05:52:29',
            ),
            9 => 
            array (
                'id' => '019e00fe-a921-70e8-a4ba-245eb0572a83',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c358a-3c11-705f-bb5a-826c3517bb21',
                'platform_name' => 'cars_com',
                'status' => 'pending',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:29',
                'updated_at' => '2026-05-07 05:52:29',
            ),
            10 => 
            array (
                'id' => '019e00fe-a929-70a4-adcc-8cf5dcc142fd',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca7f-71cf-8e61-55a2ef809750',
                'platform_name' => 'facebook',
                'status' => 'synced',
                'last_sync_at' => '2026-05-05 18:52:29',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:29',
                'updated_at' => '2026-05-07 05:52:29',
            ),
            11 => 
            array (
                'id' => '019e00fe-a932-70c2-bc85-a7b9c0c07b7b',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca7f-71cf-8e61-55a2ef809750',
                'platform_name' => 'instagram',
                'status' => 'synced',
                'last_sync_at' => '2026-05-07 00:52:29',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            12 => 
            array (
                'id' => '019e00fe-a93c-70b8-9320-d256f1b9c5fc',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca7f-71cf-8e61-55a2ef809750',
                'platform_name' => 'tiktok',
                'status' => 'error',
                'last_sync_at' => NULL,
                'error_message' => 'Connection timeout with platform API.',
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            13 => 
            array (
                'id' => '019e00fe-a944-7066-abdb-ec92c18d753e',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca7f-71cf-8e61-55a2ef809750',
                'platform_name' => 'autotrader',
                'status' => 'removed',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            14 => 
            array (
                'id' => '019e00fe-a94b-7233-a569-2b7493789e90',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca7f-71cf-8e61-55a2ef809750',
                'platform_name' => 'cars_com',
                'status' => 'synced',
                'last_sync_at' => '2026-05-05 16:52:30',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            15 => 
            array (
                'id' => '019e00fe-a953-7278-96f2-76bffaf412a2',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c20b4-1f40-7365-b248-e58b7ddf1efc',
                'platform_name' => 'instagram',
                'status' => 'synced',
                'last_sync_at' => '2026-05-05 07:52:30',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            16 => 
            array (
                'id' => '019e00fe-a95b-7130-8378-93bcb23a0386',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c20b4-1f40-7365-b248-e58b7ddf1efc',
                'platform_name' => 'autotrader',
                'status' => 'pending',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            17 => 
            array (
                'id' => '019e00fe-a964-72c2-8c6b-396203e5bf5a',
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'inventory_item_id' => '019c2bf3-e8fe-7245-b172-da0cfab66ac2',
                'platform_name' => 'facebook',
                'status' => 'synced',
                'last_sync_at' => '2026-05-05 11:52:30',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            18 => 
            array (
                'id' => '019e00fe-a96d-72d2-b07c-d405dab562ce',
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'inventory_item_id' => '019c2bf3-e8fe-7245-b172-da0cfab66ac2',
                'platform_name' => 'tiktok',
                'status' => 'synced',
                'last_sync_at' => '2026-05-05 13:52:30',
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            19 => 
            array (
                'id' => '019e00fe-a974-71d8-baa2-af158b5299b2',
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'inventory_item_id' => '019c2bf3-e8fe-7245-b172-da0cfab66ac2',
                'platform_name' => 'autotrader',
                'status' => 'pending',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            20 => 
            array (
                'id' => '019e00fe-a97d-72d7-85e2-e8c1e97e7380',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'inventory_item_id' => '019df143-91e4-7304-90b4-2b4aa1a182b5',
                'platform_name' => 'instagram',
                'status' => 'pending',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            21 => 
            array (
                'id' => '019e00fe-a984-707a-8773-7838e5c6884b',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'inventory_item_id' => '019df143-91e4-7304-90b4-2b4aa1a182b5',
                'platform_name' => 'autotrader',
                'status' => 'removed',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            22 => 
            array (
                'id' => '019e00fe-a98c-7056-a1a7-5d37c78a182e',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019dfd0d-826c-7082-be59-f116a416d712',
                'platform_name' => 'instagram',
                'status' => 'pending',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            23 => 
            array (
                'id' => '019e00fe-a994-736a-9a46-e43424a71c48',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019dfd0d-826c-7082-be59-f116a416d712',
                'platform_name' => 'tiktok',
                'status' => 'error',
                'last_sync_at' => NULL,
                'error_message' => 'Connection timeout with platform API.',
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            24 => 
            array (
                'id' => '019e00fe-a99b-71e2-b13e-3c4ad473ff26',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019dfd0d-826c-7082-be59-f116a416d712',
                'platform_name' => 'autotrader',
                'status' => 'error',
                'last_sync_at' => NULL,
                'error_message' => 'Connection timeout with platform API.',
                'created_at' => '2026-05-07 05:52:30',
                'updated_at' => '2026-05-07 05:52:30',
            ),
            25 => 
            array (
                'id' => '019e0102-6df3-738d-8358-fa32f4e52e53',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'platform_name' => 'facebook',
                'status' => 'error',
                'last_sync_at' => NULL,
                'error_message' => 'Connection timeout with platform API.',
                'created_at' => '2026-05-07 05:56:36',
                'updated_at' => '2026-05-07 05:56:36',
            ),
            26 => 
            array (
                'id' => '019e00fe-a8f4-704d-bd40-aa12a24602b9',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'platform_name' => 'instagram',
                'status' => 'error',
                'last_sync_at' => NULL,
                'error_message' => 'Connection timeout with platform API.',
                'created_at' => '2026-05-07 05:52:29',
                'updated_at' => '2026-05-07 05:56:37',
            ),
            27 => 
            array (
                'id' => '019e0102-6e22-7362-967d-edddba591bcc',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'platform_name' => 'tiktok',
                'status' => 'pending',
                'last_sync_at' => NULL,
                'error_message' => NULL,
                'created_at' => '2026-05-07 05:56:37',
                'updated_at' => '2026-05-07 05:56:37',
            ),
            28 => 
            array (
                'id' => '019e0102-6e2b-739a-a461-7a6a6f5cda21',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'platform_name' => 'autotrader',
                'status' => 'error',
                'last_sync_at' => NULL,
                'error_message' => 'Connection timeout with platform API.',
                'created_at' => '2026-05-07 05:56:37',
                'updated_at' => '2026-05-07 05:56:37',
            ),
            29 => 
            array (
                'id' => '019e00fe-a8fc-704b-b406-9c9a06539bef',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'platform_name' => 'cargurus',
                'status' => 'error',
                'last_sync_at' => NULL,
                'error_message' => 'Connection timeout with platform API.',
                'created_at' => '2026-05-07 05:52:29',
                'updated_at' => '2026-05-07 05:56:37',
            ),
        ));
        
        
    }
}