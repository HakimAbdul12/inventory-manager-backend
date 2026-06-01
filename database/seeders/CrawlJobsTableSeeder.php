<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CrawlJobsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('crawl_jobs')->delete();
        
        \DB::table('crawl_jobs')->insert(array (
            0 => 
            array (
                'id' => '019ce9d3-bf68-7369-9b76-e0277e476aa0',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'seed_url' => 'https://www.caterpillar.com',
                'status' => 'completed',
                'max_depth' => NULL,
                'max_pages' => NULL,
                'pages_discovered' => 1,
                'pages_processed' => 0,
                'pages_excluded' => 0,
                'started_at' => '2026-03-14 00:51:34',
                'completed_at' => '2026-03-14 00:51:42',
                'created_at' => '2026-03-14 00:51:34',
                'updated_at' => '2026-03-14 00:51:42',
            ),
            1 => 
            array (
                'id' => '019ce9de-d8a4-7168-b821-57fb88b853ce',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'seed_url' => 'https://www.caterpillar.com',
                'status' => 'completed',
                'max_depth' => NULL,
                'max_pages' => NULL,
                'pages_discovered' => 1,
                'pages_processed' => 0,
                'pages_excluded' => 0,
                'started_at' => '2026-03-14 01:03:41',
                'completed_at' => '2026-03-14 01:03:59',
                'created_at' => '2026-03-14 01:03:41',
                'updated_at' => '2026-03-14 01:03:59',
            ),
            2 => 
            array (
                'id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'seed_url' => 'http://localhost:4500',
                'status' => 'completed',
                'max_depth' => NULL,
                'max_pages' => NULL,
                'pages_discovered' => 4,
                'pages_processed' => 4,
                'pages_excluded' => 0,
                'started_at' => '2026-03-13 22:27:01',
                'completed_at' => '2026-03-13 22:27:03',
                'created_at' => '2026-03-13 22:27:01',
                'updated_at' => '2026-04-13 11:12:07',
            ),
            3 => 
            array (
                'id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'seed_url' => 'http://localhost:4300',
                'status' => 'completed',
                'max_depth' => NULL,
                'max_pages' => NULL,
                'pages_discovered' => 4,
                'pages_processed' => 3,
                'pages_excluded' => 0,
                'started_at' => '2026-03-13 22:15:21',
                'completed_at' => '2026-03-13 22:15:22',
                'created_at' => '2026-03-13 22:15:21',
                'updated_at' => '2026-03-13 22:15:22',
            ),
            4 => 
            array (
                'id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'seed_url' => 'http://localhost:4400',
                'status' => 'completed',
                'max_depth' => NULL,
                'max_pages' => NULL,
                'pages_discovered' => 4,
                'pages_processed' => 3,
                'pages_excluded' => 0,
                'started_at' => '2026-03-13 22:17:00',
                'completed_at' => '2026-03-13 22:17:03',
                'created_at' => '2026-03-13 22:17:00',
                'updated_at' => '2026-03-13 22:17:03',
            ),
            5 => 
            array (
                'id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'seed_url' => 'http://localhost:4500',
                'status' => 'completed',
                'max_depth' => NULL,
                'max_pages' => NULL,
                'pages_discovered' => 10,
                'pages_processed' => 10,
                'pages_excluded' => 0,
                'started_at' => '2026-03-14 00:44:13',
                'completed_at' => '2026-03-14 00:44:16',
                'created_at' => '2026-03-14 00:44:13',
                'updated_at' => '2026-03-14 00:44:16',
            ),
        ));
        
        
    }
}