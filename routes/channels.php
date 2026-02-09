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
