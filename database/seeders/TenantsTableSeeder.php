<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tenants')->delete();
        
        \DB::table('tenants')->insert(array (
            0 => 
            array (
                'id' => '019c6dbb-d032-724b-863a-a92b1a3016da',
                'name' => 'Premium Autos',
                'slug' => 'premium-autos-aanukz',
                'logo' => NULL,
                'owner_id' => 1,
                'settings' => '[]',
                'created_at' => '2026-02-17 22:32:31',
                'updated_at' => '2026-02-17 22:32:31',
                'banner_image' => NULL,
                'description' => NULL,
            ),
            1 => 
            array (
                'id' => '019c6db7-c933-73af-8b20-abf5de5cc84a',
                'name' => 'Jojo Autos',
                'slug' => 'jojo-autos',
                'logo' => 'tenant-logos/vfnRJ1qZ9nmV8SXWdSte7VY5vF5LD46hLPbpvxXb.jpg',
                'owner_id' => 3,
                'settings' => NULL,
                'created_at' => '2026-02-17 22:28:07',
                'updated_at' => '2026-02-17 23:32:59',
                'banner_image' => 'tenant-banners/uDq1dMq4FEovqBRqRX87ydCJRZt4RDnbU01qitXf.jpg',
                'description' => NULL,
            ),
            2 => 
            array (
                'id' => '019c6e30-cc43-7136-a126-afb904ab66d0',
                'name' => 'Downtown Autos',
                'slug' => 'downtown-autos-iuqdne',
                'logo' => 'tenant-logos/j14pKt8A8zJL694Bb6Naifvn7bBvD7ehcEOhEpXg.webp',
                'owner_id' => 3,
                'settings' => '[]',
                'created_at' => '2026-02-18 00:40:17',
                'updated_at' => '2026-02-18 00:42:11',
                'banner_image' => NULL,
                'description' => NULL,
            ),
            3 => 
            array (
                'id' => '019c72ca-2d80-73fe-a06c-bf6e0437e315',
                'name' => 'Test Google User\'s Workspace',
                'slug' => 'test-google-users-workspace-gurpbz',
                'logo' => NULL,
                'owner_id' => 6,
                'settings' => '[]',
                'created_at' => '2026-02-18 22:06:18',
                'updated_at' => '2026-02-18 22:06:18',
                'banner_image' => NULL,
                'description' => NULL,
            ),
            4 => 
            array (
                'id' => '019c72cc-934e-7363-82e0-fd709fc7ea51',
                'name' => 'Test Google User\'s Workspace',
                'slug' => 'test-google-users-workspace-hvqeqi',
                'logo' => NULL,
                'owner_id' => 7,
                'settings' => '[]',
                'created_at' => '2026-02-18 22:08:55',
                'updated_at' => '2026-02-18 22:08:55',
                'banner_image' => NULL,
                'description' => NULL,
            ),
            5 => 
            array (
                'id' => '019c730a-da51-708c-96a5-b45e96cd9166',
                'name' => 'TechONE\'s Workspace',
                'slug' => 'techones-workspace-p1fdrh',
                'logo' => NULL,
                'owner_id' => 9,
                'settings' => '[]',
                'created_at' => '2026-02-18 23:16:57',
                'updated_at' => '2026-02-18 23:16:57',
                'banner_image' => NULL,
                'description' => NULL,
            ),
            6 => 
            array (
                'id' => '019ca263-939a-710a-9654-18b61f121850',
                'name' => 'Test User\'s Workspace',
                'slug' => 'test-users-workspace-4coyi2',
                'logo' => NULL,
                'owner_id' => 10,
                'settings' => '[]',
                'created_at' => '2026-02-28 03:56:00',
                'updated_at' => '2026-02-28 03:56:00',
                'banner_image' => NULL,
                'description' => NULL,
            ),
            7 => 
            array (
                'id' => '019dd9a8-e608-7354-b798-1525a1cf4e10',
                'name' => 'Demo Tenant',
                'slug' => 'demo-tenant',
                'logo' => NULL,
                'owner_id' => 11,
                'settings' => '[]',
                'created_at' => '2026-04-29 14:33:38',
                'updated_at' => '2026-04-29 14:33:38',
                'banner_image' => NULL,
                'description' => NULL,
            ),
            8 => 
            array (
                'id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'name' => 'Moris Autos',
                'slug' => 'auto',
                'logo' => 'tenant-logos/h5vvRIYHHtyJPXVDSgpBLywKYDa6QIDXRFK7hJhG.webp',
                'owner_id' => 1,
                'settings' => NULL,
                'created_at' => '2026-02-17 22:18:14',
                'updated_at' => '2026-05-06 16:45:39',
                'banner_image' => 'tenant-banners/Mls7TOPZ7y6dIkUo05CVGGj2TV8HYAhNxYdUU3I1.jpg',
                'description' => NULL,
            ),
        ));
        
        
    }
}