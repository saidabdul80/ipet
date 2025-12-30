<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'store_id',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // Primary branch and store (for backward compatibility)
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Many-to-many relationships for multiple branches and stores
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')->withTimestamps();
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user')->withTimestamps();
    }

    public function customer(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function canAccessBranch($branchId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check primary branch
        if ($this->branch_id == $branchId) {
            return true;
        }

        // Check if user has access to this branch via many-to-many
        return $this->branches()->where('branch_id', $branchId)->exists();
    }

    public function canAccessStore($storeId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check primary store
        if ($this->store_id == $storeId) {
            return true;
        }

        // Check if user has access to this store via many-to-many
        if ($this->stores()->where('store_id', $storeId)->exists()) {
            return true;
        }

        // Branch managers can access all stores in their branch(es)
        if ($this->hasRole('Branch Manager')) {
            $store = Store::find($storeId);
            if ($store) {
                // Check primary branch
                if ($store->branch_id == $this->branch_id) {
                    return true;
                }
                // Check assigned branches
                return $this->branches()->where('branch_id', $store->branch_id)->exists();
            }
        }

        return false;
    }

    /**
     * Get all branch IDs the user has access to
     */
    public function getAccessibleBranchIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Branch::pluck('id')->toArray();
        }

        $branchIds = [];

        if ($this->branch_id) {
            $branchIds[] = $this->branch_id;
        }

        $branchIds = array_merge($branchIds, $this->branches()->pluck('branch_id')->toArray());

        return array_unique($branchIds);
    }

    /**
     * Get all store IDs the user has access to
     */
    public function getAccessibleStoreIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Store::pluck('id')->toArray();
        }

        $storeIds = [];

        if ($this->store_id) {
            $storeIds[] = $this->store_id;
        }

        $storeIds = array_merge($storeIds, $this->stores()->pluck('store_id')->toArray());

        // If Branch Manager, include all stores in accessible branches
        if ($this->hasRole('Branch Manager')) {
            $branchIds = $this->getAccessibleBranchIds();
            $branchStoreIds = Store::whereIn('branch_id', $branchIds)->pluck('id')->toArray();
            $storeIds = array_merge($storeIds, $branchStoreIds);
        }

        return array_unique($storeIds);
    }
}
