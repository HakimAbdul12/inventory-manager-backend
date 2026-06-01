<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MessagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('messages')->delete();
        
        \DB::table('messages')->insert(array (
            0 => 
            array (
                'id' => 1,
                'sender_id' => 4,
                'subject' => '🚧 System Maintenance Notice',
                'body' => '# 🚧 System Maintenance Notice

Dear Valued Dealer,

We’re currently performing important upgrades to improve the performance, reliability, and overall experience of the platform.

During this period, some features may be temporarily unavailable.

**Expected downtime:** Up to **48 hours**

We appreciate your patience and understanding while we complete these improvements. The system will be back online as soon as possible with enhanced capabilities.

If you need urgent assistance during this time, please contact our support team.

Thank you for your continued partnership.

— The Management Team',
                'type' => 'direct',
                'created_at' => '2026-02-07 01:40:17',
                'updated_at' => '2026-02-07 01:40:17',
                'deleted_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'sender_id' => 4,
                'subject' => '🔔 Temporary Service Update',
                'body' => '# 🔔 Temporary Service Update

Hello Dealer,

We’re rolling out a set of system updates aimed at making the platform faster, more stable, and more secure.

As a result, the system will be temporarily offline.

**Estimated restoration time:** Within **48 hours**

We apologize for any inconvenience this may cause and truly appreciate your patience. Once the updates are complete, you’ll be able to access the platform with improved performance and new enhancements.

For urgent matters, please reach out to your account representative or our support team.

Thank you for your trust and continued support.',
                'type' => 'direct',
                'created_at' => '2026-02-07 01:44:07',
                'updated_at' => '2026-02-07 01:44:07',
                'deleted_at' => NULL,
            ),
            2 => 
            array (
                'id' => 5,
                'sender_id' => 4,
                'subject' => 'System Upgrade in Progress',
                'body' => '# System Upgrade in Progress

Dear Partner,

Please be informed that our system is currently undergoing scheduled maintenance and infrastructure upgrades.

These changes are part of our ongoing efforts to enhance system stability, security, and overall service quality.

**Service availability:** The platform is expected to be restored within **48 hours**.

We regret any inconvenience this may cause and appreciate your cooperation during this period.

Thank you for your continued partnership and understanding.

Sincerely,  
**Operations Team**',
                'type' => 'direct',
                'created_at' => '2026-02-07 01:52:10',
                'updated_at' => '2026-02-07 01:52:10',
                'deleted_at' => NULL,
            ),
            3 => 
            array (
                'id' => 6,
                'sender_id' => 4,
                'subject' => 'Scheduled Maintenance',
                'body' => '# Scheduled Maintenance

Dear Dealer,

Our system is currently undergoing important updates and improvements.

Access to the platform will be temporarily unavailable and is expected to be restored within **48 hours**.

We appreciate your patience and apologize for any inconvenience caused.

— Support Team',
                'type' => 'direct',
                'created_at' => '2026-02-07 01:55:44',
                'updated_at' => '2026-02-07 01:55:44',
                'deleted_at' => NULL,
            ),
            4 => 
            array (
                'id' => 7,
                'sender_id' => 4,
                'subject' => 'Test WebSocket Subject',
                'body' => 'This is a test message to check for WebSocket events.',
                'type' => 'direct',
                'created_at' => '2026-02-07 02:01:37',
                'updated_at' => '2026-02-07 02:01:37',
                'deleted_at' => NULL,
            ),
            5 => 
            array (
                'id' => 8,
                'sender_id' => 4,
                'subject' => 'uiuhui',
                'body' => 'jbiijbjh',
                'type' => 'direct',
                'created_at' => '2026-02-07 02:19:32',
                'updated_at' => '2026-02-07 02:19:32',
                'deleted_at' => NULL,
            ),
            6 => 
            array (
                'id' => 9,
                'sender_id' => 4,
                'subject' => 'hjkkolm',
                'body' => 'jknkkl',
                'type' => 'direct',
                'created_at' => '2026-02-07 02:26:42',
                'updated_at' => '2026-02-07 02:26:42',
                'deleted_at' => NULL,
            ),
            7 => 
            array (
                'id' => 10,
                'sender_id' => 4,
                'subject' => 'kmlkn.',
                'body' => 'jhbjknn,k',
                'type' => 'direct',
                'created_at' => '2026-02-07 02:35:59',
                'updated_at' => '2026-02-07 02:35:59',
                'deleted_at' => NULL,
            ),
            8 => 
            array (
                'id' => 11,
                'sender_id' => 4,
                'subject' => 'Platform Update Notice',
                'body' => '# Platform Update Notice

Hello,

We’re making behind-the-scenes improvements to ensure a better and more reliable experience for all dealers.

The system will be temporarily unavailable during this upgrade and is expected to return within **48 hours**.

Thank you for your patience while we work to serve you better.

— Customer Success Team',
                'type' => 'direct',
                'created_at' => '2026-02-07 02:37:36',
                'updated_at' => '2026-02-07 02:37:36',
                'deleted_at' => NULL,
            ),
            9 => 
            array (
                'id' => 12,
                'sender_id' => 4,
                'subject' => 'Platform Update Notice',
                'body' => 'Platform Update Notice',
                'type' => 'direct',
                'created_at' => '2026-02-07 02:39:02',
                'updated_at' => '2026-02-07 02:39:02',
                'deleted_at' => NULL,
            ),
        ));
        
        
    }
}