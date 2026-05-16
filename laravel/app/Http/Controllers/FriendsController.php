<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if ($authUser->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use social features.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        $query = User::where('id', '!=', $authUser->id)
            ->where('is_admin', false)
            ->select('id', 'name', 'email');

        if ($search) {
            $query->where('email', $search);
        } else {
            $acceptedFriendIds = $authUser->friends()
                ->where('status', Friendship::STATUS_ACCEPTED)
                ->pluck('friend_id');

            $query->where(function ($q) use ($acceptedFriendIds) {
                $q->where('private_account', false)
                    ->orWhereIn('id', $acceptedFriendIds);
            });
        }

        // Keyed collections for O(1) lookup
        $sent = $authUser->friends()->pluck('status', 'friend_id');
        $received = $authUser->receivedRequests()->pluck('status', 'user_id');

        $users = $query->orderBy('name')->get()->map(function (User $u) use ($sent, $received) {
            $sentStatus = $sent->get($u->id);
            $receivedStatus = $received->get($u->id);

            if (
                $sentStatus === Friendship::STATUS_ACCEPTED
                || $receivedStatus === Friendship::STATUS_ACCEPTED
            ) {
                $status = 'accepted';
            } elseif ($sentStatus === Friendship::STATUS_PENDING) {
                $status = 'pending_sent';
            } elseif ($receivedStatus === Friendship::STATUS_PENDING) {
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
        if ($request->user()->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use social features.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        $authUser = $request->user();

        $acceptedFriendIds = Friendship::query()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id)
                    ->orWhere('friend_id', $authUser->id);
            })
            ->get()
            ->map(fn (Friendship $friendship) => $friendship->user_id === $authUser->id
                ? $friendship->friend_id
                : $friendship->user_id)
            ->unique()
            ->values();

        $friends = User::query()
            ->whereIn('id', $acceptedFriendIds)
            ->where('is_admin', false)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(fn (User $friend) => [
                'id' => $friend->id,
                'name' => $friend->name,
                'email' => $friend->email,
            ]);

        return $this->sendSuccessResponse(data: $friends);
    }

    /**
     * GET /friends/requests
     * Incoming pending friend requests.
     */
    public function requests(Request $request): JsonResponse
    {
        if ($request->user()->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use social features.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        $incoming = $request->user()
            ->receivedRequests()
            ->where('status', Friendship::STATUS_PENDING)
            ->with(['user' => fn ($query) => $query
                ->select('id', 'name', 'email')
                ->where('is_admin', false)])
            ->get()
            ->filter(fn ($f) => $f->user !== null)
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

        if ($authUser->is_admin || $user->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use social features.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        if ($authUser->id === $user->id) {
            return $this->sendFailResponse('You cannot add yourself as a friend.', status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $existingFriendship = Friendship::query()
            ->where(function ($query) use ($authUser, $user) {
                $query->where('user_id', $authUser->id)
                    ->where('friend_id', $user->id);
            })
            ->orWhere(function ($query) use ($authUser, $user) {
                $query->where('user_id', $user->id)
                    ->where('friend_id', $authUser->id);
            })
            ->exists();

        if ($existingFriendship) {
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

        if ($authUser->is_admin || $user->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use social features.',
                status: Response::HTTP_FORBIDDEN,
            );
        }
        $accepted = DB::transaction(function () use ($authUser, $user): bool {
            $friendship = Friendship::where('user_id', $user->id)
                ->where('friend_id', $authUser->id)
                ->where('status', Friendship::STATUS_PENDING)
                ->lockForUpdate()
                ->first();

            if (!$friendship) {
                return false;
            }

            $friendship->update(['status' => Friendship::STATUS_ACCEPTED]);

            // Keep the pair symmetric even if a reverse pending row already exists
            // from an earlier send/cancel/re-send sequence.
            Friendship::updateOrCreate(
                ['user_id' => $authUser->id, 'friend_id' => $user->id],
                ['status'  => Friendship::STATUS_ACCEPTED]
            );

            return true;
        });

        if (!$accepted) {
            return $this->sendFailResponse('No pending friend request found.', status: Response::HTTP_NOT_FOUND);
        }

        return $this->sendSuccessResponse('Friend request accepted.');
    }

    /**
     * DELETE /friends/{user}/decline
     * Decline an incoming friend request.
     */
    public function decline(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is_admin || $user->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use social features.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

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

        if ($authUser->is_admin || $user->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts cannot use social features.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        Friendship::where(function ($q) use ($authUser, $user) {
            $q->where('user_id', $authUser->id)->where('friend_id', $user->id);
        })->orWhere(function ($q) use ($authUser, $user) {
            $q->where('user_id', $user->id)->where('friend_id', $authUser->id);
        })->delete();

        return $this->sendSuccessResponse('Removed successfully.');
    }
}
