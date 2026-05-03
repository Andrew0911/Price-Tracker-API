<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FriendsController extends BaseController
{
    /**
     * GET /users?search=
     * All users (except self) with their friendship_status relative to the auth user.
     */
    public function users(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $search   = $request->query('search');

        $query = User::where('id', '!=', $authUser->id)->select('id', 'name', 'email');

        if ($search) {
            $like = '%' . $search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        // Keyed collections for O(1) lookup
        $sent = $authUser->friends()->pluck('status', 'friend_id');
        $received = $authUser->receivedRequests()->pluck('status', 'user_id');

        $users = $query->orderBy('name')->get()->map(function (User $u) use ($sent, $received) {
            if (($s = $sent->get($u->id)) !== null) {
                $status = $s === Friendship::STATUS_ACCEPTED ? 'accepted' : 'pending_sent';
            } elseif (($r = $received->get($u->id)) !== null && $r === Friendship::STATUS_PENDING) {
                $status = 'pending_received';
            } else {
                $status = 'none';
            }

            return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'friendship_status' => $status];
        });

        return $this->sendSuccessResponse(data: $users);
    }

    /**
     * GET /friends
     * Accepted friends of the auth user.
     */
    public function index(Request $request): JsonResponse
    {
        $friends = $request->user()
            ->friends()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->with('friend:id,name,email')
            ->get()
            ->map(fn($f) => ['id' => $f->friend->id, 'name' => $f->friend->name, 'email' => $f->friend->email]);

        return $this->sendSuccessResponse(data: $friends);
    }

    /**
     * GET /friends/requests
     * Incoming pending friend requests.
     */
    public function requests(Request $request): JsonResponse
    {
        $incoming = $request->user()
            ->receivedRequests()
            ->where('status', Friendship::STATUS_PENDING)
            ->with('user:id,name,email')
            ->get()
            ->map(fn($f) => ['id' => $f->user->id, 'name' => $f->user->name, 'email' => $f->user->email]);

        return $this->sendSuccessResponse(data: $incoming);
    }

    /**
     * POST /friends/{user}
     * Send a friend request.
     */
    public function sendRequest(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            return $this->sendFailResponse('You cannot add yourself as a friend.', status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($authUser->friends()->where('friend_id', $user->id)->exists()) {
            return $this->sendFailResponse('Friend request already sent or already friends.', status: Response::HTTP_CONFLICT);
        }

        Friendship::create([
            'user_id'   => $authUser->id,
            'friend_id' => $user->id,
            'status'    => Friendship::STATUS_PENDING,
        ]);

        return $this->sendSuccessResponse('Friend request sent.');
    }

    /**
     * POST /friends/{user}/accept
     * Accept an incoming friend request.
     */
    public function accept(Request $request, User $user): JsonResponse
    {
        $authUser   = $request->user();
        $friendship = Friendship::where('user_id', $user->id)
            ->where('friend_id', $authUser->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->first();

        if (!$friendship) {
            return $this->sendFailResponse('No pending friend request found.', status: Response::HTTP_NOT_FOUND);
        }

        $friendship->update(['status' => Friendship::STATUS_ACCEPTED]);

        // Create the reverse row so both users see each other in their friends list
        Friendship::firstOrCreate(
            ['user_id' => $authUser->id, 'friend_id' => $user->id],
            ['status'  => Friendship::STATUS_ACCEPTED]
        );

        return $this->sendSuccessResponse('Friend request accepted.');
    }

    /**
     * DELETE /friends/{user}/decline
     * Decline an incoming friend request.
     */
    public function decline(Request $request, User $user): JsonResponse
    {
        $request->user()
            ->receivedRequests()
            ->where('user_id', $user->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->delete();

        return $this->sendSuccessResponse('Friend request declined.');
    }

    /**
     * DELETE /friends/{user}
     * Remove an accepted friend or cancel a sent request.
     */
    public function remove(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        Friendship::where(function ($q) use ($authUser, $user) {
            $q->where('user_id', $authUser->id)->where('friend_id', $user->id);
        })->orWhere(function ($q) use ($authUser, $user) {
            $q->where('user_id', $user->id)->where('friend_id', $authUser->id);
        })->delete();

        return $this->sendSuccessResponse('Removed successfully.');
    }
}
