<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryPriceHistoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventory_price_histories')->delete();
        
        \DB::table('inventory_price_histories')->insert(array (
            0 => 
            array (
                'id' => '019df540-423b-7167-bb8b-c32be5f639a4',
                'inventory_item_id' => '019df143-91e4-7304-90b4-2b4aa1a182b5',
                'old_price' => NULL,
                'new_price' => '8999.00',
                'changed_by' => 1,
                'source' => 'manual',
                'notes' => NULL,
                'created_at' => '2026-05-04 23:08:42',
                'updated_at' => '2026-05-04 23:08:42',
            ),
            1 => 
            array (
                'id' => '019df540-aa23-7059-bbb6-48f2a65061fc',
                'inventory_item_id' => '019df143-91e4-7304-90b4-2b4aa1a182b5',
                'old_price' => '8999.00',
                'new_price' => '89998.00',
                'changed_by' => 1,
                'source' => 'manual',
                'notes' => NULL,
                'created_at' => '2026-05-04 23:09:09',
                'updated_at' => '2026-05-04 23:09:09',
            ),
            2 => 
            array (
                'id' => '019e0105-a803-7011-a0d1-000e8f7d3298',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'old_price' => '89000.00',
                'new_price' => '89003.00',
                'changed_by' => 1,
                'source' => 'manual',
                'notes' => NULL,
                'created_at' => '2026-05-07 06:00:08',
                'updated_at' => '2026-05-07 06:00:08',
            ),
            3 => 
            array (
                'id' => '019e0361-42cf-715d-98b1-3d64d6624cf0',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'old_price' => '89003.00',
                'new_price' => '70000.00',
                'changed_by' => 1,
                'source' => 'manual',
                'notes' => NULL,
                'created_at' => '2026-05-07 16:59:26',
                'updated_at' => '2026-05-07 16:59:26',
            ),
            4 => 
            array (
                'id' => '019e037d-ff69-70c8-8cbb-59a8eb48befe',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'old_price' => '70000.00',
                'new_price' => '69500.00',
                'changed_by' => 1,
                'source' => 'manual',
                'notes' => NULL,
                'created_at' => '2026-05-07 17:30:49',
                'updated_at' => '2026-05-07 17:30:49',
            ),
        ));
        
        
    }
}