<?php

namespace App\Providers;

use App\Services\DataProviders\StopForumSpamProvider;
use App\Services\ThreatScoring\ThreatScoringService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThreatScoringService::class, function ($app) {
            return new ThreatScoringService([
                new StopForumSpamProvider,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
