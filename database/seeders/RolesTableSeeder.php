<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('roles')->delete();
        
        \DB::table('roles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'admin',
                'guard_name' => 'web',
                'created_at' => '2026-02-04 10:17:05',
                'updated_at' => '2026-02-04 10:17:05',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'dealer',
                'guard_name' => 'web',
                'created_at' => '2026-02-04 10:17:05',
                'updated_at' => '2026-02-04 10:17:05',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'owner',
                'guard_name' => 'web',
                'created_at' => '2026-02-18 23:16:30',
                'updated_at' => '2026-02-18 23:16:30',
            ),
        ));
        
        
    }
}