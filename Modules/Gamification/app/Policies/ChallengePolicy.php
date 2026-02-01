<?php

namespace Modules\Gamification\Policies;

use Modules\Auth\Models\User;
use Modules\Gamification\Models\Challenge;

class ChallengePolicy
{
    
    public function viewAny(?User $user): bool
    {
        return true; 
    }

    public function view(?User $user, Challenge $challenge): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin') || $user->hasRole('Admin');
    }

    public function update(User $user, Challenge $challenge): bool
    {
        return $user->hasRole('Superadmin') || $user->hasRole('Admin');
    }

    public function delete(User $user, Challenge $challenge): bool
    {
        return $user->hasRole('Superadmin');
    }

    public function claim(User $user, Challenge $challenge): bool
    {
        return true; 
    }
}
