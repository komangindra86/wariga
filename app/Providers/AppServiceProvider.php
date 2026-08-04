<?php

namespace App\Providers;

use App\Contracts\LunarCalendarProviderInterface;
use App\Services\Lunar\VerifiedDatasetLunarProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            LunarCalendarProviderInterface::class,
            VerifiedDatasetLunarProvider::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') && config('app.force_https')) {
            URL::forceScheme('https');
        }

        if (Schema::hasTable('engine_settings')) {
            foreach (DB::table('engine_settings')->pluck('value', 'key') as $key => $value) {
                config(["wariga.{$key}" => json_decode($value, true)]);
            }
        }

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) config('wariga.api_rate_limit', 60))->by(
                $request->user()?->getAuthIdentifier() ?: $request->ip(),
            );
        });

        RateLimiter::for('authentication', fn (Request $request): array => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        RateLimiter::for('password-reset', fn (Request $request): array => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
        ]);
    }
}
