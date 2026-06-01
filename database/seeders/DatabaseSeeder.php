<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->call([
            CategoriesTableSeeder::class,
            UsersTableSeeder::class,
            TenantsTableSeeder::class,
            ChatAnalyticsTableSeeder::class,
            ChatConversationsTableSeeder::class,
            InventoryProcessesTableSeeder::class,
            InventoryItemsTableSeeder::class,
            ChatLeadsTableSeeder::class,
            ChatRoomsTableSeeder::class,
            ChatMessagesTableSeeder::class,
            ChatMessageReactionsTableSeeder::class,
            ChatRoomMembersTableSeeder::class,
            ChatWidgetMessagesTableSeeder::class,
            CrawlJobsTableSeeder::class,
            CrawlPagesTableSeeder::class,
            CrawlLinksTableSeeder::class,
            ProspectsTableSeeder::class,
            LeadsTableSeeder::class,
            CreditApplicationsTableSeeder::class,
            DealerConnectionsTableSeeder::class,
            DealerProfilesTableSeeder::class,
            ImportsTableSeeder::class,
            InventoryDocumentsTableSeeder::class,
            InventoryImagesTableSeeder::class,
            InventoryItemLeadTableSeeder::class,
            InventoryPriceHistoriesTableSeeder::class,
            InventoryPublishingStatusesTableSeeder::class,
            InventoryPushJobsTableSeeder::class,
            InventoryPushHistoryTableSeeder::class,
            InventoryVideosTableSeeder::class,
            LeadCommunicationsTableSeeder::class,
            LeadStatusHistoryTableSeeder::class,
            MessagesTableSeeder::class,
            MessageRecipientsTableSeeder::class,
            MessageTemplatesTableSeeder::class,
            RolesTableSeeder::class,
            ModelHasRolesTableSeeder::class,
            NotificationsTableSeeder::class,
            NotificationRecipientsTableSeeder::class,
            PersonalAccessTokensTableSeeder::class,
            ProcessStepsTableSeeder::class,
            RagDocumentsTableSeeder::class,
            ReconditioningTasksTableSeeder::class,
            ServiceRecordsTableSeeder::class,
            SftpConnectionsTableSeeder::class,
            TelegramAgentsTableSeeder::class,
            TelegramConnectionsTableSeeder::class,
            TenantEmailSettingsTableSeeder::class,
            TenantInvitationsTableSeeder::class,
            TenantPermissionsTableSeeder::class,
            TenantRolesTableSeeder::class,
            TenantRolePermissionsTableSeeder::class,
            TenantUserRolesTableSeeder::class,
            TenantUserTableSeeder::class,
            TestDriveConfigsTableSeeder::class,
            TestDrivesTableSeeder::class,
            TransfersTableSeeder::class,
            VehiclesTableSeeder::class,
            VirtualShowroomsTableSeeder::class,
            WorkspaceChatConfigsTableSeeder::class,
        ]);

        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
    }
}
