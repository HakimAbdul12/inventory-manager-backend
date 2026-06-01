<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantUserRolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tenant_user_roles')->delete();
        
        \DB::table('tenant_user_roles')->insert(array (
            0 => 
            array (
                'id' => '1a9cdf20-822a-4857-843a-d59a58a2a34f',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'user_id' => 1,
                'tenant_role_id' => '019df04c-d560-7092-9175-98f13d9c791a',
                'assigned_by' => NULL,
                'created_at' => '2026-05-04 00:04:20',
                'updated_at' => '2026-05-04 00:04:20',
            ),
            1 => 
            array (
                'id' => '8486a087-72b9-47ea-8d2f-77825ba1f7d7',
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'user_id' => 3,
                'tenant_role_id' => '019df04c-d6d5-7396-bc8a-0cc7aff5f51c',
                'assigned_by' => NULL,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            2 => 
            array (
                'id' => '89f745f5-17f5-491c-acd1-6e09916e240c',
                'tenant_id' => '019c6e30-cc43-7136-a126-afb904ab66d0',
                'user_id' => 3,
                'tenant_role_id' => '019df04c-d80d-713d-a5fc-c8c1c4c0206c',
                'assigned_by' => NULL,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            3 => 
            array (
                'id' => 'db99b3ef-eb1f-4609-990a-740c43020388',
                'tenant_id' => '019c72ca-2d80-73fe-a06c-bf6e0437e315',
                'user_id' => 6,
                'tenant_role_id' => '019df04c-d92b-7066-9839-0b18f7187031',
                'assigned_by' => NULL,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            4 => 
            array (
                'id' => '327df46b-38ee-46d8-adc2-e1d23a98d32d',
                'tenant_id' => '019c72cc-934e-7363-82e0-fd709fc7ea51',
                'user_id' => 7,
                'tenant_role_id' => '019df04c-da47-718a-8815-a81fdb9e9ccd',
                'assigned_by' => NULL,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            5 => 
            array (
                'id' => '80bc00be-2376-4829-83a1-16ead8a9f10a',
                'tenant_id' => '019c730a-da51-708c-96a5-b45e96cd9166',
                'user_id' => 9,
                'tenant_role_id' => '019df04c-db5a-70bc-8596-81118e87e1df',
                'assigned_by' => NULL,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            6 => 
            array (
                'id' => '2efe1ccc-c944-437b-876e-10c7b15bea4f',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 1,
                'tenant_role_id' => '019df04c-dc7a-730a-89e9-e2495be9a86e',
                'assigned_by' => NULL,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            7 => 
            array (
                'id' => '2f6a6cc6-ed8a-4ca4-a1e0-392e50a5950f',
                'tenant_id' => '019ca263-939a-710a-9654-18b61f121850',
                'user_id' => 10,
                'tenant_role_id' => '019df04c-dd96-70ce-bfc7-d2e8d3893880',
                'assigned_by' => NULL,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            8 => 
            array (
                'id' => '7be49563-080e-4f43-992d-e7863056b9d8',
                'tenant_id' => '019dd9a8-e608-7354-b798-1525a1cf4e10',
                'user_id' => 11,
                'tenant_role_id' => '019df04c-deb0-7253-a15d-0b595e8a7f37',
                'assigned_by' => NULL,
                'created_at' => '2026-05-04 00:04:23',
                'updated_at' => '2026-05-04 00:04:23',
            ),
            9 => 
            array (
                'id' => '019df573-cfee-72ed-afc2-5f2edda7d037',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'user_id' => 14,
                'tenant_role_id' => '019df02b-d95e-7302-8c86-660a268daa7d',
                'assigned_by' => 1,
                'created_at' => '2026-05-05 00:05:01',
                'updated_at' => '2026-05-05 00:05:01',
            ),
            10 => 
            array (
                'id' => '019df576-71e8-70a8-885e-fbdab3c68f1e',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'user_id' => 15,
                'tenant_role_id' => '019df04c-d60d-7103-92c6-e823648fe278',
                'assigned_by' => 1,
                'created_at' => '2026-05-05 00:07:53',
                'updated_at' => '2026-05-05 00:07:53',
            ),
            11 => 
            array (
                'id' => '019dfac2-b492-736d-947b-7cfe5487856e',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'user_id' => 3,
                'tenant_role_id' => '019df02b-d95e-7302-8c86-660a268daa7d',
                'assigned_by' => 1,
                'created_at' => '2026-05-06 00:49:17',
                'updated_at' => '2026-05-06 00:49:17',
            ),
            12 => 
            array (
                'id' => '019e0849-1206-70bd-b3ae-e65cbc7b19e6',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 3,
                'tenant_role_id' => '019dd8ea-ccbc-72cb-b6bd-ca27075e36a6',
                'assigned_by' => 1,
                'created_at' => '2026-05-08 15:51:07',
                'updated_at' => '2026-05-08 15:51:07',
            ),
            13 => 
            array (
                'id' => '019e0fb8-f4b8-70de-a680-039575a20829',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 17,
                'tenant_role_id' => '019dd8ea-ccbc-72cb-b6bd-ca27075e36a6',
                'assigned_by' => 1,
                'created_at' => '2026-05-10 02:30:40',
                'updated_at' => '2026-05-10 02:30:40',
            ),
            14 => 
            array (
                'id' => '019e0fbe-08c4-7388-845c-ff39b039f993',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 18,
                'tenant_role_id' => '019df04c-dd12-7121-a7f0-41bf59b68060',
                'assigned_by' => 1,
                'created_at' => '2026-05-10 02:36:12',
                'updated_at' => '2026-05-10 02:36:12',
            ),
            15 => 
            array (
                'id' => '019e0fc0-084b-71d2-b8a5-797389e1daee',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 19,
                'tenant_role_id' => '019df04c-dd4c-70d0-84f1-2491c75a53aa',
                'assigned_by' => 1,
                'created_at' => '2026-05-10 02:38:23',
                'updated_at' => '2026-05-10 02:38:23',
            ),
            16 => 
            array (
                'id' => '019e0fc0-0854-73bc-9b71-3904dca9b639',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'user_id' => 19,
                'tenant_role_id' => '019df04c-dd6b-73df-baf9-c7f4fe1c5ccc',
                'assigned_by' => 1,
                'created_at' => '2026-05-10 02:38:23',
                'updated_at' => '2026-05-10 02:38:23',
            ),
        ));
        
        
    }
}