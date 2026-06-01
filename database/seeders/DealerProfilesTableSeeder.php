<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DealerProfilesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('dealer_profiles')->delete();
        
        \DB::table('dealer_profiles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 1,
                'service_area' => NULL,
                'certifications' => NULL,
                'accepting_partnerships' => true,
                'is_bulk_trader' => true,
                'is_verified' => false,
                'verification_badges' => NULL,
                'reputation_score' => '0.00',
                'response_time_hours' => NULL,
                'created_at' => '2026-02-16 22:00:49',
                'updated_at' => '2026-02-16 22:02:04',
            ),
        ));
        
        
    }
}