<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ActivityLogService;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    /**
     * Get recent activities for the current user's company
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $defaultCompany = $user->defaultCompany();

        if (!$defaultCompany) {
            return response()->json(['error' => 'No default company found'], 404);
        }

        $limit = $request->get('limit', 50);
        $activities = ActivityLogService::getCompanyActivities($defaultCompany->id, $limit);

        return response()->json([
            'activities' => $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'created_at' => $activity->created_at->toISOString(),
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'subject' => $activity->subject ? [
                        'type' => class_basename($activity->subject_type),
                        'id' => $activity->subject_id,
                    ] : null,
                    'properties' => $activity->properties,
                ];
            }),
        ]);
    }

    /**
     * Get activities for a specific user (admin only)
     */
    public function userActivities(Request $request, int $userId)
    {
        // Check if current user can view user activities
        $currentUser = Auth::user();
        if (!$currentUser->hasRole('Super Admin') && !$currentUser->hasRole('Company Admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $limit = $request->get('limit', 50);
        $activities = ActivityLogService::getUserActivities($userId, $limit);

        return response()->json([
            'activities' => $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'created_at' => $activity->created_at->toISOString(),
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'subject' => $activity->subject ? [
                        'type' => class_basename($activity->subject_type),
                        'id' => $activity->subject_id,
                    ] : null,
                    'properties' => $activity->properties,
                ];
            }),
        ]);
    }

    /**
     * Get recent system activities (Super Admin only)
     */
    public function systemActivities(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser->hasRole('Super Admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $limit = $request->get('limit', 100);
        $activities = ActivityLogService::getRecentActivities($limit);

        return response()->json([
            'activities' => $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'created_at' => $activity->created_at->toISOString(),
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'subject' => $activity->subject ? [
                        'type' => class_basename($activity->subject_type),
                        'id' => $activity->subject_id,
                    ] : null,
                    'properties' => $activity->properties,
                ];
            }),
        ]);
    }
}
