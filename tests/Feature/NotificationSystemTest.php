<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantRole;
use App\DTOs\NotificationData;
use App\Services\NotificationService;
use App\Events\NotificationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationService::class);
    }

    public function test_can_send_tenant_scoped_notification_to_users(): void
    {
        Event::fake([NotificationReceived::class]);

        $tenant = Tenant::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Attach users to tenant
        $tenant->users()->attach($user1->id);
        $tenant->users()->attach($user2->id);

        $dto = NotificationData::fromArray([
            'title' => 'Test Notification',
            'body' => 'Test Body',
            'category' => 'info',
            'tenantId' => $tenant->id,
        ]);

        $notification = $this->service->send($dto, [
            'user_ids' => [$user1->id, $user2->id]
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'title' => 'Test Notification',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notification->id,
            'user_id' => $user1->id,
            'read_at' => null,
        ]);

        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notification->id,
            'user_id' => $user2->id,
            'read_at' => null,
        ]);

        Event::assertDispatched(NotificationReceived::class, function ($event) use ($notification, $user1, $user2) {
            return $event->notification->id === $notification->id &&
                in_array($user1->id, $event->userIds) &&
                in_array($user2->id, $event->userIds);
        });
    }

    public function test_controller_can_list_notifications_and_unread_counts(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['current_tenant_id' => $tenant->id]);
        $tenant->users()->attach($user->id);

        $dto = NotificationData::fromArray([
            'title' => 'Unread Notification',
            'body' => 'Test Body',
            'category' => 'info',
            'tenantId' => $tenant->id,
        ]);

        $notification = $this->service->send($dto, [
            'user_ids' => [$user->id]
        ]);

        $response = $this->actingAs($user)
            ->getJson("/crm/notifications");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'title' => 'Unread Notification',
            'read_at' => null,
        ]);

        $countResponse = $this->actingAs($user)
            ->getJson("/crm/notifications/unread-count");

        $countResponse->assertStatus(200);
        $countResponse->assertJsonPath('count', 1);
    }

    public function test_controller_can_mark_read(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['current_tenant_id' => $tenant->id]);
        $tenant->users()->attach($user->id);

        $dto = NotificationData::fromArray([
            'title' => 'Unread Notification',
            'body' => 'Test Body',
            'category' => 'info',
            'tenantId' => $tenant->id,
        ]);

        $notification = $this->service->send($dto, [
            'user_ids' => [$user->id]
        ]);

        $response = $this->actingAs($user)
            ->postJson("/crm/notifications/{$notification->id}/mark-read");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('notification_recipients', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $countResponse = $this->actingAs($user)
            ->getJson("/crm/notifications/unread-count");

        $countResponse->assertJsonPath('count', 0);
    }
}
