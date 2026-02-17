<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DealerConnection;
use App\Models\FeedInteraction;
use App\Models\FeedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerFeedController extends Controller
{
    /**
     * Get the social feed (own + connected dealers' posts + public posts).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get connected user IDs
        $connectedIds = DealerConnection::where('status', 'accepted')
            ->where(fn($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->get()
            ->map(fn($c) => $c->sender_id === $user->id ? $c->receiver_id : $c->sender_id)
            ->toArray();

        $feedUserIds = array_merge($connectedIds, [$user->id]);

        $posts = FeedPost::whereIn('user_id', $feedUserIds)
            ->with(['user:id,name,company_name,avatar,location_city'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate($request->input('per_page', 20));

        // Add interaction state for current user
        $postIds = $posts->pluck('id');
        $myInteractions = FeedInteraction::where('user_id', $user->id)
            ->whereIn('feed_post_id', $postIds)
            ->get()
            ->groupBy('feed_post_id');

        $posts->getCollection()->transform(function ($post) use ($myInteractions) {
            $interactions = $myInteractions->get($post->id, collect());
            $post->is_liked = $interactions->contains('type', 'like');
            $post->is_bookmarked = $interactions->contains('type', 'bookmark');
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Create a new feed post.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'type' => 'sometimes|in:general,inventory,looking_to_buy,deal_success,price_drop,announcement',
            'media' => 'nullable|array',
            'inventory_ids' => 'nullable|array',
        ]);

        $post = FeedPost::create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'type' => $validated['type'] ?? 'general',
            'media' => $validated['media'] ?? null,
            'inventory_ids' => $validated['inventory_ids'] ?? null,
        ]);

        $post->load('user:id,name,company_name,avatar,location_city');

        return response()->json(['post' => $post], 201);
    }

    /**
     * Toggle like on a post.
     */
    public function toggleLike(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        $post = FeedPost::findOrFail($postId);

        $existing = FeedInteraction::where('feed_post_id', $postId)
            ->where('user_id', $user->id)
            ->where('type', 'like')
            ->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('likes_count');
            return response()->json(['liked' => false, 'likes_count' => $post->fresh()->likes_count]);
        }

        FeedInteraction::create([
            'feed_post_id' => $postId,
            'user_id' => $user->id,
            'type' => 'like',
        ]);

        $post->increment('likes_count');
        return response()->json(['liked' => true, 'likes_count' => $post->fresh()->likes_count]);
    }

    /**
     * Add a comment to a post.
     */
    public function comment(Request $request, int $postId): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $post = FeedPost::findOrFail($postId);

        $interaction = FeedInteraction::create([
            'feed_post_id' => $postId,
            'user_id' => $request->user()->id,
            'type' => 'comment',
            'content' => $validated['content'],
        ]);

        $post->increment('comments_count');

        $interaction->load('user:id,name,avatar');

        return response()->json(['comment' => $interaction], 201);
    }

    /**
     * Toggle bookmark on a post.
     */
    public function toggleBookmark(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        FeedPost::findOrFail($postId);

        $existing = FeedInteraction::where('feed_post_id', $postId)
            ->where('user_id', $user->id)
            ->where('type', 'bookmark')
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['bookmarked' => false]);
        }

        FeedInteraction::create([
            'feed_post_id' => $postId,
            'user_id' => $user->id,
            'type' => 'bookmark',
        ]);

        return response()->json(['bookmarked' => true]);
    }

    /**
     * Get comments for a post.
     */
    public function comments(Request $request, int $postId): JsonResponse
    {
        $comments = FeedInteraction::where('feed_post_id', $postId)
            ->where('type', 'comment')
            ->with('user:id,name,avatar')
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json($comments);
    }
}
