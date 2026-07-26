<?php

namespace App\Providers;

use App\Models\GeneralSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
        JsonResource::withoutWrapping();

        Inertia::share([
            'flash' => function () {
                return [
                    'success' => session('success'),
                    'error' => session('error'),
                ];
            },
        ]);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Invoice views (PDF, thermal print, email) all need the company
        // name/logo/currency symbol — share it once here instead of every
        // call site (job, mailable, controller) fetching it separately.
        View::composer(['invoices.pdf', 'invoices.thermal', 'invoices.email'], function ($view) {
            $view->with('setting', GeneralSetting::current());
        });
    }
}
