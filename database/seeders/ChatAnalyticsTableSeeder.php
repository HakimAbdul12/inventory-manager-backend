<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChatAnalyticsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('chat_analytics')->delete();
        
        \DB::table('chat_analytics')->insert(array (
            0 => 
            array (
                'id' => '019ca732-0ed2-70a2-af2d-53576c9d2f55',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-03-01',
                'total_conversations' => 10,
                'total_messages' => 25,
                'human_handoff_count' => 5,
                'leads_captured' => 7,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-03-01 02:20:01',
            ),
            1 => 
            array (
                'id' => '019c7dea-2f9e-7049-b50e-c9c73bf9fa7e',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-02-21',
                'total_conversations' => 18,
                'total_messages' => 37,
                'human_handoff_count' => 0,
                'leads_captured' => 0,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-02-21 01:57:05',
            ),
            2 => 
            array (
                'id' => '019d2277-b5f0-7245-8042-1eb3db231da8',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-03-25',
                'total_conversations' => 7,
                'total_messages' => 12,
                'human_handoff_count' => 0,
                'leads_captured' => 0,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-03-25 00:49:23',
            ),
            3 => 
            array (
                'id' => '019c8ed0-6c36-7337-abf4-70ea9e2e29fd',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-02-24',
                'total_conversations' => 16,
                'total_messages' => 27,
                'human_handoff_count' => 12,
                'leads_captured' => 8,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-02-24 08:42:29',
            ),
            4 => 
            array (
                'id' => '019c9265-1a8b-7352-900a-86da1c67f254',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-02-25',
                'total_conversations' => 1,
                'total_messages' => 4,
                'human_handoff_count' => 1,
                'leads_captured' => 1,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-02-25 01:23:45',
            ),
            5 => 
            array (
                'id' => '019d3bfc-7315-701c-beac-3f21a5e3809e',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-03-29',
                'total_conversations' => 2,
                'total_messages' => 9,
                'human_handoff_count' => 0,
                'leads_captured' => 2,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-03-29 23:44:53',
            ),
            6 => 
            array (
                'id' => '019ca287-7c48-735d-a8cd-1e56617e6836',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-02-28',
                'total_conversations' => 1,
                'total_messages' => 2,
                'human_handoff_count' => 1,
                'leads_captured' => 1,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-02-28 04:35:14',
            ),
            7 => 
            array (
                'id' => '019caf6e-6b16-7363-bb0c-cdaf7d0e4ece',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-03-02',
                'total_conversations' => 3,
                'total_messages' => 11,
                'human_handoff_count' => 1,
                'leads_captured' => 3,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-03-02 16:42:55',
            ),
            8 => 
            array (
                'id' => '019cee85-d380-7354-b593-893a53c10604',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-03-14',
                'total_conversations' => 11,
                'total_messages' => 28,
                'human_handoff_count' => 5,
                'leads_captured' => 11,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-03-14 22:44:33',
            ),
            9 => 
            array (
                'id' => '019d3c78-baa7-72c9-b956-b8193d2a4511',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-03-30',
                'total_conversations' => 1,
                'total_messages' => 7,
                'human_handoff_count' => 0,
                'leads_captured' => 2,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-03-30 02:00:38',
            ),
            10 => 
            array (
                'id' => '019ceed5-c1d4-720d-9022-c10abe56be0c',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-03-15',
                'total_conversations' => 3,
                'total_messages' => 2,
                'human_handoff_count' => 2,
                'leads_captured' => 2,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-03-15 00:11:52',
            ),
            11 => 
            array (
                'id' => '019dceb1-afd7-717d-ab0b-84f2572b77ea',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-04-27',
                'total_conversations' => 1,
                'total_messages' => 2,
                'human_handoff_count' => 0,
                'leads_captured' => 1,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-04-27 11:27:24',
            ),
            12 => 
            array (
                'id' => '019d1348-c764-70d7-9a4a-8a630f01cc20',
                'tenant_id' => '019c6dae-bcb7-7388-a2c0-f465f5daf777',
                'date' => '2026-03-22',
                'total_conversations' => 12,
                'total_messages' => 30,
                'human_handoff_count' => 0,
                'leads_captured' => 15,
                'avg_confidence_score' => '0.00',
                'most_requested_vehicles' => '[]',
                'avg_response_time_seconds' => 0,
                'created_at' => '2026-03-22 02:03:49',
            ),
        ));
        
        
    }
}