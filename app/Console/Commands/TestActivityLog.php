<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class TestActivityLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:activity-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test activity logging functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Activity Log Functionality...');

        // Test Company logging
        $company = Company::first();
        if ($company) {
            $this->info("Found company: {$company->name}");

            $originalDescription = $company->description;
            $company->update(['description' => 'Updated description for testing activity log at ' . now()]);

            $this->info('Company updated!');
        } else {
            $this->error('No company found!');
            return;
        }

        // Test User logging
        $user = User::first();
        if ($user) {
            $this->info("Found user: {$user->name}");

            $originalName = $user->name;
            $user->update(['name' => $user->name . ' (Activity Log Test)']);

            $this->info('User updated!');

            // Revert the change
            $user->update(['name' => $originalName]);
            $this->info('User name reverted!');
        }

        // Count activity logs
        $count = Activity::count();
        $this->info("Total activity logs: {$count}");

        // Show latest activities
        $latest = Activity::latest()->take(5)->get();
        if ($latest->count() > 0) {
            $this->info('Recent activities:');
            foreach ($latest as $activity) {
                $this->line("- {$activity->description} at {$activity->created_at}");
                $this->line("  Subject: {$activity->subject_type} (ID: {$activity->subject_id})");
                $causer = $activity->causer ? $activity->causer->name : 'System';
                $this->line("  Causer: {$causer}");
                $this->line('');
            }
        }

        $this->info('Activity logging test completed!');
    }
}
