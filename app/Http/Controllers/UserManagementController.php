<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $currentCompany = currentCompany();

        if (!$currentCompany) {
            abort(403, 'No company context available.');
        }

        $query = $currentCompany->users()->withPivot(['role_id', 'is_default', 'created_at']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->wherePivot('role_id', $request->get('role'));
        }

        $users = $query->paginate(15);
        $roles = Role::all();

        $stats = [
            'total' => $currentCompany->users()->count(),
            'active' => $currentCompany->users()->count(), // All users are active since we don't use soft deletes
            'new_this_month' => $currentCompany->users()
                ->wherePivot('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        $filters = [
            'search' => $request->get('search', ''),
            'role' => $request->get('role', ''),
            'status' => $request->get('status', ''),
        ];

        return inertia('users/Index', [
            'users' => $users,
            'company' => $currentCompany,
            'roles' => $roles,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $currentCompany = currentCompany();
        $roles = Role::all();

        return inertia('users/Create', [
            'company' => $currentCompany,
            'roles' => $roles,
        ]);
    }

    public function store(CreateUserRequest $request)
    {
        $currentCompany = currentCompany();

        DB::beginTransaction();

        try {
            if ($request->input('action') === 'create_new') {
                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => bcrypt($request->input('password')),
                ]);
            } else {
                $user = User::findOrFail($request->input('user_id'));
            }

            $currentCompany->users()->attach($user->id, [
                'role_id' => $request->input('role_id'),
                'is_default' => $request->boolean('is_default'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($request->boolean('is_default')) {
                DB::table('user_companies')
                    ->where('user_id', $user->id)
                    ->where('company_id', '!=', $currentCompany->id)
                    ->update(['is_default' => false]);
            }

            DB::commit();

            return redirect()->route('users.index')->with('success', 'User assigned successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['general' => $e->getMessage()])->withInput();
        }
    }

    public function show(User $user)
    {
        $currentCompany = currentCompany();
        $roles = Role::all();

        $userCompanies = DB::table('user_companies')
            ->join('companies', 'companies.id', '=', 'user_companies.company_id')
            ->leftJoin('roles', 'roles.id', '=', 'user_companies.role_id')
            ->where('user_companies.user_id', $user->id)
            ->select([
                'companies.name as company_name',
                'roles.name as role_name',
                'user_companies.is_default',
                'user_companies.created_at as assigned_at',
            ])
            ->get();

        return inertia('users/Show', [
            'user' => $user,
            'company' => $currentCompany,
            'roles' => $roles,
            'userCompanies' => $userCompanies,
        ]);
    }

    public function edit(User $user)
    {
        $currentCompany = currentCompany();
        $roles = Role::all();

        return inertia('users/Edit', [
            'user' => $user,
            'company' => $currentCompany,
            'roles' => $roles,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $currentCompany = currentCompany();

        DB::beginTransaction();

        try {
            $user->update([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
            ]);

            $currentCompany->users()->updateExistingPivot($user->id, [
                'role_id' => $request->input('role_id'),
                'is_default' => $request->boolean('is_default'),
                'updated_at' => now(),
            ]);

            if ($request->boolean('is_default')) {
                DB::table('user_companies')
                    ->where('user_id', $user->id)
                    ->where('company_id', '!=', $currentCompany->id)
                    ->update(['is_default' => false]);
            }

            DB::commit();

            return redirect()->route('users.show', $user)->with('success', 'User updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['general' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(User $user)
    {
        $currentCompany = currentCompany();
        $currentCompany->users()->detach($user->id);

        return redirect()->route('users.index')->with('success', 'User removed successfully!');
    }

    public function available(Request $request)
    {
        $currentCompany = currentCompany();
        $search = $request->get('search', '');

        $users = User::where(function ($query) use ($search) {
                if ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                }
            })
            ->whereDoesntHave('companies', function ($query) use ($currentCompany) {
                $query->where('company_id', $currentCompany->id);
            })
            ->limit(20)
            ->get(['id', 'name', 'email', 'created_at']);

        return response()->json(['data' => $users]);
    }
}
