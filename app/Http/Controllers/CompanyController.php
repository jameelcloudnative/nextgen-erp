<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    /**
     * Check if the current user can access the given company
     */
    private function authorizeCompanyAccess(Company $company)
    {
        $user = Auth::user();

        if (!$user->canAccessCompany($company->id)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this company.');
        }
    }

    /**
     * Check if user can create/manage companies (admin only)
     */
    private function authorizeCompanyManagement()
    {
        $user = Auth::user();

        // Only super admin can create/manage companies
        if (!$this->isAdminUser($user)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to manage companies.');
        }
    }

    /**
     * Check if user is admin (same logic as in User model)
     */
    private function isAdminUser($user)
    {
        return $user->name === 'Admin User' || strpos(strtolower($user->name), 'admin') !== false;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        // Get companies user has access to
        $companies = $user->accessibleCompanies()
            ->select(['id', 'name', 'code', 'email', 'phone', 'currency', 'is_active'])
            ->paginate(10);

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorizeCompanyManagement();

        return Inertia::render('Companies/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeCompanyManagement();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:companies,code',
            'description' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:50',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:20',
        ]);

        $company = Company::create($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        $this->authorizeCompanyAccess($company);

        return Inertia::render('Companies/Show', [
            'company' => $company->load('users:id,name,email,company_id'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        $this->authorizeCompanyAccess($company);
        $this->authorizeCompanyManagement(); // Only admins can edit

        return Inertia::render('Companies/Edit', [
            'company' => $company,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        $this->authorizeCompanyAccess($company);
        $this->authorizeCompanyManagement(); // Only admins can update

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:10', Rule::unique('companies')->ignore($company->id)],
            'description' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:50',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:20',
            'is_active' => 'boolean',
        ]);

        $company->update($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        // Prevent deletion if company has users
        if ($company->users()->count() > 0) {
            return back()->withErrors(['company' => 'Cannot delete company with associated users.']);
        }

        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }

    /**
     * Switch active company for the current user.
     */
    public function switch(Request $request)
    {
        $companyId = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ])['company_id'];

        $user = Auth::user();

        // Check if user has access to this company
        if ($user->canAccessCompany($companyId)) {
            // Update session
            session(['active_company_id' => $companyId]);

            return response()->json([
                'success' => true,
                'company' => Company::find($companyId, ['id', 'name', 'code']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access denied to this company.',
        ], 403);
    }

    /**
     * Get user's accessible companies.
     */
    public function accessible()
    {
        $user = Auth::user();
        $companies = $user->accessibleCompanies()
            ->select(['id', 'name', 'code', 'currency'])
            ->get();

        return response()->json([
            'companies' => $companies,
            'active_company_id' => app('active_company_id'),
        ]);
    }
}
