<?php

namespace App\Providers;

use App\Models\Documents;
use App\Models\ProcessingJobs;
use App\Policies\DocumentsPolicy;
use App\Policies\ProcessingJobsPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(ProcessingJobs::class, ProcessingJobsPolicy::class);
        Gate::policy(Documents::class, DocumentsPolicy::class);
    }
}
