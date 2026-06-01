<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VirtualShowroomsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('virtual_showrooms')->delete();
        
        \DB::table('virtual_showrooms')->insert(array (
            0 => 
            array (
                'id' => '019c3545-90fe-715b-9d95-94d8b2c7160b',
                'user_id' => '1',
                'image_path' => 'virtual-showrooms/1/showroom_0c54150e-4324-4078-a175-38fec884b03b.jpeg',
                'name' => 'Alabama show room',
                'description' => NULL,
                'created_at' => '2026-02-06 23:24:37',
                'updated_at' => '2026-02-06 23:24:37',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
        ));
        
        
    }
}