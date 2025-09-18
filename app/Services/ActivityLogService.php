<?php

namespace App\Services;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Log ERP-specific activities
     */
    public static function log(string $event, string $description, $subject = null, array $properties = [])
    {
        $activity = activity('erp')
            ->event($event)
            ->withProperties($properties);

        if ($subject) {
            $activity->performedOn($subject);
        }

        if (Auth::check()) {
            $activity->causedBy(Auth::user());
        }

        return $activity->log($description);
    }

    /**
     * Log business operations
     */
    public static function logBusinessOperation(string $operation, string $description, $subject = null, array $details = [])
    {
        return self::log('business_operation', $description, $subject, [
            'operation' => $operation,
            'details' => $details,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log user company assignments
     */
    public static function logUserCompanyAssignment($user, $company, $role, string $action = 'assigned')
    {
        return self::log('user_company_assignment',
            "User {$user->name} was {$action} to company {$company->name} with role {$role}",
            $user,
            [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'role' => $role,
                'action' => $action,
            ]
        );
    }

    /**
     * Log role changes
     */
    public static function logRoleChange($user, string $oldRole, string $newRole, $company = null)
    {
        $description = "User {$user->name} role changed from {$oldRole} to {$newRole}";
        if ($company) {
            $description .= " in company {$company->name}";
        }

        return self::log('role_change', $description, $user, [
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'company_id' => $company?->id,
            'company_name' => $company?->name,
        ]);
    }

    /**
     * Log system configuration changes
     */
    public static function logSystemConfig(string $setting, $oldValue, $newValue, string $section = 'general')
    {
        return self::log('system_config',
            "System setting '{$setting}' changed from '{$oldValue}' to '{$newValue}'",
            null,
            [
                'section' => $section,
                'setting' => $setting,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ]
        );
    }

    /**
     * Get activities for a specific company
     */
    public static function getCompanyActivities($companyId, int $limit = 50)
    {
        return Activity::where('log_name', 'erp')
            ->where(function ($query) use ($companyId) {
                $query->where('subject_type', 'App\Models\Company')
                      ->where('subject_id', $companyId)
                      ->orWhereJsonContains('properties->company_id', $companyId);
            })
            ->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities for a specific user
     */
    public static function getUserActivities($userId, int $limit = 50)
    {
        return Activity::where('log_name', 'erp')
            ->where(function ($query) use ($userId) {
                $query->where('causer_id', $userId)
                      ->orWhere(function ($q) use ($userId) {
                          $q->where('subject_type', 'App\Models\User')
                            ->where('subject_id', $userId);
                      });
            })
            ->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent system activities
     */
    public static function getRecentActivities(int $limit = 100)
    {
        return Activity::where('log_name', 'erp')
            ->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
