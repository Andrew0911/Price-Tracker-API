<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MessagesController extends BaseController
{
    /**
     * GET /messages/{user}?after_id=&limit=
     * Returns the conversation between the auth user and the given friend,
     * ordered oldest-first. Supports incremental polling via after_id.
     */
    public function index(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->is_admin || $user->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use messaging.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        if (!$this->areFriends($authUser, $user)) {
            return $this->sendFailResponse('You can only message friends.', status: Response::HTTP_FORBIDDEN);
        }

        $afterId = (int) $request->query('after_id', 0);
        $limit = min((int) $request->query('limit', 50), 100);

        $messages = Message::with(['product:id,name,url,image_url,store,current_price,currency'])
            ->where(function ($q) use ($authUser, $user) {
                $q->where(function ($inner) use ($authUser, $user) {
                    $inner->where('sender_id', $authUser->id)->where('receiver_id', $user->id);
                })->orWhere(function ($inner) use ($authUser, $user) {
                    $inner->where('sender_id', $user->id)->where('receiver_id', $authUser->id);
                });
            })
            ->when($afterId > 0, fn($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn(Message $m) => $this->formatMessage($m, $authUser->id));

        // Mark unread messages sent by the other user as read
        Message::where('sender_id', $user->id)
            ->where('receiver_id', $authUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->sendSuccessResponse(data: $messages);
    }

    /**
     * GET /messages/unread-counts
     * Returns a mapping of sender_id => unread_count for the authenticated user.
     */
    public function unreadCounts(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use messaging.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        $counts = Message::where('receiver_id', $authUser->id)
            ->whereNull('read_at')
            ->selectRaw('sender_id, COUNT(*) as cnt')
            ->groupBy('sender_id')
            ->get()
            ->pluck('cnt', 'sender_id')
            ->map(fn($v) => (int) $v)
            ->toArray();

        return $this->sendSuccessResponse(data: $counts);
    }

    /**
     * POST /messages/{user}
     * Send a message (text body and/or a shared product).
     */
    public function store(SendMessageRequest $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->is_admin || $user->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use messaging.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        if ($authUser->id === $user->id) {
            return $this->sendFailResponse('You cannot message yourself.', status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->areFriends($authUser, $user)) {
            return $this->sendFailResponse('You can only message friends.', status: Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();

        if (empty($validated['body']) && empty($validated['product_id'])) {
            return $this->sendFailResponse(
                'A message must have either a body or a shared product.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // If sharing a product, verify the auth user actually owns it
        if (!empty($validated['product_id'])) {
            $owns = $authUser->products()->where('product_id', $validated['product_id'])->exists();
            if (!$owns) {
                return $this->sendFailResponse('Product not found in your wishlist.', status: Response::HTTP_FORBIDDEN);
            }
        }

        $message = Message::create([
            'sender_id' => $authUser->id,
            'receiver_id' => $user->id,
            'body' => $validated['body'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
        ]);

        $message->load('product:id,name,url,image_url,store,current_price,currency');

        return $this->sendSuccessResponse(
            message: 'Message sent.',
            data: $this->formatMessage($message, $authUser->id),
            status: Response::HTTP_CREATED
        );
    }

    // -------------------------------------------------------------------------

    private function areFriends(User $a, User $b): bool
    {
        return Friendship::where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($q) use ($a, $b) {
                $q->where(function ($inner) use ($a, $b) {
                    $inner->where('user_id', $a->id)->where('friend_id', $b->id);
                })->orWhere(function ($inner) use ($a, $b) {
                    $inner->where('user_id', $b->id)->where('friend_id', $a->id);
                });
            })
            ->exists();
    }

    private function formatMessage(Message $m, int $authUserId): array
    {
        return [
            'id' => $m->id,
            'body' => $m->body,
            'is_mine' => $m->sender_id === $authUserId,
            'product' => $m->product ? [
                'id' => $m->product->id,
                'name' => $m->product->name,
                'url' => $m->product->url,
                'image_url' => $m->product->image_url,
                'store' => $m->product->store,
                'current_price' => $m->product->current_price,
                'currency' => $m->product->currency,
            ] : null,
            'read_at' => $m->read_at?->toISOString(),
            'created_at' => $m->created_at->toISOString(),
        ];
    }
}
