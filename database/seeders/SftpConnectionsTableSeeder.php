<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SftpConnectionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('sftp_connections')->delete();
        
        \DB::table('sftp_connections')->insert(array (
            0 => 
            array (
                'id' => '019c8dc4-72d4-71d8-8cc8-58bf510f74b9',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'ONLYUSEDTESLA',
                'host' => 'feed-uploads.onlyusedtesla.com',
                'port' => 22,
                'username' => 'hakim',
                'encrypted_password' => 'eyJpdiI6Im9HcjM1VGpmNk1CM29OT3Izb2NJdEE9PSIsInZhbHVlIjoiZkFwbjV0TitMUFRGMUdOSVNrZk1BZz09IiwibWFjIjoiYTViYmNhMTU5NTQ1YTA3YWE5NGE3MmMzOWIyMzQ2ZDAxNGM1ZGJiNTM4MDEwYTJlNGFmY2U1MzA1ZDBiZTYzYiIsInRhZyI6IiJ9',
                'encrypted_private_key' => NULL,
                'auth_type' => 'password',
                'default_remote_path' => '/home/hakim/hakim',
                'is_active' => true,
                'last_tested_at' => '2026-02-24 04:09:17',
                'last_test_status' => 'success',
                'last_test_message' => 'Connection successful. Read and write access verified.',
                'created_at' => '2026-02-24 03:49:47',
                'updated_at' => '2026-02-24 04:09:17',
            ),
        ));
        
        
    }
}