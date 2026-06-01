<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryVideosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventory_videos')->delete();
        
        \DB::table('inventory_videos')->insert(array (
            0 => 
            array (
                'id' => '019cef12-dc51-7077-bfd5-beb1eaf30d8f',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'type' => 'link',
                'url' => 'https://youtube.com/shorts/T8y1zaWpGhU?si=lmph3vvaUKAMe5Pj',
                'path' => NULL,
                'title' => NULL,
                'created_at' => '2026-03-15 01:18:36',
                'updated_at' => '2026-03-15 01:18:36',
            ),
        ));
        
        
    }
}