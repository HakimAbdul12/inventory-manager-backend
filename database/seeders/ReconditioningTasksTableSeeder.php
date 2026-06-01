<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReconditioningTasksTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('reconditioning_tasks')->delete();
        
        \DB::table('reconditioning_tasks')->insert(array (
            0 => 
            array (
                'id' => '019e2e36-75d3-7170-82eb-1ab213bd24f1',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'inventory_item_id' => '019c6fce-ca64-71fa-94a2-abfbeb06fcd1',
                'task_name' => 'Detailing',
                'status' => 'in_progress',
                'assigned_vendor' => 'Grok detailing services',
                'cost' => '500.00',
                'priority' => 'High',
                'notes' => NULL,
                'created_at' => '2026-05-16 00:36:21',
                'updated_at' => '2026-05-16 00:36:21',
            ),
        ));
        
        
    }
}