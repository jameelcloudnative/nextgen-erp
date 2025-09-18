<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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
}
