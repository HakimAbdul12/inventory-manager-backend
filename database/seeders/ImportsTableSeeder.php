<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ImportsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('imports')->delete();
        
        \DB::table('imports')->insert(array (
            0 => 
            array (
                'id' => 1,
                'file_path' => 'imports/2KfNGTCbSCcjJhOH6mkB2vNWbsVpfIKHLlZLll6D.csv',
                'file_name' => 'cars.csv',
                'total_rows' => 428,
                'processed_rows' => 0,
                'status' => 'mapping',
                'category_slug' => 'cars',
                'mappings' => '{"Make":"make"}',
                'errors' => NULL,
                'created_at' => '2026-02-03 20:39:14',
                'updated_at' => '2026-02-03 23:07:58',
                'user_id' => NULL,
                'tenant_id' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'file_path' => 'imports/Ki4nZpw066ER3FoWdIoiggaL2MAtuX93jnBDPZCL.csv',
                'file_name' => 'cars.csv',
                'total_rows' => 428,
                'processed_rows' => 0,
                'status' => 'mapping',
                'category_slug' => 'cars',
                'mappings' => NULL,
                'errors' => NULL,
                'created_at' => '2026-02-06 21:35:39',
                'updated_at' => '2026-02-06 21:35:39',
                'user_id' => 1,
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
            ),
            2 => 
            array (
                'id' => 3,
                'file_path' => 'imports/5QR4d8wxQzIypgxlQiVwmlTQiHfOm4Xsm2fg6h4B.csv',
                'file_name' => 'cars.csv',
                'total_rows' => 428,
                'processed_rows' => 0,
                'status' => 'mapping',
                'category_slug' => 'cars',
                'mappings' => NULL,
                'errors' => NULL,
                'created_at' => '2026-04-27 11:03:23',
                'updated_at' => '2026-04-27 11:03:23',
                'user_id' => 1,
                'tenant_id' => NULL,
            ),
        ));
        
        
    }
}