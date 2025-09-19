<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CompanyContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Ensures that authenticated users have an active company context.
     * If no active company is set, defaults to their default/first active company.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is not authenticated, continue.
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Try to get active company id from session or resolve it.
        $activeCompanyId = Session::get('active_company_id');
        if (!$activeCompanyId) {
            $activeCompanyId = $this->resolveActiveCompanyId($request, $user);
        }

        // If we still don't have a company, user has no access to any company.
        if (!$activeCompanyId) {
            return $this->handleNoCompanyAccess($request);
        }

        // If user lost access to current company, try fallback to default/alternative.
        if (!$user->hasAccessToCompany($activeCompanyId)) {
            $defaultCompany = $user->defaultCompany();
            if ($defaultCompany) {
                Session::put('active_company_id', $defaultCompany->id);
                $activeCompanyId = $defaultCompany->id;
            } else {
                Session::forget('active_company_id');
                return $this->handleUnauthorizedCompanyAccess($request, (int) $activeCompanyId);
            }
        }

        // Load company and ensure it's active.
        $company = Company::find($activeCompanyId);
        if (!$company || !$company->is_active) {
            return $this->handleInactiveCompany($request, $company);
        }

        // Set context everywhere we need it.
        $this->setCompanyContext($company, $user);

        // Also attach to current request cycle & share with views (optional but handy).
        $request->attributes->set('active_company', $company);
        view()->share('activeCompany', $company);

        return $next($request);
    }

    /**
     * Resolve which company should be active for this request.
     */
    private function resolveActiveCompanyId(Request $request, $user): ?int
    {
        // 1) Explicit switch via request param
        if ($request->has('switch_company_id')) {
            $companyId = (int) $request->input('switch_company_id');
            if ($user->hasAccessToCompany($companyId)) {
                Session::put('active_company_id', $companyId);
                return $companyId;
            }
            // if explicitly asked to switch to something they don't have, treat as unauthorized
            return null;
        }

        // 2) Previously selected company in session
        $sessionCompanyId = Session::get('active_company_id');
        if ($sessionCompanyId && $user->hasAccessToCompany($sessionCompanyId)) {
            return (int) $sessionCompanyId;
        }

        // 3) User's default company
        $defaultCompany = $user->defaultCompany();
        if ($defaultCompany) {
            Session::put('active_company_id', $defaultCompany->id);
            return (int) $defaultCompany->id;
        }

        // 4) First available active company
        $firstCompany = $user->companies()->where('is_active', true)->first();
        if ($firstCompany) {
            Session::put('active_company_id', $firstCompany->id);
            return (int) $firstCompany->id;
        }

        // Nothing available
        return null;
    }

    /**
     * Set company context in various global locations.
     */
    private function setCompanyContext(Company $company, $user): void
    {
        // Session
        Session::put('active_company_id', $company->id);
        Session::put('active_company', $company);

        // App config / service container, for helpers like currentCompany()
        config(['app.current_company_id' => $company->id]);
        config(['app.current_company' => $company]);

        app()->instance('current_company', $company);
        app()->instance('current_company_id', $company->id);
    }

    /**
     * Handle case where user has no company access.
     */
    private function handleNoCompanyAccess(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error'   => 'No company access',
                'message' => 'You do not have access to any company. Please contact your administrator.',
            ], Response::HTTP_FORBIDDEN);
        }

        Auth::logout();
        return redirect()->route('login')->withErrors([
            'access' => 'You do not have access to any company. Please contact your administrator.',
        ]);
    }

    /**
     * Handle case where user tries to access unauthorized company.
     */
    private function handleUnauthorizedCompanyAccess(Request $request, int $companyId): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error'   => 'Unauthorized company access',
                'message' => "You do not have access to company ID {$companyId}.",
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect()->back()->withErrors([
            'company' => 'You do not have access to the selected company.',
        ]);
    }

    /**
     * Handle case where company exists but is inactive (or missing).
     */
    private function handleInactiveCompany(Request $request, ?Company $company): Response
    {
        $companyName = $company ? $company->name : 'Unknown Company';

        if ($request->expectsJson()) {
            return response()->json([
                'error'   => 'Inactive company',
                'message' => "The company '{$companyName}' is currently inactive.",
            ], Response::HTTP_FORBIDDEN);
        }

        // Try to switch to an alternative active company
        $user = Auth::user();
        $alternativeCompany = $user->companies()->where('is_active', true)->first();

        if ($alternativeCompany) {
            Session::put('active_company_id', $alternativeCompany->id);
            return redirect()->back()->with('warning',
                "Company '{$companyName}' is inactive. Switched to {$alternativeCompany->name}."
            );
        }

        // No alternatives
        return $this->handleNoCompanyAccess($request);
    }
}
