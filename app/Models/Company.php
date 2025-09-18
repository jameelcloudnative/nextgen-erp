<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Company extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'description',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'currency',
        'timezone',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Users that belong to this company
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_companies')
                    ->withPivot(['role_id', 'is_default'])
                    ->withTimestamps();
    }

    /**
     * Configure activity logging options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'description', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Company was {$eventName}");
    }

    /**
     * Assign a user to this company with a role
     */
    public function assignUser($user, $roleId, bool $isDefault = false)
    {
        // Attach user to company
        $this->users()->attach($user->id, [
            'role_id' => $roleId,
            'is_default' => $isDefault,
        ]);

        // Log the assignment
        $role = \Spatie\Permission\Models\Role::find($roleId);
        if ($role) {
            \App\Services\ActivityLogService::logUserCompanyAssignment(
                $user,
                $this,
                $role->name,
                'assigned'
            );
        }
    }

    /**
     * Remove a user from this company
     */
    public function removeUser($user)
    {
        // Get user's current role before removing
        $userCompany = $this->users()->where('user_id', $user->id)->first();
        $role = null;
        if ($userCompany && $userCompany->pivot->role_id) {
            $role = \Spatie\Permission\Models\Role::find($userCompany->pivot->role_id);
        }

        // Remove user from company
        $this->users()->detach($user->id);

        // Log the removal
        if ($role) {
            \App\Services\ActivityLogService::logUserCompanyAssignment(
                $user,
                $this,
                $role->name,
                'removed'
            );
        }
    }
}
