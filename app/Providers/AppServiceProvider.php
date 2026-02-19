<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Listen for activity being logged
        Activity::creating(function (Activity $activity) {
            Log::info('Activity being logged:', [
                'description' => $activity->description,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'causer_type' => $activity->causer_type,
                'causer_id' => $activity->causer_id,
            ]);
        });

        // Listen for activity logged event
        Activity::created(function (Activity $activity) {
            Log::info('Activity logged successfully:', [
                'id' => $activity->id,
                'description' => $activity->description,
            ]);
        });
    }
}
