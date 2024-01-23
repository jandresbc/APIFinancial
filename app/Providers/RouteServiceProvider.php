<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {


            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::prefix('api/Siigo')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api_siigo.php'));
            Route::prefix('api/auth')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/auth.php'));

            Route::prefix('api/request')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/request.php'));

            Route::prefix('api/messenger')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/messenger.php'));

            Route::prefix('api/quotas')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/quotas.php'));

            Route::prefix('api/payments')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/payments.php'));

            Route::prefix('api/truora')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/truora.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            Route::prefix('api/whats-app')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/whatsApp.php'));

            Route::prefix('api/product')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/product.php'));

            Route::prefix('api/en-banca')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/enBanca.php'));

            Route::prefix('api/moto-safe')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/motoSafe.php'));

            Route::prefix('api/nuovo-api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/nuovo.php'));

            Route::prefix('api/trustonic')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/trustonic.php'));

            Route::prefix('api/devices')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/manageDevices.php'));

            Route::prefix('api/risks')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/risks.php'));

            Route::prefix('api/self')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/self.php'));
        });


    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', static function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
