<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password',
    'role', 'status', 'monday_id',
    'team', 'region', 'skills',
    'branch', 'address', 'account_name', 'brand', 'model',
    'serial_number', 'installation_date',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'skills'            => 'array',
            'installation_date' => 'date',
        ];
    }

    // -----------------------------------------------------------------
    // Role helpers
    // -----------------------------------------------------------------

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isTsp(): bool
    {
        return in_array($this->role, ['fse', 'its'], true);
    }

    public function isFse(): bool
    {
        return $this->role === 'fse';
    }

    public function isIts(): bool
    {
        return $this->role === 'its';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * The route name we should send this user to after login.
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            'superadmin'                         => 'admin.invites',
            'admin'                              => 'admin.kpi',
            'fse', 'its', 'manager'              => 'tsp.dashboard',
            default                              => 'dashboard',
        };
    }

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    /**
     * All service reports this user has authored (as a TSP). Used by
     * the per-TSP performance widget on the executive KPI dashboard.
     */
    public function serviceReports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ServiceReport::class);
    }
}
