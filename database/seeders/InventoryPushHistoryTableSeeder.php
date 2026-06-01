<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryPushHistoryTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventory_push_history')->delete();
        
        \DB::table('inventory_push_history')->insert(array (
            0 => 
            array (
                'id' => 1,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'push_job_id' => NULL,
                'triggered_by' => '1',
                'file_format' => 'csv',
                'total_records' => 0,
                'status' => 'success',
                'error_message' => 'No matching inventory items found.',
                'started_at' => '2026-02-24 04:11:07',
                'completed_at' => '2026-02-24 04:11:07',
                'execution_time_ms' => 257,
                'file_name' => NULL,
                'target_connections' => '["019c8dc4-72d4-71d8-8cc8-58bf510f74b9"]',
                'connection_results' => NULL,
                'created_at' => '2026-02-24 04:11:07',
                'updated_at' => '2026-02-24 04:11:07',
            ),
            1 => 
            array (
                'id' => 2,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'push_job_id' => '019c8de3-f035-71b8-8692-4f2c07debec2',
                'triggered_by' => '1',
                'file_format' => 'csv',
                'total_records' => 0,
                'status' => 'failed',
                'error_message' => 'Array to string conversion',
                'started_at' => '2026-02-24 04:24:22',
                'completed_at' => '2026-02-24 04:24:22',
                'execution_time_ms' => 909,
                'file_name' => NULL,
                'target_connections' => '["019c8dc4-72d4-71d8-8cc8-58bf510f74b9"]',
                'connection_results' => NULL,
                'created_at' => '2026-02-24 04:24:22',
                'updated_at' => '2026-02-24 04:24:22',
            ),
            2 => 
            array (
                'id' => 3,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'push_job_id' => '019c8de3-f035-71b8-8692-4f2c07debec2',
                'triggered_by' => '1',
                'file_format' => 'csv',
                'total_records' => 4,
                'status' => 'success',
                'error_message' => NULL,
                'started_at' => '2026-02-24 04:29:45',
                'completed_at' => '2026-02-24 04:29:53',
                'execution_time_ms' => 8224,
                'file_name' => 'inventory_20260224_042945_699d293907124.csv',
                'target_connections' => '["019c8dc4-72d4-71d8-8cc8-58bf510f74b9"]',
                'connection_results' => '[{"connection_id":"019c8dc4-72d4-71d8-8cc8-58bf510f74b9","connection_name":"ONLYUSEDTESLA","host":"feed-uploads.onlyusedtesla.com","remote_path":"\\/home\\/hakim\\/hakim\\/inventory_20260224_042945_699d293907124.csv","success":true,"message":"File uploaded successfully to feed-uploads.onlyusedtesla.com:\\/home\\/hakim\\/hakim\\/inventory_20260224_042945_699d293907124.csv"}]',
                'created_at' => '2026-02-24 04:29:45',
                'updated_at' => '2026-02-24 04:29:53',
            ),
            3 => 
            array (
                'id' => 4,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'push_job_id' => '019c8de3-f035-71b8-8692-4f2c07debec2',
                'triggered_by' => '1',
                'file_format' => 'csv',
                'total_records' => 4,
                'status' => 'success',
                'error_message' => NULL,
                'started_at' => '2026-02-24 04:44:18',
                'completed_at' => '2026-02-24 04:44:24',
                'execution_time_ms' => 6523,
                'file_name' => 'inventory_20260224_044418_699d2ca22436a.csv',
                'target_connections' => '["019c8dc4-72d4-71d8-8cc8-58bf510f74b9"]',
                'connection_results' => '[{"connection_id":"019c8dc4-72d4-71d8-8cc8-58bf510f74b9","connection_name":"ONLYUSEDTESLA","host":"feed-uploads.onlyusedtesla.com","remote_path":"\\/home\\/hakim\\/hakim\\/inventory_20260224_044418_699d2ca22436a.csv","success":true,"message":"File uploaded successfully to feed-uploads.onlyusedtesla.com:\\/home\\/hakim\\/hakim\\/inventory_20260224_044418_699d2ca22436a.csv"}]',
                'created_at' => '2026-02-24 04:44:18',
                'updated_at' => '2026-02-24 04:44:24',
            ),
            4 => 
            array (
                'id' => 5,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'push_job_id' => '019c8df8-a701-72f6-8f92-1337179b4045',
                'triggered_by' => '1',
                'file_format' => 'csv',
                'total_records' => 4,
                'status' => 'success',
                'error_message' => NULL,
                'started_at' => '2026-02-24 04:48:13',
                'completed_at' => '2026-02-24 04:48:18',
                'execution_time_ms' => 5916,
                'file_name' => 'inventory_20260224_044813_699d2d8dbbc6f.csv',
                'target_connections' => '["019c8dc4-72d4-71d8-8cc8-58bf510f74b9"]',
                'connection_results' => '[{"connection_id":"019c8dc4-72d4-71d8-8cc8-58bf510f74b9","connection_name":"ONLYUSEDTESLA","host":"feed-uploads.onlyusedtesla.com","remote_path":"inventory_20260224_044813_699d2d8dbbc6f.csv","success":true,"message":"File uploaded successfully to feed-uploads.onlyusedtesla.com:inventory_20260224_044813_699d2d8dbbc6f.csv"}]',
                'created_at' => '2026-02-24 04:48:13',
                'updated_at' => '2026-02-24 04:48:18',
            ),
            5 => 
            array (
                'id' => 6,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'push_job_id' => '019c8dfc-fa98-7336-a782-8be430143ef6',
                'triggered_by' => '1',
                'file_format' => 'csv',
                'total_records' => 5,
                'status' => 'success',
                'error_message' => NULL,
                'started_at' => '2026-02-24 04:51:43',
                'completed_at' => '2026-02-24 04:51:49',
                'execution_time_ms' => 6861,
                'file_name' => 'inventory_20260224_045143_699d2e5f80e02.csv',
                'target_connections' => '["019c8dc4-72d4-71d8-8cc8-58bf510f74b9"]',
                'connection_results' => '[{"connection_id":"019c8dc4-72d4-71d8-8cc8-58bf510f74b9","connection_name":"ONLYUSEDTESLA","host":"feed-uploads.onlyusedtesla.com","remote_path":"inventory-test.csv","success":true,"message":"File uploaded successfully to feed-uploads.onlyusedtesla.com:inventory-test.csv"}]',
                'created_at' => '2026-02-24 04:51:43',
                'updated_at' => '2026-02-24 04:51:49',
            ),
        ));
        
        
    }
}