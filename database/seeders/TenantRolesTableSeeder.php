<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantRolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tenant_roles')->delete();
        
        \DB::table('tenant_roles')->insert(array (
            0 => 
            array (
                'id' => '019dbfb2-8d09-7129-a055-d6d5f8de575e',
                'tenant_id' => NULL,
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-04-24 13:34:03',
                'updated_at' => '2026-04-24 13:34:03',
            ),
            1 => 
            array (
                'id' => '019dbfb2-8d4d-71e3-b3b6-5ac32b25d2e2',
                'tenant_id' => NULL,
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-04-24 13:34:03',
                'updated_at' => '2026-04-24 13:34:03',
            ),
            2 => 
            array (
                'id' => '019dbfb2-8d9f-7271-8cf2-921cb19bb074',
                'tenant_id' => NULL,
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-04-24 13:34:03',
                'updated_at' => '2026-04-24 13:34:03',
            ),
            3 => 
            array (
                'id' => '019dbfb2-8e0b-713c-b613-a6d3e5dd06cd',
                'tenant_id' => NULL,
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-04-24 13:34:03',
                'updated_at' => '2026-04-24 13:34:03',
            ),
            4 => 
            array (
                'id' => '019dd8ea-ccbc-72cb-b6bd-ca27075e36a6',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'Content Editor',
                'slug' => 'content-editor',
                'description' => NULL,
                'is_system' => false,
                'level' => 0,
                'created_at' => '2026-04-29 11:05:59',
                'updated_at' => '2026-04-29 11:05:59',
            ),
            5 => 
            array (
                'id' => '019df02b-d95e-7302-8c86-660a268daa7d',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'name' => 'Inventory Editor',
                'slug' => 'inventory-editor',
                'description' => 'Someone who edits our inventory',
                'is_system' => false,
                'level' => 0,
                'created_at' => '2026-05-03 23:28:18',
                'updated_at' => '2026-05-03 23:28:18',
            ),
            6 => 
            array (
                'id' => '019df04c-d560-7092-9175-98f13d9c791a',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-05-04 00:04:20',
                'updated_at' => '2026-05-04 00:04:20',
            ),
            7 => 
            array (
                'id' => '019df04c-d5bc-7386-852a-9b6fa500b629',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-05-04 00:04:20',
                'updated_at' => '2026-05-04 00:04:20',
            ),
            8 => 
            array (
                'id' => '019df04c-d60d-7103-92c6-e823648fe278',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-05-04 00:04:20',
                'updated_at' => '2026-05-04 00:04:20',
            ),
            9 => 
            array (
                'id' => '019df04c-d65b-72b3-8543-007499d20ff9',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-04 00:04:20',
                'updated_at' => '2026-05-04 00:04:20',
            ),
            10 => 
            array (
                'id' => '019df04c-d67f-7298-8a4f-1b26c788f1c6',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-05-04 00:04:20',
                'updated_at' => '2026-05-04 00:04:20',
            ),
            11 => 
            array (
                'id' => '019df04c-d6d5-7396-bc8a-0cc7aff5f51c',
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-05-04 00:04:20',
                'updated_at' => '2026-05-04 00:04:20',
            ),
            12 => 
            array (
                'id' => '019df04c-d72a-70d9-8b51-1552398969f1',
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-05-04 00:04:20',
                'updated_at' => '2026-05-04 00:04:20',
            ),
            13 => 
            array (
                'id' => '019df04c-d778-73a9-a8b6-c113394c1778',
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-05-04 00:04:20',
                'updated_at' => '2026-05-04 00:04:20',
            ),
            14 => 
            array (
                'id' => '019df04c-d7ba-718f-8195-1ea045605203',
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            15 => 
            array (
                'id' => '019df04c-d7df-716d-9932-8a1e17bc3624',
                'tenant_id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            16 => 
            array (
                'id' => '019df04c-d80d-713d-a5fc-c8c1c4c0206c',
                'tenant_id' => '019c6e30-cc43-7136-a126-afb904ab66d0',
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            17 => 
            array (
                'id' => '019df04c-d85d-72f7-9be6-7e7464dc73f3',
                'tenant_id' => '019c6e30-cc43-7136-a126-afb904ab66d0',
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            18 => 
            array (
                'id' => '019df04c-d8a8-704c-b8dc-cadf8cff45ae',
                'tenant_id' => '019c6e30-cc43-7136-a126-afb904ab66d0',
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            19 => 
            array (
                'id' => '019df04c-d8e7-7044-9ff0-e6d75ca66bdc',
                'tenant_id' => '019c6e30-cc43-7136-a126-afb904ab66d0',
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            20 => 
            array (
                'id' => '019df04c-d906-704c-a5ec-89bd340d401b',
                'tenant_id' => '019c6e30-cc43-7136-a126-afb904ab66d0',
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            21 => 
            array (
                'id' => '019df04c-d92b-7066-9839-0b18f7187031',
                'tenant_id' => '019c72ca-2d80-73fe-a06c-bf6e0437e315',
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            22 => 
            array (
                'id' => '019df04c-d975-7224-b513-68e0e6434e2f',
                'tenant_id' => '019c72ca-2d80-73fe-a06c-bf6e0437e315',
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            23 => 
            array (
                'id' => '019df04c-d9c0-7334-9f5f-fff07d9bfa28',
                'tenant_id' => '019c72ca-2d80-73fe-a06c-bf6e0437e315',
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            24 => 
            array (
                'id' => '019df04c-d9ff-72a5-896b-91b0e9fa35e8',
                'tenant_id' => '019c72ca-2d80-73fe-a06c-bf6e0437e315',
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            25 => 
            array (
                'id' => '019df04c-da1e-70f0-a8e4-f83ec8edd3f1',
                'tenant_id' => '019c72ca-2d80-73fe-a06c-bf6e0437e315',
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            26 => 
            array (
                'id' => '019df04c-da47-718a-8815-a81fdb9e9ccd',
                'tenant_id' => '019c72cc-934e-7363-82e0-fd709fc7ea51',
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            27 => 
            array (
                'id' => '019df04c-da91-7019-8284-c88c56c0135a',
                'tenant_id' => '019c72cc-934e-7363-82e0-fd709fc7ea51',
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            28 => 
            array (
                'id' => '019df04c-dad6-7369-84d1-8d6e39a59fb1',
                'tenant_id' => '019c72cc-934e-7363-82e0-fd709fc7ea51',
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            29 => 
            array (
                'id' => '019df04c-db14-7199-a7e9-012c35fac8b1',
                'tenant_id' => '019c72cc-934e-7363-82e0-fd709fc7ea51',
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            30 => 
            array (
                'id' => '019df04c-db32-73b6-9ea4-7974f0bd8ddb',
                'tenant_id' => '019c72cc-934e-7363-82e0-fd709fc7ea51',
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            31 => 
            array (
                'id' => '019df04c-db5a-70bc-8596-81118e87e1df',
                'tenant_id' => '019c730a-da51-708c-96a5-b45e96cd9166',
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-05-04 00:04:21',
                'updated_at' => '2026-05-04 00:04:21',
            ),
            32 => 
            array (
                'id' => '019df04c-dba9-70f5-8519-f0e8c19e121d',
                'tenant_id' => '019c730a-da51-708c-96a5-b45e96cd9166',
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            33 => 
            array (
                'id' => '019df04c-dbf4-72e3-988d-5d27ce5eb793',
                'tenant_id' => '019c730a-da51-708c-96a5-b45e96cd9166',
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            34 => 
            array (
                'id' => '019df04c-dc31-70a2-9733-beb860041c66',
                'tenant_id' => '019c730a-da51-708c-96a5-b45e96cd9166',
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            35 => 
            array (
                'id' => '019df04c-dc50-71a8-b2db-bcd7a3cfc389',
                'tenant_id' => '019c730a-da51-708c-96a5-b45e96cd9166',
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            36 => 
            array (
                'id' => '019df04c-dc7a-730a-89e9-e2495be9a86e',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            37 => 
            array (
                'id' => '019df04c-dccd-73d0-ae6f-b6c94030413f',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            38 => 
            array (
                'id' => '019df04c-dd12-7121-a7f0-41bf59b68060',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            39 => 
            array (
                'id' => '019df04c-dd4c-70d0-84f1-2491c75a53aa',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            40 => 
            array (
                'id' => '019df04c-dd6b-73df-baf9-c7f4fe1c5ccc',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            41 => 
            array (
                'id' => '019df04c-dd96-70ce-bfc7-d2e8d3893880',
                'tenant_id' => '019ca263-939a-710a-9654-18b61f121850',
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            42 => 
            array (
                'id' => '019df04c-dde4-72d9-a9b4-cb612be5f452',
                'tenant_id' => '019ca263-939a-710a-9654-18b61f121850',
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            43 => 
            array (
                'id' => '019df04c-de2a-7212-81c8-36a9bb2ad1e7',
                'tenant_id' => '019ca263-939a-710a-9654-18b61f121850',
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            44 => 
            array (
                'id' => '019df04c-de68-70fd-a3a5-9fe085dec76b',
                'tenant_id' => '019ca263-939a-710a-9654-18b61f121850',
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            45 => 
            array (
                'id' => '019df04c-de88-709a-b92a-0da19025ad5f',
                'tenant_id' => '019ca263-939a-710a-9654-18b61f121850',
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            46 => 
            array (
                'id' => '019df04c-deb0-7253-a15d-0b595e8a7f37',
                'tenant_id' => '019dd9a8-e608-7354-b798-1525a1cf4e10',
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Full access to all workspace features and settings.',
                'is_system' => true,
                'level' => 50,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            47 => 
            array (
                'id' => '019df04c-def9-702d-9bbb-f17b32f19543',
                'tenant_id' => '019dd9a8-e608-7354-b798-1525a1cf4e10',
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full access to inventory, users, and settings. Cannot manage roles.',
                'is_system' => true,
                'level' => 40,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            48 => 
            array (
                'id' => '019df04c-df3a-7192-94d9-c87cbb825ccf',
                'tenant_id' => '019dd9a8-e608-7354-b798-1525a1cf4e10',
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage inventory, publish, and view activity logs. Cannot manage users or settings.',
                'is_system' => true,
                'level' => 30,
                'created_at' => '2026-05-04 00:04:22',
                'updated_at' => '2026-05-04 00:04:22',
            ),
            49 => 
            array (
                'id' => '019df04c-df7a-71cb-b1f2-22308f99a4b4',
                'tenant_id' => '019dd9a8-e608-7354-b798-1525a1cf4e10',
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-04 00:04:23',
                'updated_at' => '2026-05-04 00:04:23',
            ),
            50 => 
            array (
                'id' => '019df04c-df99-701d-a799-9dfd96eba637',
                'tenant_id' => '019dd9a8-e608-7354-b798-1525a1cf4e10',
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to inventory.',
                'is_system' => true,
                'level' => 10,
                'created_at' => '2026-05-04 00:04:23',
                'updated_at' => '2026-05-04 00:04:23',
            ),
            51 => 
            array (
                'id' => '019e001b-e588-7254-86e6-c492d6f366a9',
                'tenant_id' => NULL,
                'name' => 'Clerk',
                'slug' => 'clerk',
                'description' => 'Can create and edit inventory, upload media, but cannot publish, delete, or change pricing.',
                'is_system' => true,
                'level' => 20,
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
            ),
            52 => 
            array (
                'id' => '019e0f4c-db08-734a-b2d4-34b543688c11',
                'tenant_id' => NULL,
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Full platform access including all system administration.',
                'is_system' => true,
                'level' => 100,
                'created_at' => '2026-05-10 00:32:35',
                'updated_at' => '2026-05-10 00:32:35',
            ),
        ));
        
        
    }
}