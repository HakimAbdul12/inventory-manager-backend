<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\InventoryController;

use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Dashboard\TenantEmailSettingController;
use App\Http\Controllers\Api\Dashboard\LeadController;
use App\Http\Controllers\Api\AINegotiatorController;
use App\Http\Controllers\Api\AcquisitionController;
use App\Http\Controllers\Api\DealerProfileController;
use App\Http\Controllers\Api\DealerConnectionController;
use App\Http\Controllers\Api\InAppChatController;
use App\Http\Controllers\Api\ChatAttachmentController;
use App\Http\Controllers\Api\DealerFeedController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\CrawlJobController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\SystemRoleController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Public auth routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

/*
|--------------------------------------------------------------------------
| Invitation Routes
|--------------------------------------------------------------------------
*/
Route::prefix('invitations')->group(function () {
    Route::get('/{token}', [\App\Http\Controllers\Api\InvitationController::class, 'show']);
    Route::post('/{token}/accept', [\App\Http\Controllers\Api\InvitationController::class, 'accept']);
});

/*
|--------------------------------------------------------------------------
| Tenant (Workspace) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('tenants')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/', [TenantController::class, 'index']);
    Route::post('/', [TenantController::class, 'store']);
    Route::post('/switch', [TenantController::class, 'switchTenant']);
    Route::put('/{id}', [TenantController::class, 'update']);
    Route::delete('/{id}', [TenantController::class, 'destroy']);
    Route::post('/{id}/upload-banner', [TenantController::class, 'uploadBanner']);
    Route::post('/{id}/upload-logo', [TenantController::class, 'uploadLogo']);
    Route::get('/{id}/members', [TenantController::class, 'members']);
    Route::post('/{id}/members', [TenantController::class, 'addMember']);
    Route::post('/{id}/members/invite', [TenantController::class, 'inviteMember']);
    Route::get('/{tenantId}/members/{userId}', [TenantController::class, 'getMember']);
    Route::put('/{tenantId}/members/{userId}', [TenantController::class, 'updateMember']);
    Route::delete('/{tenantId}/members/{userId}', [TenantController::class, 'removeMember']);
    Route::put('/{tenantId}/members/{userId}/roles', [TenantController::class, 'assignUserRoles']);

    // Custom Roles & Permissions
    Route::get('/{id}/roles', [TenantController::class, 'roles']);
    Route::post('/{id}/roles', [TenantController::class, 'createRole']);
    Route::put('/{id}/roles/{roleId}', [TenantController::class, 'updateRole']);
    Route::delete('/{id}/roles/{roleId}', [TenantController::class, 'deleteRole']);
});

/*
|--------------------------------------------------------------------------
| Permissions Routes
|--------------------------------------------------------------------------
*/
Route::prefix('permissions')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/', [PermissionController::class, 'index']); // All available system permissions
    Route::get('/me', [PermissionController::class, 'userPermissions']); // Current user's resolved permissions

    // Super admin only routes
    Route::middleware('permission:system.manage_permissions')->group(function () {
        Route::post('/', [PermissionController::class, 'store']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Category Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{slug}', [CategoryController::class, 'show']);
    Route::get('/{slug}/fields', [CategoryController::class, 'show']);
});

Route::prefix('categories')->middleware('auth:sanctum')->group(function () {
    Route::put('/{id}', [CategoryController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Inventory Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('inventory')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/search', [InventoryController::class, 'search'])->middleware('permission:inventory.view');
    Route::get('/', [InventoryController::class, 'index'])->middleware('permission:inventory.view');
    Route::get('/count', [InventoryController::class, 'count'])->middleware('permission:inventory.view');
    Route::post('/start', [InventoryController::class, 'start'])->middleware('permission:inventory.ai.generate');
    Route::get('/processes', [InventoryController::class, 'processes'])->middleware('permission:inventory.view');
    Route::get('/{processId}/status', [InventoryController::class, 'status'])->middleware('permission:inventory.view');

    // Metrics Routes
    Route::get('/metrics', [\App\Http\Controllers\Api\MetricsController::class, 'stats']);
    Route::get('/logs', [\App\Http\Controllers\Api\MetricsController::class, 'logs']);

    // Blocked IPs
    Route::get('/blocked-ips', [\App\Http\Controllers\Api\BlockedIpController::class, 'index']);
    Route::post('/blocked-ips', [\App\Http\Controllers\Api\BlockedIpController::class, 'store']);
    Route::delete('/blocked-ips/{ip_address}', [\App\Http\Controllers\Api\BlockedIpController::class, 'destroy']);

    Route::get('/spreadsheet/all', [InventoryController::class, 'allItems'])->middleware('permission:inventory.view');
    Route::post('/spreadsheet/create', [InventoryController::class, 'store'])->middleware('permission:inventory.create');
    Route::get('/spreadsheet/export/{format}', [InventoryController::class, 'export'])->middleware('permission:inventory.export');

    Route::get('/{id}', [InventoryController::class, 'show'])->middleware('permission:inventory.view');
    Route::post('/{id}', [InventoryController::class, 'update'])->middleware('permission:inventory.edit');
    Route::post('/{id}/images', [InventoryController::class, 'uploadImage'])->middleware('permission:inventory.image.upload');
    Route::put('/{id}/images/{image}/primary', [InventoryController::class, 'setPrimaryImage'])->middleware('permission:inventory.image.set_primary');
    Route::delete('/{id}/images/{image}', [InventoryController::class, 'deleteImage'])->middleware('permission:inventory.image.delete');
    Route::post('/{id}/images/external', [InventoryController::class, 'addExternalImage'])->middleware('permission:inventory.image.upload');
    Route::post('/{id}/videos', [InventoryController::class, 'uploadVideo'])->middleware('permission:inventory.video.upload');
    Route::delete('/{id}/videos/{video}', [InventoryController::class, 'deleteVideo'])->middleware('permission:inventory.video.delete');
    Route::post('/{id}/documents', [InventoryController::class, 'uploadDocument'])->middleware('permission:inventory.document.upload');
    Route::delete('/{id}/documents/{document}', [InventoryController::class, 'deleteDocument'])->middleware('permission:inventory.document.delete');
    Route::post('/{id}/analyze', [InventoryController::class, 'analyze'])->middleware('permission:inventory.ai.analyze');
    Route::post('/{id}/generate-description', [InventoryController::class, 'generateDescription'])->middleware('permission:inventory.ai.description');
    Route::post('/{id}/images/generate', [InventoryController::class, 'generateAIImages'])->middleware('permission:inventory.ai.generate');
    Route::post('/{id}/images/{image}/approve', [InventoryController::class, 'approveImage'])->middleware('permission:inventory.image.upload');
    Route::post('/{id}/images/{image}/reject', [InventoryController::class, 'rejectImage'])->middleware('permission:inventory.image.upload');
    Route::post('/{id}/images/{image}/process', [InventoryController::class, 'processImage'])->middleware('permission:inventory.image.upload');
    Route::get('/{id}/price-history', [InventoryController::class, 'priceHistory'])->middleware('permission:inventory.price.history');

    // VDP Lazy Load Endpoints
    Route::get('/{id}/deals', [\App\Http\Controllers\Api\DealController::class, 'index'])->middleware('permission:inventory.view');
    Route::get('/{id}/service-records', [\App\Http\Controllers\Api\ServiceRecordController::class, 'index'])->middleware('permission:inventory.view');
    Route::get('/{id}/reconditioning-tasks', [\App\Http\Controllers\Api\ReconditioningTaskController::class, 'index'])->middleware('permission:inventory.view');
    Route::get('/{id}/publishing-status', [\App\Http\Controllers\Api\InventoryPublishingStatusController::class, 'index'])->middleware('permission:inventory.view');
    Route::get('/{id}/leads', [\App\Http\Controllers\Api\InventoryLeadController::class, 'index'])->middleware('permission:inventory.view');

});

/*
|--------------------------------------------------------------------------
| API Key Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('api-keys')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ApiKeyController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\ApiKeyController::class, 'store']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\ApiKeyController::class, 'destroy']);
});
/*
|--------------------------------------------------------------------------
| Import Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('imports')->middleware(['auth:sanctum', 'permission:inventory.import'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ImportController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\ImportController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\ImportController::class, 'show']);
    Route::put('/{id}/mapping', [\App\Http\Controllers\Api\ImportController::class, 'updateMapping']);
    Route::post('/{id}/process', [\App\Http\Controllers\Api\ImportController::class, 'process']);
    Route::post('/{id}/predict-mapping', [\App\Http\Controllers\Api\ImportController::class, 'predictMapping']);
});

/*
|--------------------------------------------------------------------------
| Message Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('messages')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\MessageController::class, 'index']);
    Route::get('/sent', [\App\Http\Controllers\Api\MessageController::class, 'sent']);
    Route::post('/', [\App\Http\Controllers\Api\MessageController::class, 'store']);
    Route::get('/unread-count', [\App\Http\Controllers\Api\MessageController::class, 'unreadCount']);
    Route::get('/{id}', [\App\Http\Controllers\Api\MessageController::class, 'show']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\MessageController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Transfer Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('transfers')->middleware(['auth:sanctum', 'permission:inventory.transfer'])->group(function () {
    Route::post('/search', [TransferController::class, 'search']);
    Route::get('/', [TransferController::class, 'index']);
    Route::post('/', [TransferController::class, 'store']);
    Route::post('/{id}/accept', [TransferController::class, 'accept']);
    Route::post('/{id}/decline', [TransferController::class, 'decline']);
    Route::post('/{id}/cancel', [TransferController::class, 'cancel']);
    Route::get('/{id}/items', [TransferController::class, 'items']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/users', [\App\Http\Controllers\Api\AdminUserController::class, 'index']);
    Route::post('/users/{id}/block', [\App\Http\Controllers\Api\AdminUserController::class, 'toggleBlock']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'destroy']);
});

// System-level role management (super admin only)
Route::prefix('system')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/roles', [SystemRoleController::class, 'index']);
    Route::post('/roles', [SystemRoleController::class, 'store']);
    Route::put('/roles/{roleId}', [SystemRoleController::class, 'update']);
    Route::delete('/roles/{roleId}', [SystemRoleController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Profile Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('profile')->middleware('auth:sanctum')->group(function () {
    Route::put('/', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::post('/avatar', [\App\Http\Controllers\Api\ProfileController::class, 'updateAvatar']);
    Route::put('/password', [\App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
    Route::delete('/', [\App\Http\Controllers\Api\ProfileController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Virtual Showroom Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('virtual-showrooms')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\VirtualShowroomController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\VirtualShowroomController::class, 'store']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\VirtualShowroomController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Automotive / DIS Routes
    Route::get('/dashboard/war-room', [DashboardController::class, 'warRoom']);

    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'index']);
        Route::post('/', [VehicleController::class, 'store']);
        Route::get('/{id}', [VehicleController::class, 'show']);
        Route::post('/decode-vin', [VehicleController::class, 'decodeVin']);
        Route::post('/{id}/pricing/calculate', [VehicleController::class, 'updatePricing']);
        Route::post('/{id}/negotiate', [AINegotiatorController::class, 'chat']);
        Route::get('/{id}/exit-strategies', [AcquisitionController::class, 'proposeExit']);
    });

    Route::get('/acquisition/recommendations', [AcquisitionController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| Dealer Hub Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('dealer-hub')->middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/profile', [DealerProfileController::class, 'show']);
    Route::put('/profile', [DealerProfileController::class, 'update']);
    Route::post('/profile/avatar', [DealerProfileController::class, 'uploadAvatar']);
    Route::post('/profile/banner', [DealerProfileController::class, 'uploadBanner']);
    Route::get('/profile/{id}', [DealerProfileController::class, 'viewProfile']);
    Route::get('/discover', [DealerProfileController::class, 'discover']);
    Route::get('/discover/suggestions', [DealerProfileController::class, 'suggestions']);

    // Connections
    Route::get('/connections', [DealerConnectionController::class, 'index']);
    Route::post('/connections', [DealerConnectionController::class, 'store']);
    Route::put('/connections/{id}', [DealerConnectionController::class, 'update']);
    Route::get('/connections/pending', [DealerConnectionController::class, 'pending']);
    Route::get('/connections/mutual/{userId}', [DealerConnectionController::class, 'mutual']);

    // Feed
    Route::get('/feed', [DealerFeedController::class, 'index']);
    Route::post('/feed', [DealerFeedController::class, 'store']);
    Route::post('/feed/{id}/like', [DealerFeedController::class, 'toggleLike']);
    Route::post('/feed/{id}/comment', [DealerFeedController::class, 'comment']);
    Route::post('/feed/{id}/bookmark', [DealerFeedController::class, 'toggleBookmark']);
    Route::get('/feed/{id}/comments', [DealerFeedController::class, 'comments']);
});

/*
|--------------------------------------------------------------------------
| In-App Chat Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('in-app')->middleware('auth:sanctum')->group(function () {
    // Chat
    Route::get('/chat/rooms', [InAppChatController::class, 'rooms']);
    Route::post('/chat/rooms', [InAppChatController::class, 'createRoom']);
    Route::post('/chat/rooms/{id}/favorite', [InAppChatController::class, 'toggleFavorite']);
    Route::post('/chat/rooms/{id}/pin-room', [InAppChatController::class, 'togglePinRoom']);
    Route::get('/chat/rooms/{id}/messages', [InAppChatController::class, 'messages']);
    Route::post('/chat/rooms/{id}/messages', [InAppChatController::class, 'sendMessage']);
    Route::put('/chat/rooms/{id}/read', [InAppChatController::class, 'markRead']);
    Route::post('/chat/rooms/{id}/messages/{messageId}/reactions', [InAppChatController::class, 'toggleReaction']);
    Route::post('/chat/rooms/{id}/pin/{messageId}', [InAppChatController::class, 'togglePin']);
    Route::post('/chat/rooms/{id}/attachments', [ChatAttachmentController::class, 'upload']);
    Route::get('/chat/rooms/{id}/members', [InAppChatController::class, 'roomMembers']);
    Route::post('/chat/rooms/{id}/members', [InAppChatController::class, 'addMember']);
    Route::delete('/chat/rooms/{id}/members/{userId}', [InAppChatController::class, 'removeMember']);
});

/*
|--------------------------------------------------------------------------
| Chat Widget - Public Endpoints (No Auth, API Key Based)
|--------------------------------------------------------------------------
*/
Route::prefix('widget')->group(function () {
    Route::get('/config-by-tenant/{tenantId}', [\App\Http\Controllers\Api\WidgetConversationController::class, 'configByTenant']);
    Route::get('/{apiKey}/config', [\App\Http\Controllers\Api\WidgetConversationController::class, 'config']);
    Route::post('/{apiKey}/start', [\App\Http\Controllers\Api\WidgetConversationController::class, 'start']);
    Route::post('/{apiKey}/message', [\App\Http\Controllers\Api\WidgetConversationController::class, 'message']);
    Route::post('/{apiKey}/request-human', [\App\Http\Controllers\Api\WidgetConversationController::class, 'requestHuman']);
    Route::post('/{apiKey}/human', [\App\Http\Controllers\Api\WidgetConversationController::class, 'requestHuman']);
    Route::post('/{apiKey}/submit-lead', [\App\Http\Controllers\Api\WidgetConversationController::class, 'submitLead']);
    Route::post('/{apiKey}/lead', [\App\Http\Controllers\Api\WidgetConversationController::class, 'submitLead']);
    Route::get('/{apiKey}/status', [\App\Http\Controllers\Api\WidgetConversationController::class, 'status']);
    Route::get('/{apiKey}/messages', [\App\Http\Controllers\Api\WidgetConversationController::class, 'messages']);
    Route::post('/{apiKey}/disconnect', [\App\Http\Controllers\Api\WidgetConversationController::class, 'disconnect']);

    // Test Drive Scheduling (Public)
    Route::get('/{apiKey}/test-drives/slots', [\App\Http\Controllers\Api\TestDriveController::class, 'availableSlots']);
    Route::post('/{apiKey}/test-drives/book', [\App\Http\Controllers\Api\TestDriveController::class, 'book']);
    Route::post('/{apiKey}/test-drives/reschedule', [\App\Http\Controllers\Api\TestDriveController::class, 'reschedule']);
    Route::post('/{apiKey}/test-drives/cancel', [\App\Http\Controllers\Api\TestDriveController::class, 'cancel']);
    Route::post('/{apiKey}/test-drives/lookup', [\App\Http\Controllers\Api\TestDriveController::class, 'lookup']);
});

/*
|--------------------------------------------------------------------------
| Telegram Webhook (No Auth, Secret Token Verification)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/telegram', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| Chat Widget - Dashboard Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('chat-widget')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Configuration
    Route::get('/config', [\App\Http\Controllers\Api\ChatWidgetConfigController::class, 'show']);
    Route::put('/config', [\App\Http\Controllers\Api\ChatWidgetConfigController::class, 'update']);
    Route::post('/config/regenerate-key', [\App\Http\Controllers\Api\ChatWidgetConfigController::class, 'regenerateKey']);
    Route::get('/config/embed-code', [\App\Http\Controllers\Api\ChatWidgetConfigController::class, 'embedCode']);
    Route::post('/config/test-external-api', [\App\Http\Controllers\Api\ChatWidgetConfigController::class, 'testExternalApi']);

    // Knowledge Base
    Route::get('/knowledge', [\App\Http\Controllers\Api\KnowledgeBaseController::class, 'index']);
    Route::post('/knowledge', [\App\Http\Controllers\Api\KnowledgeBaseController::class, 'store']);
    Route::put('/knowledge/{id}', [\App\Http\Controllers\Api\KnowledgeBaseController::class, 'update']);
    Route::delete('/knowledge/{id}', [\App\Http\Controllers\Api\KnowledgeBaseController::class, 'destroy']);
    Route::post('/knowledge/{id}/reindex', [\App\Http\Controllers\Api\KnowledgeBaseController::class, 'reindex']);

    // Telegram
    Route::get('/telegram', [\App\Http\Controllers\Api\TelegramConnectionController::class, 'show']);
    Route::get('/telegram/agents', [\App\Http\Controllers\Api\TelegramConnectionController::class, 'agents']);
    Route::put('/telegram/agents/{agent}', [\App\Http\Controllers\Api\TelegramConnectionController::class, 'updateAgent']);
    Route::post('/telegram/connect', [\App\Http\Controllers\Api\TelegramConnectionController::class, 'connect']);
    Route::post('/telegram/test', [\App\Http\Controllers\Api\TelegramConnectionController::class, 'test']);
    Route::post('/telegram/disconnect', [\App\Http\Controllers\Api\TelegramConnectionController::class, 'disconnect']);
    Route::delete('/telegram/agents/{agent}', [\App\Http\Controllers\Api\TelegramConnectionController::class, 'removeAgent']);
    Route::put('/telegram/settings', [\App\Http\Controllers\Api\TelegramConnectionController::class, 'updateSettings']);
    Route::get('/telegram/webhook-info', [\App\Http\Controllers\Api\TelegramConnectionController::class, 'webhookInfo']);

    // Analytics & Conversations
    Route::get('/analytics', [\App\Http\Controllers\Api\ChatAnalyticsController::class, 'summary']);
    Route::get('/conversations', [\App\Http\Controllers\Api\ChatAnalyticsController::class, 'conversations']);
    Route::get('/conversations/{id}', [\App\Http\Controllers\Api\ChatAnalyticsController::class, 'showConversation']);
    Route::get('/leads', [\App\Http\Controllers\Api\ChatAnalyticsController::class, 'leads']);

    // Email Integrations & Lead Management
    Route::get('/settings/email', [TenantEmailSettingController::class, 'show']);
    Route::post('/settings/email', [TenantEmailSettingController::class, 'update']);
    Route::patch('/leads/{id}/status', [LeadController::class, 'updateStatus']);
    Route::post('/leads/fetch-emails', [LeadController::class, 'fetchEmails']);

    // Live Queue & Reply (Dealer Dashboard)
    Route::get('/queue', [\App\Http\Controllers\Api\ChatConversationController::class, 'index']);
    Route::get('/queue/pending', [\App\Http\Controllers\Api\ChatConversationController::class, 'pendingHandoffs']);
    Route::get('/queue/{id}', [\App\Http\Controllers\Api\ChatConversationController::class, 'show']);
    Route::post('/queue/{id}/reply', [\App\Http\Controllers\Api\ChatConversationController::class, 'reply']);
    Route::post('/queue/{id}/end', [\App\Http\Controllers\Api\ChatConversationController::class, 'endAndHandToAI']);
    Route::post('/queue/{id}/close', [\App\Http\Controllers\Api\ChatConversationController::class, 'close']);

    // Test Drive Configuration & Management
    Route::get('/test-drives/config', [\App\Http\Controllers\Api\TestDriveConfigController::class, 'show']);
    Route::put('/test-drives/config', [\App\Http\Controllers\Api\TestDriveConfigController::class, 'update']);
    Route::get('/test-drives', [\App\Http\Controllers\Api\TestDriveController::class, 'index']);
    Route::get('/test-drives/{id}', [\App\Http\Controllers\Api\TestDriveController::class, 'show']);
    Route::patch('/test-drives/{id}/status', [\App\Http\Controllers\Api\TestDriveController::class, 'updateStatus']);
});

/*
|--------------------------------------------------------------------------
| SFTP Distribution Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('sftp')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // SFTP Connections
    Route::get('/connections', [\App\Http\Controllers\Api\SftpConnectionController::class, 'index']);
    Route::post('/connections', [\App\Http\Controllers\Api\SftpConnectionController::class, 'store']);
    Route::put('/connections/{id}', [\App\Http\Controllers\Api\SftpConnectionController::class, 'update']);
    Route::delete('/connections/{id}', [\App\Http\Controllers\Api\SftpConnectionController::class, 'destroy']);
    Route::post('/connections/{id}/test', [\App\Http\Controllers\Api\SftpConnectionController::class, 'test']);

    // Push Jobs
    Route::get('/push-jobs', [\App\Http\Controllers\Api\InventoryPushJobController::class, 'index']);
    Route::post('/push-jobs', [\App\Http\Controllers\Api\InventoryPushJobController::class, 'store']);
    Route::put('/push-jobs/{id}', [\App\Http\Controllers\Api\InventoryPushJobController::class, 'update']);
    Route::delete('/push-jobs/{id}', [\App\Http\Controllers\Api\InventoryPushJobController::class, 'destroy']);
    Route::post('/push-jobs/{id}/execute', [\App\Http\Controllers\Api\InventoryPushJobController::class, 'execute']);

    // Push History
    Route::get('/history', [\App\Http\Controllers\Api\InventoryPushHistoryController::class, 'index']);
    Route::get('/history/{id}', [\App\Http\Controllers\Api\InventoryPushHistoryController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Website Crawler Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('crawler')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/', [CrawlJobController::class, 'index']);
    Route::post('/', [CrawlJobController::class, 'store']);
    Route::get('/{id}', [CrawlJobController::class, 'show']);
    Route::delete('/{id}', [CrawlJobController::class, 'destroy']);

    // Actions
    Route::post('/{id}/pause', [CrawlJobController::class, 'pause']);
    Route::post('/{id}/resume', [CrawlJobController::class, 'resume']);
    Route::post('/{id}/cancel', [CrawlJobController::class, 'cancel']);
    Route::post('/{id}/chunk-and-index', [CrawlJobController::class, 'chunkAndIndex']);

    // Pages
    Route::get('/{id}/pages', [CrawlJobController::class, 'pages']);
    Route::get('/{id}/pages/{pageId}', [CrawlJobController::class, 'pageContent']);
    Route::put('/{id}/pages/{pageId}/toggle', [CrawlJobController::class, 'togglePage']);
});

/*
|--------------------------------------------------------------------------
| Activity Log Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('activity-logs')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/', [ActivityLogController::class, 'index']);
    Route::get('/{id}', [ActivityLogController::class, 'show']);
    Route::get('/subject/{type}/{subjectId}', [ActivityLogController::class, 'forSubject']);
});

/*
|--------------------------------------------------------------------------
| CRM Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('crm')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Metadata for filter dropdowns
    Route::get('/metadata', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'metadata']);

    // Leads CRUD
    Route::get('/leads', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'index'])
        ->middleware('permission:crm.leads.view');
    Route::post('/leads', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'store'])
        ->middleware('permission:crm.leads.create');
    Route::get('/leads/{id}', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'show'])
        ->middleware('permission:crm.leads.view');
    Route::put('/leads/{id}', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'update'])
        ->middleware('permission:crm.leads.edit');
    Route::delete('/leads/{id}', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'destroy'])
        ->middleware('permission:crm.leads.delete');

    // Lead status & assignment
    Route::patch('/leads/{id}/status', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'updateStatus'])
        ->middleware('permission:crm.leads.edit');
    Route::patch('/leads/{id}/assign', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'assign'])
        ->middleware('permission:crm.leads.assign');

    // Lead timeline & vehicles
    Route::get('/leads/{id}/status-timeline', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'statusTimeline'])
        ->middleware('permission:crm.leads.view');
    Route::get('/leads/{id}/vehicles', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'vehicles'])
        ->middleware('permission:crm.leads.view');
    Route::post('/leads/{id}/vehicles', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'addVehicle'])
        ->middleware('permission:crm.leads.edit');
    Route::delete('/leads/{id}/vehicles/{vehicleId}', [\App\Http\Controllers\Api\Crm\CrmLeadController::class, 'removeVehicle'])
        ->middleware('permission:crm.leads.edit');
});

