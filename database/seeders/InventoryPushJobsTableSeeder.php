<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryPushJobsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventory_push_jobs')->delete();
        
        \DB::table('inventory_push_jobs')->insert(array (
            0 => 
            array (
                'id' => '019c8de3-f035-71b8-8692-4f2c07debec2',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'ONLYUSEDTESLA',
                'type' => 'one_time',
                'schedule_time' => NULL,
                'file_format' => 'csv',
                'filters' => '{"make":"BMW"}',
                'category_ids' => '["019bdb2e-9645-738b-a832-069c0d0da298"]',
                'sftp_connection_ids' => '["019c8dc4-72d4-71d8-8cc8-58bf510f74b9"]',
                'destination_folder_override' => NULL,
                'is_active' => true,
                'last_run_at' => '2026-02-24 04:44:24',
                'next_run_at' => NULL,
                'created_at' => '2026-02-24 04:24:11',
                'updated_at' => '2026-02-24 04:44:24',
                'custom_filename' => NULL,
            ),
            1 => 
            array (
                'id' => '019c8df8-a701-72f6-8f92-1337179b4045',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'ONLYUSEDTESLA-01',
                'type' => 'one_time',
                'schedule_time' => NULL,
                'file_format' => 'csv',
                'filters' => '{"make":"BMW"}',
                'category_ids' => '["019bdb2e-9645-738b-a832-069c0d0da298"]',
                'sftp_connection_ids' => '["019c8dc4-72d4-71d8-8cc8-58bf510f74b9"]',
                'destination_folder_override' => NULL,
                'is_active' => true,
                'last_run_at' => '2026-02-24 04:48:18',
                'next_run_at' => NULL,
                'created_at' => '2026-02-24 04:46:49',
                'updated_at' => '2026-02-24 04:48:18',
                'custom_filename' => NULL,
            ),
            2 => 
            array (
                'id' => '019c8dfc-fa98-7336-a782-8be430143ef6',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'ONLYUSEDTESLA-02',
                'type' => 'one_time',
                'schedule_time' => NULL,
                'file_format' => 'csv',
                'filters' => NULL,
                'category_ids' => '["019bdb2e-9645-738b-a832-069c0d0da298"]',
                'sftp_connection_ids' => '["019c8dc4-72d4-71d8-8cc8-58bf510f74b9"]',
                'destination_folder_override' => NULL,
                'is_active' => true,
                'last_run_at' => '2026-02-24 04:51:49',
                'next_run_at' => NULL,
                'created_at' => '2026-02-24 04:51:32',
                'updated_at' => '2026-02-24 04:51:49',
                'custom_filename' => 'inventory-test',
            ),
        ));
        
        
    }
}