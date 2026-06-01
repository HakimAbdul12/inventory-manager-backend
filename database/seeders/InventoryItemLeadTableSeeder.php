<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryItemLeadTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventory_item_lead')->delete();
        
        \DB::table('inventory_item_lead')->insert(array (
            0 => 
            array (
                'id' => 1,
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'lead_id' => '019caaa6-c37b-7142-a5c4-39048233610c',
                'created_at' => '2026-05-11 16:11:41',
                'updated_at' => '2026-05-11 16:11:41',
            ),
            1 => 
            array (
                'id' => 2,
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'inventory_item_id' => '019c358a-3c11-705f-bb5a-826c3517bb21',
                'lead_id' => '019dceb1-b22c-7358-b3ee-5cab0f59bd83',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}