<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NotificationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('notifications')->delete();
        
        \DB::table('notifications')->insert(array (
            0 => 
            array (
                'id' => '019e4667-7f9d-71b4-95a1-31ef0d33b645',
                'tenant_id' => NULL,
                'sender_id' => NULL,
                'type' => NULL,
                'title' => 'System Role Template Updated',
                'body' => 'The system role template "Owner" has been updated.',
                'action_url' => NULL,
                'category' => 'system',
                'data' => '{"role_name":"Owner"}',
                'subject_type' => 'App\\Models\\TenantRole',
                'subject_id' => '019dbfb2-8d09-7129-a055-d6d5f8de575e',
                'created_at' => '2026-05-20 17:20:48',
                'updated_at' => '2026-05-20 17:20:48',
            ),
            1 => 
            array (
                'id' => '019e4674-f66d-7292-b3e1-e593b03db53f',
                'tenant_id' => NULL,
                'sender_id' => NULL,
                'type' => NULL,
                'title' => 'Role Permissions Updated',
                'body' => 'The permissions for your role "Owner" have changed. Gained: crm.credit_application.edit.',
                'action_url' => NULL,
                'category' => 'system',
                'data' => '{"role_name":"Owner"}',
                'subject_type' => 'App\\Models\\TenantRole',
                'subject_id' => '019dbfb2-8d09-7129-a055-d6d5f8de575e',
                'created_at' => '2026-05-20 17:35:30',
                'updated_at' => '2026-05-20 17:35:30',
            ),
            2 => 
            array (
                'id' => '019e4b22-9f0b-71af-a77b-f2e28f824a65',
                'tenant_id' => NULL,
                'sender_id' => NULL,
                'type' => NULL,
                'title' => 'Role Permissions Updated',
                'body' => 'The permissions for your role "Owner" have changed. Gained: View Credit Applications, Create Credit Applications, Edit Credit Applications, Send Credit Application Links, Reactivate Credit Applications, Download Credit Application PDF.',
                'action_url' => NULL,
                'category' => 'system',
                'data' => '{"role_name":"Owner"}',
                'subject_type' => 'App\\Models\\TenantRole',
                'subject_id' => '019dbfb2-8d09-7129-a055-d6d5f8de575e',
                'created_at' => '2026-05-21 15:23:40',
                'updated_at' => '2026-05-21 15:23:40',
            ),
            3 => 
            array (
                'id' => '019e4b29-594a-709f-b894-230e988d0656',
                'tenant_id' => NULL,
                'sender_id' => NULL,
                'type' => NULL,
                'title' => 'Role Permissions Updated',
                'body' => 'The permissions for your role "Owner" have changed. Lost: Assign Leads.',
                'action_url' => NULL,
                'category' => 'system',
                'data' => '{"role_name":"Owner"}',
                'subject_type' => 'App\\Models\\TenantRole',
                'subject_id' => '019dbfb2-8d09-7129-a055-d6d5f8de575e',
                'created_at' => '2026-05-21 15:31:01',
                'updated_at' => '2026-05-21 15:31:01',
            ),
            4 => 
            array (
                'id' => '019e4b2e-7548-7265-9e94-7382f543a2b7',
                'tenant_id' => NULL,
                'sender_id' => NULL,
                'type' => NULL,
                'title' => 'Role Permissions Updated',
                'body' => 'The permissions for your role "Owner" have changed. Gained: Assign Leads.',
                'action_url' => NULL,
                'category' => 'system',
                'data' => '{"role_name":"Owner"}',
                'subject_type' => 'App\\Models\\TenantRole',
                'subject_id' => '019dbfb2-8d09-7129-a055-d6d5f8de575e',
                'created_at' => '2026-05-21 15:36:36',
                'updated_at' => '2026-05-21 15:36:36',
            ),
            5 => 
            array (
                'id' => '019e4b33-d06f-736d-86e0-3f06c5afbcbe',
                'tenant_id' => NULL,
                'sender_id' => NULL,
                'type' => NULL,
                'title' => 'Role Permissions Updated',
                'body' => 'The permissions for your role "Owner" have changed. Gained: View Vehicle Leads, Manage Leads, View Deals, Create & Edit Deals.',
                'action_url' => NULL,
                'category' => 'system',
                'data' => '{"role_name":"Owner"}',
                'subject_type' => 'App\\Models\\TenantRole',
                'subject_id' => '019dbfb2-8d09-7129-a055-d6d5f8de575e',
                'created_at' => '2026-05-21 15:42:27',
                'updated_at' => '2026-05-21 15:42:27',
            ),
            6 => 
            array (
                'id' => '019e6f05-870d-7183-a9de-15c2cdd9087e',
                'tenant_id' => NULL,
                'sender_id' => NULL,
                'type' => NULL,
                'title' => 'Role Permissions Updated',
                'body' => 'The permissions for your role "Owner" have changed. Gained: Invite New Members.',
                'action_url' => NULL,
                'category' => 'system',
                'data' => '{"role_name":"Owner"}',
                'subject_type' => 'App\\Models\\TenantRole',
                'subject_id' => '019dbfb2-8d09-7129-a055-d6d5f8de575e',
                'created_at' => '2026-05-28 14:38:13',
                'updated_at' => '2026-05-28 14:38:13',
            ),
        ));
        
        
    }
}