<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantInvitationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tenant_invitations')->delete();
        
        \DB::table('tenant_invitations')->insert(array (
            0 => 
            array (
                'id' => '019df8ae-393e-734c-a578-97378f94461e',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'email' => 'hakim.abdul.tech@gmail.com',
                'name' => NULL,
                'role_ids' => '["019df04c-d65b-72b3-8543-007499d20ff9"]',
                'token' => 'rqKbmt8R5t0evdpDOD8Ft9e6IY7PSi77fX5wgxUs',
                'invited_by' => 1,
                'accepted_at' => '2026-05-05 15:35:23',
                'expires_at' => '2026-05-12 15:07:40',
                'created_at' => '2026-05-05 15:07:40',
                'updated_at' => '2026-05-05 15:35:23',
            ),
            1 => 
            array (
                'id' => '019dfabd-4b5a-73b9-8984-bb332464dc6a',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'email' => 'hakim.abdul.tech@gmail.com',
                'name' => NULL,
                'role_ids' => '["019df02b-d95e-7302-8c86-660a268daa7d"]',
                'token' => 'IHwkIb36YRUcmqZNlSz8SDdZjHNyRTQpxaVKs3mw',
                'invited_by' => 1,
                'accepted_at' => '2026-05-06 00:49:17',
                'expires_at' => '2026-05-13 00:43:22',
                'created_at' => '2026-05-06 00:43:22',
                'updated_at' => '2026-05-06 00:49:17',
            ),
            2 => 
            array (
                'id' => '019dfac9-5ff4-7305-96e1-c6e23952b563',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'email' => 'hakim.addul.tech@gmail.com',
                'name' => NULL,
                'role_ids' => '["019df02b-d95e-7302-8c86-660a268daa7d"]',
                'token' => 'FczyURKvIpmWambKrpEiI37XEJRqB5WKXPk5czdo',
                'invited_by' => 1,
                'accepted_at' => NULL,
                'expires_at' => '2026-05-13 00:56:34',
                'created_at' => '2026-05-06 00:56:34',
                'updated_at' => '2026-05-06 00:56:34',
            ),
            3 => 
            array (
                'id' => '019dfaca-a463-70ef-aa3d-3cea275cc754',
                'tenant_id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'email' => 'hakim.abdul.tech@gmail.com',
                'name' => NULL,
                'role_ids' => '["019df02b-d95e-7302-8c86-660a268daa7d"]',
                'token' => 'P0mUTup1lAdRaKLGyst3QRfWooI43seu8uPsNXHB',
                'invited_by' => 1,
                'accepted_at' => '2026-05-06 01:01:54',
                'expires_at' => '2026-05-13 00:57:57',
                'created_at' => '2026-05-06 00:57:57',
                'updated_at' => '2026-05-06 01:01:54',
            ),
            4 => 
            array (
                'id' => '019e0846-5f49-72c3-9a67-813386d0d0e7',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'email' => 'hakim.abdul.tech@gmail.com',
                'name' => NULL,
                'role_ids' => '["019dd8ea-ccbc-72cb-b6bd-ca27075e36a6"]',
                'token' => 'SkCmnNTxt34OFevbmJivqXGHBxnLB1ixAbmxybqp',
                'invited_by' => 1,
                'accepted_at' => '2026-05-08 15:51:07',
                'expires_at' => '2026-05-15 15:48:10',
                'created_at' => '2026-05-08 15:48:10',
                'updated_at' => '2026-05-08 15:51:07',
            ),
            5 => 
            array (
                'id' => '019e0fb5-9576-7232-b4bf-6c0f6469f58e',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'email' => 'test.member.01@gmail.com',
                'name' => NULL,
                'role_ids' => '["019dd8ea-ccbc-72cb-b6bd-ca27075e36a6"]',
                'token' => 'nv4YsRB810gGxrXKvWmQohN4TSZcWYEoLXxWiVsR',
                'invited_by' => 1,
                'accepted_at' => NULL,
                'expires_at' => '2026-05-17 02:26:59',
                'created_at' => '2026-05-10 02:26:59',
                'updated_at' => '2026-05-10 02:26:59',
            ),
        ));
        
        
    }
}