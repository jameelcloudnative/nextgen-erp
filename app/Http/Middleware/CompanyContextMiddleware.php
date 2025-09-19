<?php<?php



namespace App\Http\Middleware;namespace App\Http\Middleware;



use Closure;use Closure;

use Illuminate\Http\Request;use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Session;use Illuminate\Support\Facades\Session;

use App\Models\Company;use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Response;

class CompanyContextMiddleware

class CompanyContextMiddleware{

{    /**

    /**     * Handle an incoming request.

     * Handle an incoming request.     *

     *     * Ensures that authenticated users have an active company context.

     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next     * If no active company is set, defaults to their default company.

     */     */

    public function handle(Request $request, Closure $next): Response    public function handle(Request $request, Closure $next): Response

    {    {

        // Skip if not authenticated        if (Auth::check()) {

        if (!Auth::check()) {            $user = Auth::user();

            return $next($request);            $activeCompanyId = Session::get('active_company_id');

        }

            // If no active company is set, use the user's default company

        $user = Auth::user();            if (!$activeCompanyId) {

        $activeCompanyId = $this->resolveActiveCompanyId($request, $user);                $defaultCompany = $user->defaultCompany();



        // If no active company could be resolved, handle appropriately                if ($defaultCompany) {

        if (!$activeCompanyId) {                    Session::put('active_company_id', $defaultCompany->id);

            return $this->handleNoCompanyAccess($request);                    $activeCompanyId = $defaultCompany->id;

        }                } else {

                    // User has no companies assigned - this shouldn't happen in normal flow

        // Verify user has access to the resolved company                    // but we'll handle it gracefully

        if (!$user->hasAccessToCompany($activeCompanyId)) {                    abort(403, 'No company access assigned. Please contact your administrator.');

            return $this->handleUnauthorizedCompanyAccess($request, $activeCompanyId);                }

        }            }



        // Load the company and set context            // Verify user still has access to the active company

        $company = Company::find($activeCompanyId);            if (!$user->hasAccessToCompany($activeCompanyId)) {

        if (!$company || !$company->is_active) {                // User lost access to current company, switch to default

            return $this->handleInactiveCompany($request, $company);                $defaultCompany = $user->defaultCompany();

        }

                if ($defaultCompany) {

        // Set company context in various places for easy access                    Session::put('active_company_id', $defaultCompany->id);

        $this->setCompanyContext($company, $user);                } else {

                    Session::forget('active_company_id');

        // Store in request for this request cycle                    abort(403, 'No company access available. Please contact your administrator.');

        $request->merge(['active_company' => $company]);                }

            }

        return $next($request);

    }            // Share active company with all views/requests

            $activeCompany = $user->companies()->find($activeCompanyId);

    /**            if ($activeCompany) {

     * Resolve which company should be active for this request                $request->attributes->set('active_company', $activeCompany);

     */                view()->share('activeCompany', $activeCompany);

    private function resolveActiveCompanyId(Request $request, $user): ?int            }

    {        }

        // 1. Check if company is being explicitly switched via request

        if ($request->has('switch_company_id')) {        return $next($request);

            $companyId = (int) $request->input('switch_company_id');    }

            if ($user->hasAccessToCompany($companyId)) {}

                // Store in session for future requests
                Session::put('active_company_id', $companyId);
                return $companyId;
            }
        }

        // 2. Check session for previously selected company
        $sessionCompanyId = Session::get('active_company_id');
        if ($sessionCompanyId && $user->hasAccessToCompany($sessionCompanyId)) {
            return (int) $sessionCompanyId;
        }

        // 3. Use user's default company
        $defaultCompany = $user->defaultCompany();
        if ($defaultCompany) {
            Session::put('active_company_id', $defaultCompany->id);
            return $defaultCompany->id;
        }

        // 4. Use first available company
        $firstCompany = $user->companies()->where('is_active', true)->first();
        if ($firstCompany) {
            Session::put('active_company_id', $firstCompany->id);
            return $firstCompany->id;
        }

        return null;
    }

    /**
     * Set company context in various global locations
     */
    private function setCompanyContext(Company $company, $user): void
    {
        // Set in session
        Session::put('active_company_id', $company->id);
        Session::put('active_company', $company);

        // Set in config for global access
        config(['app.current_company_id' => $company->id]);
        config(['app.current_company' => $company]);

        // Set a global query scope helper
        app()->instance('current_company', $company);
        app()->instance('current_company_id', $company->id);
    }

    /**
     * Handle case where user has no company access
     */
    private function handleNoCompanyAccess(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'No company access',
                'message' => 'You do not have access to any company. Please contact your administrator.',
            ], Response::HTTP_FORBIDDEN);
        }

        // For web requests, redirect to a "no access" page or logout
        Auth::logout();
        return redirect()->route('login')->withErrors([
            'access' => 'You do not have access to any company. Please contact your administrator.'
        ]);
    }

    /**
     * Handle case where user tries to access unauthorized company
     */
    private function handleUnauthorizedCompanyAccess(Request $request, int $companyId): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized company access',
                'message' => "You do not have access to company ID {$companyId}.",
            ], Response::HTTP_FORBIDDEN);
        }

        // For web requests, redirect back with error
        return redirect()->back()->withErrors([
            'company' => 'You do not have access to the selected company.'
        ]);
    }

    /**
     * Handle case where company exists but is inactive
     */
    private function handleInactiveCompany(Request $request, ?Company $company): Response
    {
        $companyName = $company ? $company->name : 'Unknown Company';

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Inactive company',
                'message' => "The company '{$companyName}' is currently inactive.",
            ], Response::HTTP_FORBIDDEN);
        }

        // For web requests, try to switch to another company
        $user = Auth::user();
        $alternativeCompany = $user->companies()->where('is_active', true)->first();

        if ($alternativeCompany) {
            Session::put('active_company_id', $alternativeCompany->id);
            return redirect()->back()->with('warning',
                "Company '{$companyName}' is inactive. Switched to {$alternativeCompany->name}."
            );
        }

        // No alternative companies available
        return $this->handleNoCompanyAccess($request);
    }
}
