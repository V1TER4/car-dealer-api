<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StoreUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeManagement($request);

        return response()->json(['data' => $request->attributes->get('store')->users()->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');
        $this->authorizeManagement($request);
        $role = $this->validatedRole($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:180', 'unique:users,email'], 'password' => ['required', 'string', 'min:8']]);
        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);
        $user->stores()->attach($store, ['role' => $role, 'is_active' => true]);

        return response()->json(['data' => $user->fresh()], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $store = $request->attributes->get('store');
        $this->authorizeManagement($request);
        abort_unless($store->users()->whereKey($user->id)->exists(), 404);
        $role = $this->validatedRole($request, false);
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:120'], 'email' => ['sometimes', 'email', 'max:180', Rule::unique('users')->ignore($user->id)], 'password' => ['sometimes', 'string', 'min:8'], 'is_active' => ['sometimes', 'boolean']]);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        if ($role) {
            $store->users()->updateExistingPivot($user, ['role' => $role]);
        }

        return response()->json(['data' => $user->fresh()]);
    }

    private function validatedRole(Request $request, bool $required = true): ?string
    {
        $rules = ['role' => [$required ? 'required' : 'sometimes', Rule::in(['store_admin', 'store_manager', 'seller'])]];
        $role = $request->validate($rules)['role'] ?? null;
        $currentRole = $request->user()->stores()->whereKey($request->attributes->get('store')->id)->first()?->pivot?->role;
        if ($role === 'store_admin' && $currentRole !== 'store_admin') {
            abort(403, 'Somente administradores da loja podem atribuir este papel.');
        }

        return $role;
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()->stores()->whereKey($request->attributes->get('store')->id)->wherePivotIn('role', ['store_admin', 'store_manager'])->exists(), 403);
    }
}
