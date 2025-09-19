<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use App\Services\ActivityLogService;
use App\Services\AuthorizationService;

class UserRoleController extends Controller
{
    /**
     * Assign user to a company with a specific role.
     */
    public function assignToCompany(Request $request, User $user)
    {
        // Only Super Admin or Company Admin can assign users
        AuthorizationService::authorizeAny(
            ['view-companies', 'edit-users'],
            'You do not have permission to assign users to companies.'
        );

        $validated = $request->validate([
            'company_id'         => 'required|integer|exists:companies,id',
            'role'               => 'required|string|exists:roles,name',
            'is_default_company' => 'boolean',
        ]);

        $currentUser    = Auth::user();
        $targetCompany  = Company::findOrFail($validated['company_id']);

        // If not Super Admin, can only assign to current company
        if (!AuthorizationService::isSuperAdmin()) {
            $activeCompany = $currentUser->getActiveCompany();
            if ((int) $validated['company_id'] !== (int) $activeCompany->id) {
                return response()->json(['error' => 'You can only assign users to your current company'], 403);
            }
        }

        // Company must be active
        if (property_exists($targetCompany, 'is_active') && !$targetCompany->is_active) {
            return response()->json(['error' => 'Cannot assign user to inactive company'], 400);
        }

        // If already assigned
        if ($user->hasAccessToCompany($targetCompany->id)) {
            return response()->json(['error' => 'User is already assigned to this company'], 400);
        }

        $role = Role::where('name', $validated['role'])->first();
        if (!$role) {
            return response()->json(['error' => 'Invalid role specified'], 400);
        }

        // Attach
        $user->companies()->attach($targetCompany->id, [
            'role_id'    => $role->id,
            'is_default' => $request->boolean('is_default_company', false),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log activity
        activity()
            ->causedBy($currentUser)
            ->performedOn($user)
            ->withProperties([
                'company_id'   => $targetCompany->id,
                'company_name' => $targetCompany->name,
                'role'         => $role->name,
                'is_default'   => $request->boolean('is_default_company', false),
            ])
            ->log('User assigned to company');

        return response()->json([
            'message'    => "User assigned to {$targetCompany->name} successfully",
            'assignment' => [
                'user_id'            => $user->id,
                'user_name'          => $user->name,
                'company_id'         => $targetCompany->id,
                'company_name'       => $targetCompany->name,
                'role'               => $role->name,
                'is_default_company' => $request->boolean('is_default_company', false),
            ],
        ]);
    }

    /**
     * Update user's role in a company.
     */
    public function updateRole(Request $request, User $user)
    {
        AuthorizationService::authorize('edit-users', 'You do not have permission to update user roles.');

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'role'       => 'required|string|exists:roles,name',
        ]);

        $currentUser   = Auth::user();
        $targetCompany = Company::findOrFail($validated['company_id']);

        // If not Super Admin, can only modify roles in current company
        if (!AuthorizationService::isSuperAdmin()) {
            $activeCompany = $currentUser->getActiveCompany();
            if ((int) $validated['company_id'] !== (int) $activeCompany->id) {
                return response()->json(['error' => 'You can only modify roles in your current company'], 403);
            }
        }

        if (!$user->hasAccessToCompany($targetCompany->id)) {
            return response()->json(['error' => 'User is not assigned to this company'], 400);
        }

        $oldRoleId = $user->companies()
            ->where('company_id', $targetCompany->id)
            ->first()?->pivot->role_id;

        $oldRole = $oldRoleId ? Role::find($oldRoleId)?->name : null;

        $newRole = Role::where('name', $validated['role'])->first();
        if (!$newRole) {
            return response()->json(['error' => 'Invalid role specified'], 400);
        }

        // Update role in pivot
        $user->companies()->updateExistingPivot($targetCompany->id, [
            'role_id'    => $newRole->id,
            'updated_at' => now(),
        ]);

        // Log role change
        ActivityLogService::logRoleChange($user, $oldRole, $newRole->name, $targetCompany);

        return response()->json([
            'message'    => 'User role updated successfully',
            'role_change'=> [
                'user_id'      => $user->id,
                'user_name'    => $user->name,
                'company_id'   => $targetCompany->id,
                'company_name' => $targetCompany->name,
                'old_role'     => $oldRole,
                'new_role'     => $newRole->name,
            ],
        ]);
    }

    /**
     * Remove user from a company.
     */
    public function removeFromCompany(Request $request, User $user)
    {
        AuthorizationService::authorize('delete-users', 'You do not have permission to remove users from companies.');

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        $currentUser   = Auth::user();
        $targetCompany = Company::findOrFail($validated['company_id']);

        // If not Super Admin, can only remove from current company
        if (!AuthorizationService::isSuperAdmin()) {
            $activeCompany = $currentUser->getActiveCompany();
            if ((int) $validated['company_id'] !== (int) $activeCompany->id) {
                return response()->json(['error' => 'You can only remove users from your current company'], 403);
            }
        }

        if (!$user->hasAccessToCompany($targetCompany->id)) {
            return response()->json(['error' => 'User is not assigned to this company'], 400);
        }

        // Prevent removing if this is user's only company
        if ($user->companies()->count() <= 1) {
            return response()->json(['error' => 'Cannot remove user from their only company'], 400);
        }

        // Detach
        $user->companies()->detach($targetCompany->id);

        ActivityLogService::logBusinessOperation(
            'user_removed_from_company',
            "User '{$user->name}' removed from '{$targetCompany->name}'",
            $user,
            [
                'company_id'   => $targetCompany->id,
                'company_name' => $targetCompany->name,
            ]
        );

        return response()->json([
            'message' => "User removed from {$targetCompany->name} successfully",
        ]);
    }

    /**
     * Set default company for a user.
     */
    public function setDefaultCompany(Request $request, User $user)
    {
        AuthorizationService::authorize('edit-users', 'You do not have permission to set default company.');

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        $currentUser   = Auth::user();
        $targetCompany = Company::findOrFail($validated['company_id']);

        // Verify current user has access to the target company
        if (!$currentUser->hasAccessToCompany($targetCompany->id)) {
            return response()->json(['message' => 'You do not have access to this company.'], 403);
        }

        // Verify subject user has access to the target company
        if (!$user->hasAccessToCompany($targetCompany->id)) {
            return response()->json(['message' => 'User is not assigned to this company'], 400);
        }

        // Remove default from all companies for this user
        $user->companies()->updateExistingPivot(
            $user->companies()->pluck('companies.id')->toArray(),
            ['is_default' => false]
        );

        // Set new default company
        $user->companies()->updateExistingPivot($targetCompany->id, [
            'is_default' => true,
            'updated_at' => now(),
        ]);

        ActivityLogService::logBusinessOperation(
            'default_company_changed',
            "User '{$user->name}' default company changed to '{$targetCompany->name}'",
            $user,
            [
                'company_id'   => $targetCompany->id,
                'company_name' => $targetCompany->name,
            ]
        );

        return response()->json([
            'message' => "Default company set to {$targetCompany->name}",
            'default_company' => [
                'id'   => $targetCompany->id,
                'name' => $targetCompany->name,
                'code' => $targetCompany->code ?? null,
            ],
        ]);
    }

    /**
     * Get user's roles across accessible companies.
     */
    public function getRoles(User $user)
    {
        $currentUser    = Auth::user();
        $currentCompany = currentCompany();

        if ($currentCompany && !$user->hasAccessToCompany($currentCompany->id)) {
            return response()->json(['message' => 'User not found in current company.'], 404);
        }

        $user->load([
            'companies' => function ($query) use ($currentUser) {
                $query->whereIn(
                    'companies.id',
                    $currentUser->companies()->pluck('companies.id')
                )->withPivot(['role_id', 'is_default', 'created_at', 'updated_at']);
            },
            'companies.users' => function ($query) {
                $query->limit(3);
            },
        ]);

        $availableRoles = Role::all();

        $companyAssignments = $user->companies->map(function ($company) use ($availableRoles) {
            $role = $availableRoles->find($company->pivot->role_id);

            return [
                'company_id'    => $company->id,
                'company_name'  => $company->name,
                'company_code'  => $company->code ?? null,
                'role_id'       => $company->pivot->role_id,
                'role_name'     => $role?->name ?? 'Unknown',
                'is_default'    => (bool) $company->pivot->is_default,
                'assigned_at'   => $company->pivot->created_at,
                'updated_at'    => $company->pivot->updated_at,
                'team_count'    => $company->users->count(),
            ];
        });

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'created_at' => $user->created_at,
            ],
            'company_assignments' => $companyAssignments,
            'available_roles'     => $availableRoles,
            'statistics' => [
                'total_companies' => $companyAssignments->count(),
                'default_company' => $companyAssignments->firstWhere('is_default', true),
                'admin_companies' => $companyAssignments->where('role_name', 'Admin')->count(),
            ],
        ]);
    }

    /**
     * Switch current user to a different company context.
     */
    public function switchCompany(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $user          = Auth::user();
        $targetCompany = Company::findOrFail($validated['company_id']);

        if (!$user->hasAccessToCompany($targetCompany->id)) {
            return response()->json(['message' => 'You do not have access to this company.'], 403);
            }

        $user->switchToCompany($targetCompany->id);

        activity()
            ->causedBy($user)
            ->withProperties([
                'company_id'   => $targetCompany->id,
                'company_name' => $targetCompany->name,
            ])
            ->log('User switched company context');

        return response()->json([
            'message'          => 'Company context switched successfully.',
            'current_company'  => $targetCompany,
        ]);
    }
}
