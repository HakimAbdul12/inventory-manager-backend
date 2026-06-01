<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PersonalAccessTokensTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('personal_access_tokens')->delete();
        
        \DB::table('personal_access_tokens')->insert(array (
            0 => 
            array (
                'id' => 2,
                'tokenable_type' => 'App\\Models\\User',
                'tokenable_id' => 1,
                'name' => 'Test key',
                'token' => '1c78dec9c2e02b81dbf5084794c7bae04e72869922300f6475cefff5aa9f2626',
                'abilities' => '["inventory:read"]',
                'last_used_at' => NULL,
                'expires_at' => NULL,
                'created_at' => '2026-02-03 03:11:36',
                'updated_at' => '2026-02-03 03:11:36',
            ),
            1 => 
            array (
                'id' => 7,
                'tokenable_type' => 'App\\Models\\User',
                'tokenable_id' => 1,
                'name' => 'VIT TEST KEY',
                'token' => '231aca988d82f44f7c4033572e8d172d4db82e3a9b274afbdd8177f9658e37b9',
                'abilities' => '["inventory:read"]',
                'last_used_at' => '2026-04-27 17:03:05',
                'expires_at' => NULL,
                'created_at' => '2026-03-25 00:54:02',
                'updated_at' => '2026-04-27 17:03:05',
            ),
            2 => 
            array (
                'id' => 6,
                'tokenable_type' => 'App\\Models\\User',
                'tokenable_id' => 1,
                'name' => 'Dealer Website Token',
                'token' => '0a664e0c9c6c2ca284ac2c2529c1125495077d02fa46c4beedb014ed743ae4f3',
                'abilities' => '["inventory:read"]',
                'last_used_at' => '2026-03-01 18:51:01',
                'expires_at' => NULL,
                'created_at' => '2026-03-01 17:48:55',
                'updated_at' => '2026-03-01 18:51:01',
            ),
            3 => 
            array (
                'id' => 4,
                'tokenable_type' => 'App\\Models\\User',
                'tokenable_id' => 8,
                'name' => 'auth_token',
                'token' => '07defed3ed974345ceb48876366764259c7e7f65af760fa6acf9b47e97134812',
                'abilities' => '["*"]',
                'last_used_at' => NULL,
                'expires_at' => NULL,
                'created_at' => '2026-02-18 23:13:12',
                'updated_at' => '2026-02-18 23:13:12',
            ),
            4 => 
            array (
                'id' => 5,
                'tokenable_type' => 'App\\Models\\User',
                'tokenable_id' => 9,
                'name' => 'auth_token',
                'token' => 'ccb13d61b6d1620a0c527c9b4620f7327c1098f82c20e48c91857e181e6ec79e',
                'abilities' => '["*"]',
                'last_used_at' => NULL,
                'expires_at' => NULL,
                'created_at' => '2026-02-18 23:16:57',
                'updated_at' => '2026-02-18 23:16:57',
            ),
        ));
        
        
    }
}