<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminUsersController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->is_admin) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        $search = $request->query('search');

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->select('id', 'name', 'email', 'email_verified_at', 'private_account', 'is_admin', 'created_at')
            ->orderBy('name')
            ->paginate($request->integer('perPage', 10));

        return $this->sendSuccessResponse(data: $users);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->is_admin) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        return $this->sendSuccessResponse(data: $user);
    }

    public function promote(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->is_admin) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        if ($authUser->id === $user->id) {
            return $this->sendFailResponse(
                'You are already an admin.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->is_admin) {
            return $this->sendSuccessResponse(
                message: 'User is already an admin.',
                data: $user->only(['id', 'name', 'email', 'email_verified_at', 'private_account', 'is_admin', 'created_at']),
            );
        }

        $user->forceFill(['is_admin' => true])->save();

        return $this->sendSuccessResponse(
            message: 'User promoted to admin successfully.',
            data: $user->fresh()->only(['id', 'name', 'email', 'email_verified_at', 'private_account', 'is_admin', 'created_at']),
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->is_admin) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        if ($authUser->id === $user->id) {
            return $this->sendFailResponse(
                'You cannot delete your own admin account.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->sendSuccessResponse('User account deleted successfully.');
    }
}
