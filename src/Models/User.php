<?php

namespace Dcplibrary\EntraSSO\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Base User model with Entra SSO support
 * 
 * Your app's User model should extend this class to inherit
 * all Entra SSO functionality and helper methods.
 */
class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'entra_id',
        'role',
        'entra_groups',
        'entra_custom_claims',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'entra_groups' => 'array',
            'entra_custom_claims' => 'array',
        ]);
    }

    /**
     * Get the fillable attributes for the model.
     *
     * Automatically merges parent fillable with child fillable,
     * so child models don't need to re-declare Entra fields.
     *
     * @return array
     */
    public function getFillable()
    {
        // Get child's fillable (from app User model)
        $childFillable = $this->fillable;

        // Merge with parent's fillable (Entra fields)
        return array_merge($this->fillable, [
            'entra_id',
            'role',
            'entra_groups',
            'entra_custom_claims',
        ]);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a manager
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if user belongs to a specific Entra group
     */
    public function inGroup(string $groupName): bool
    {
        return in_array($groupName, $this->entra_groups ?? []);
    }

    /**
     * Check if user belongs to any of the given Entra groups
     */
    public function inAnyGroup(array $groups): bool
    {
        $userGroups = $this->entra_groups ?? [];
        return !empty(array_intersect($groups, $userGroups));
    }

    /**
     * Get a custom claim value
     */
    public function getCustomClaim(string $claimName, $default = null)
    {
        return $this->entra_custom_claims[$claimName] ?? $default;
    }

    /**
     * Check if user has a specific custom claim
     */
    public function hasCustomClaim(string $claimName): bool
    {
        return isset($this->entra_custom_claims[$claimName]);
    }

    /**
     * Get all Entra groups
     */
    public function getEntraGroups(): array
    {
        return $this->entra_groups ?? [];
    }

    /**
     * Get all custom claims
     */
    public function getCustomClaims(): array
    {
        return $this->entra_custom_claims ?? [];
    }
}
