<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryDocumentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventory_documents')->delete();
        
        \DB::table('inventory_documents')->insert(array (
            0 => 
            array (
                'id' => '019cef0d-0577-73fd-87bc-eb3ef71ff9a4',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'name' => 'Carfax report 1',
                'type' => 'Report',
                'path' => 'inventory/019c6fce-ca64-71fa-94a2-abfbeb06fcd1/documents/doc_69b6076ddf188.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'size' => 155285,
                'created_at' => '2026-03-15 01:12:13',
                'updated_at' => '2026-03-15 01:12:13',
            ),
        ));
        
        
    }
}