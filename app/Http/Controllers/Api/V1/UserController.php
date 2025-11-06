<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserStatus;
use App\Http\Controllers\Concerns\RespondsWithJsonError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserIndexRequest;
use App\Http\Requests\Api\UserStoreRequest;
use App\Http\Requests\Api\UserUpdateRequest;
use App\Http\Resources\UserResource as UserResourceData;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UserController extends Controller
{
    use RespondsWithJsonError;

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(UserIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 25);
        $perPage = min(max($perPage, 1), 100);

        $query = User::query()
            ->with('roles')
            ->orderBy('name');

        if (($validated['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        } elseif (($validated['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        }

        if (! empty($validated['search'])) {
            $term = $validated['search'];
            $query->where(function (Builder $builder) use ($term): void {
                $builder
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            });
        }

        if (! empty($validated['role'])) {
            $query->whereHas('roles', function (Builder $builder) use ($validated): void {
                $builder->where('name', $validated['role']);
            });
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $users = $query->paginate($perPage);
        $users->appends($request->query());

        return response()->json([
            'data' => UserResourceData::collection($users->items())->resolve(),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
            'links' => [
                'self' => $request->fullUrl(),
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
        ]);
    }

    public function store(UserStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $roles = Arr::get($data, 'roles', []);
        unset($data['roles'], $data['password_confirmation']);

        $user = new User();
        $user->fill($data);
        $user->status = $data['status'] ?? UserStatus::ACTIVE->value;
        $user->save();

        if (! empty($roles)) {
            $user->syncRoles($roles);
        }

        $user->load('roles');

        AuditLogger::record(
            $request->user(),
            'user.created',
            $user,
            $request,
            [
                'status' => $user->status instanceof UserStatus ? $user->status->value : $user->status,
                'roles' => $user->roles->pluck('name')->all(),
            ]
        );

        return response()->json([
            'data' => UserResourceData::make($user)->resolve(),
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $user->load('roles');

        return response()->json([
            'data' => UserResourceData::make($user)->resolve(),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? null;
        unset($data['roles'], $data['password_confirmation']);

        if (array_key_exists('password', $data) && $data['password'] === null) {
            unset($data['password']);
        }

        $dirty = [];

        if (! empty($data)) {
            $user->fill($data);
            $dirty = $user->getDirty();
            if (! empty($dirty)) {
                $user->save();
            }
        }

        $roleChanges = false;

        if ($roles !== null) {
            $user->syncRoles($roles);
            $roleChanges = true;
        }

        $user->load('roles');

        if (! empty($dirty) || $roleChanges) {
            unset($dirty['password']);

            AuditLogger::record(
                $request->user(),
                'user.updated',
                $user,
                $request,
                [
                    'changed' => array_keys($dirty),
                    'roles' => $user->roles->pluck('name')->all(),
                ]
            );
        }

        return response()->json([
            'data' => UserResourceData::make($user)->resolve(),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return $this->jsonError('USER_DELETE_FORBIDDEN', 'You cannot delete your own account.', 422);
        }

        if (! $user->delete()) {
            return $this->jsonError('USER_DELETE_FAILED', 'Unable to delete user.', 500);
        }

        AuditLogger::record(
            $request->user(),
            'user.deleted',
            $user,
            $request,
            [
                'status' => $user->status instanceof UserStatus ? $user->status->value : $user->status,
            ]
        );

        return response()->json([], 204);
    }

    public function restore(Request $request, int $user): JsonResponse
    {
        $record = User::withTrashed()->find($user);

        if ($record === null) {
            return $this->jsonError('USER_NOT_FOUND', 'User not found.', 404);
        }

        $this->authorize('restore', $record);

        if (! $record->trashed()) {
            return $this->jsonError('USER_NOT_DELETED', 'User is not deleted.', 422);
        }

        $record->restore();
        $record->load('roles');

        AuditLogger::record(
            $request->user(),
            'user.restored',
            $record,
            $request,
            [
                'status' => $record->status instanceof UserStatus ? $record->status->value : $record->status,
            ]
        );

        return response()->json([
            'data' => UserResourceData::make($record)->resolve(),
        ]);
    }
}
