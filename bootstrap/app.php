<?php

use App\Exceptions\AccessDeniedHttpException;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ModelNotfoundException;
use App\Exceptions\NotFoundHttpException;
use App\Exceptions\TooManyRequestsHttpException;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnforceCompanySupportTokenScope;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

if (! function_exists('loadRoutesFromFolder')) {
    function loadRoutesFromFolder(string $folder, string $namePrefix = '', array $middlewareMap = []): void
    {
        foreach (glob($folder.'/*.php') as $file) {
            $module = basename($file, '.php');

            Route::middleware($middlewareMap[$module] ?? [])
                ->group($file);
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // 🔓 AUTH
            Route::prefix('api/auth')
                ->middleware(['api'])
                ->as('auth.')
                ->group(base_path('routes/api/auth.php'));

            // 🔓 PROFILE
            Route::prefix('api/user')
                ->middleware(['api', 'auth:sanctum', 'company.support.scope', 'check.permissions'])
                ->as('profile.')
                ->group(base_path('routes/api/profile.php'));

            // 👤 USER (auto load)
            Route::prefix('api/user')
                ->middleware(['api', 'auth:sanctum', 'company.support.scope'])
                ->as('user.')
                ->group(function () {
                    loadRoutesFromFolder(base_path('routes/api/user'));
                });

            // 🛠 ADMIN (auto load)
            Route::prefix('api/admin')
                ->middleware(['api', 'auth:sanctum', 'company.support.scope', 'check.permissions'])
                ->as('admin.')
                ->group(function () {
                    loadRoutesFromFolder(base_path('routes/api/admin'));
                });
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'check.permissions' => CheckPermission::class,
            'company.support.scope' => EnforceCompanySupportTokenScope::class,
        ]);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, $request) {
            throw new TooManyRequestsHttpException($e);
        });

        $exceptions->render(function (Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            throw new AccessDeniedHttpException($e);
        });

        $exceptions->render(function (Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            $previous = $e->getPrevious();

            if ($previous instanceof Illuminate\Database\Eloquent\ModelNotFoundException) {
                throw new ModelNotfoundException($e);
            } else {
                throw new NotFoundHttpException($e);
            }
        });

        $exceptions->render(function (Illuminate\Auth\AuthenticationException $e, $request) {
            throw new AuthenticationException($e);
        });
    })->create();
