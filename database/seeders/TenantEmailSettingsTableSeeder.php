<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantEmailSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tenant_email_settings')->delete();
        
        \DB::table('tenant_email_settings')->insert(array (
            0 => 
            array (
                'id' => '019c9da7-7376-704c-b434-8e0a024367e9',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'imap_host' => 'imap.gmail.com',
                'imap_port' => 993,
                'imap_username' => 'abdulhakimaben@gmail.com',
                'imap_password' => 'eyJpdiI6IjVUVE1DKzFXcWJ1VktRZklQbEI2MVE9PSIsInZhbHVlIjoibllrY3B2UExGRlJ2d2R1Nkc4M0Q4R3FvWG9KRVd5RUpLejdGQ202WWk3ND0iLCJtYWMiOiIxZTdjNTcxYzFhNjQyNzlmODIxNmRiMjU2N2RhYzIyZTQ5NWVlNzRiMGY0ZjhjYjE2Nzg3NThhNTQxYjhjMDljIiwidGFnIjoiIn0=',
                'imap_encryption' => 'ssl',
                'is_active' => true,
                'created_at' => '2026-02-27 05:52:02',
                'updated_at' => '2026-02-27 05:52:02',
                'mail_mailer' => 'smtp',
                'mail_host' => NULL,
                'mail_port' => NULL,
                'mail_username' => NULL,
                'mail_password' => NULL,
                'mail_encryption' => NULL,
                'mail_from_address' => NULL,
                'mail_from_name' => NULL,
            ),
        ));
        
        
    }
}