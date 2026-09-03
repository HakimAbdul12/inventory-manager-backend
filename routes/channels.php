<?php

use App\Models\InventoryProcess;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Inventory Process Channel
|--------------------------------------------------------------------------
|
| This channel is used for real-time updates during inventory generation.
| Users can only subscribe to their own process channels.
|
*/
Broadcast::channel('inventory-process.{processId}', function ($user, $processId) {
    // For demo purposes without auth, allow all connections
    // In production, verify the user owns this process:
    // $process = InventoryProcess::find($processId);
    // return $process && $process->user_id === $user->id;

    return true; // Allow for development
});

/*
|--------------------------------------------------------------------------
| User Processes Channel
|--------------------------------------------------------------------------
|
| This channel is used for real-time updates to the user's process list.
| When a new process is created or an existing one updates, this channel
| broadcasts the changes.
|
*/
Broadcast::channel('user.{userId}.processes', function ($user, $userId) {
    // For demo purposes, allow all connections
    // In production: return $user && $user->id === $userId;

    return true; // Allow for development
});

/*
|--------------------------------------------------------------------------
| User Inbox Channel
|--------------------------------------------------------------------------
|
| This channel is used for real-time messages.
|
*/
Broadcast::channel('user.{id}.inbox', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Dealer Hub Chat Channel
|--------------------------------------------------------------------------
|
| This channel is used for real-time dealer-to-dealer chat messages.
| Users must be a member of the chat room to subscribe.
|
*/
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    return \App\Models\ChatRoomMember::where('chat_room_id', $roomId)
        ->where('user_id', $user->id)
        ->exists();
});

/*
|--------------------------------------------------------------------------
| Dealer Hub Notifications Channel
|--------------------------------------------------------------------------
|
| Per-user channel for real-time notifications (connection requests,
| accepted connections, new message alerts).
|
*/
Broadcast::channel('user.{id}.notifications', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Chat Widget — Tenant Handoff Channel (Private)
|--------------------------------------------------------------------------
|
| Dashboard users subscribe to receive instant alerts when visitors
| request a human agent. Only authenticated users of the same tenant
| can subscribe.
|
*/
Broadcast::channel('tenant.{tenantId}.handoffs', function ($user, $tenantId) {
    \Illuminate\Support\Facades\Log::info('Authorizing tenant handoffs channel', [
        'user_id' => $user->id,
        'user_tenant_id' => $user->current_tenant_id,
        'requested_tenant_id' => $tenantId,
        'match' => $user->current_tenant_id == $tenantId
    ]);
    return $user && (string)$user->current_tenant_id === (string)$tenantId;
});

/*
|--------------------------------------------------------------------------
| Tenant Permissions Channel (Private)
|--------------------------------------------------------------------------
|
| Used for broadcasting real-time permission and role updates to users
| actively working within the tenant.
|
*/
Broadcast::channel('tenant.{tenantId}.permissions', function ($user, $tenantId) {
    return $user && (string)$user->current_tenant_id === (string)$tenantId;
});

// Note: chat-conversation.{id} uses a public Channel (not PrivateChannel)
// so that the unauthenticated widget can subscribe without Sanctum auth.
// Security is enforced by session_token checks in the event payload.

/*
|--------------------------------------------------------------------------
| Website Crawler Channel
|--------------------------------------------------------------------------
|
| This channel is used for real-time updates during website crawling.
| Only users whose tenant owns the crawl job can subscribe.
|
*/
Broadcast::channel('crawl-job.{jobId}', function ($user, $jobId) {
    $job = \App\Models\CrawlJob::withoutGlobalScope('tenant')->find($jobId);
    return $job && (string)$job->tenant_id === (string)$user->current_tenant_id;
});

/*
|--------------------------------------------------------------------------
| Publishing Batch Channel
|--------------------------------------------------------------------------
|
| Used for broadcasting real-time progress of vehicle publishing tasks.
|
*/
Broadcast::channel('publishing-batch.{batchId}', function ($user, $batchId) {
    return true; // Allow authenticated users in workspace
});

/*
|--------------------------------------------------------------------------
| Inventory Channel
|--------------------------------------------------------------------------
|
| Used for broadcasting real-time inventory updates such as price changes.
|
*/
Broadcast::channel('inventory', function () {
    return true;
});

Broadcast::channel('tenant.{tenantId}.inventory', function () {
    return true;
});

