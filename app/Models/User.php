<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The companies that belong to the user.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'user_companies')
                    ->withPivot(['role_id', 'is_default'])
                    ->withTimestamps();
    }

    /**
     * Get the user's default company.
     */
    public function defaultCompany()
    {
        return $this->companies()->wherePivot('is_default', true)->first();
    }

    /**
     * Check if user has access to a specific company.
     */
    public function hasAccessToCompany(int $companyId): bool
    {
        return $this->companies()->where('company_id', $companyId)->exists();
    }

    /**
     * Get the user's role for a specific company.
     */
    public function getRoleForCompany(int $companyId): ?string
    {
        $userCompany = $this->companies()->where('company_id', $companyId)->first();
        if (!$userCompany) {
            return null;
        }

        // Get role from pivot table
        $roleId = $userCompany->pivot->role_id;
        $role = \Spatie\Permission\Models\Role::find($roleId);

        return $role ? $role->name : null;
    }

    /**
     * Check if user has specific permission in a company context.
     */
    public function hasPermissionInCompany(string $permission, int $companyId): bool
    {
        if (!$this->hasAccessToCompany($companyId)) {
            return false;
        }

        $role = $this->getRoleForCompany($companyId);
        if (!$role) {
            return false;
        }

        return $this->hasPermissionTo($permission);
    }

    /**
     * Get active company from global context.
     */
    public function getActiveCompany(): ?\App\Models\Company
    {
        // Try to get from app instance (set by middleware)
        if (app()->has('current_company')) {
            return app('current_company');
        }

        // Fallback to default company
        return $this->defaultCompany();
    }

    /**
     * Get active company ID from global context.
     */
    public function getActiveCompanyId(): ?int
    {
        // Try to get from app instance (set by middleware)
        if (app()->has('current_company_id')) {
            return app('current_company_id');
        }

        // Fallback to default company
        $defaultCompany = $this->defaultCompany();
        return $defaultCompany ? $defaultCompany->id : null;
    }

    /**
     * Switch user's active company (updates default).
     */
    public function switchToCompany(int $companyId): bool
    {
        if (!$this->hasAccessToCompany($companyId)) {
            return false;
        }

        // Remove current default
        $this->companies()->updateExistingPivot(
            $this->companies()->pluck('company_id')->toArray(),
            ['is_default' => false]
        );

        // Set new default
        $this->companies()->updateExistingPivot($companyId, ['is_default' => true]);

        return true;
    }

    /**
     * Get all companies user has access to with role information.
     */
    public function getAccessibleCompaniesWithRoles(): \Illuminate\Support\Collection
    {
        return $this->companies()->where('is_active', true)->get()->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'role' => $this->getRoleForCompany($company->id),
                'is_default' => (bool) $company->pivot->is_default,
                'is_active' => (bool) $company->is_active,
            ];
        });
    }

    /**
     * Configure activity logging options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "User was {$eventName}");
    }
}
