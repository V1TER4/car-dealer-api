<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->stores()->wherePivot('is_active', true)->exists();
    }

    public function create(User $user): bool
    {
        return $user->stores()->wherePivotIn('role', ['store_admin', 'store_manager', 'seller'])->wherePivot('is_active', true)->exists();
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $this->inStore($user, $vehicle) && $this->create($user);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $this->inStore($user, $vehicle) && $user->stores()->wherePivotIn('role', ['store_admin', 'store_manager'])->wherePivot('is_active', true)->exists();
    }

    private function inStore(User $user, Vehicle $vehicle): bool
    {
        return $user->stores()->whereKey($vehicle->store_id)->wherePivot('is_active', true)->exists();
    }
}
