<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DealerConnectionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('dealer_connections')->delete();
        
        \DB::table('dealer_connections')->insert(array (
            0 => 
            array (
                'id' => 1,
                'sender_id' => 3,
                'receiver_id' => 1,
                'status' => 'accepted',
                'connection_level' => 'basic',
                'message' => NULL,
                'created_at' => '2026-02-16 22:10:05',
                'updated_at' => '2026-02-16 22:10:30',
            ),
        ));
        
        
    }
}