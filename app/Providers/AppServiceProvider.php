<?php

namespace App\Providers;

use App\Interfaces\CityRepositoryInterface;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Interfaces\CompanyInterface;
use App\Interfaces\PermissionInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Models\Company;
use App\Models\User;
use App\Observers\CompanyObserver;
use App\Observers\UserObserver;
use App\Repositories\City\CityRepository;
use App\Repositories\Company\CompanyDataRepository;
use App\Repositories\Company\CompanyRepository;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Permission\RoleRepository;
use App\Repositories\User\UserRepository;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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
        Company::observe(CompanyObserver::class);
        User::observe(UserObserver::class);

        // User
        $this->app->bind(UserInterface::class, UserRepository::class);

        // Company
        $this->app->bind(CompanyDataRepositoryInterface::class, CompanyDataRepository::class);
        $this->app->bind(CompanyInterface::class, CompanyRepository::class);

        // City
        $this->app->bind(CityRepositoryInterface::class, CityRepository::class);

        // Role And Permission
        $this->app->bind(PermissionInterface::class, PermissionRepository::class);
        $this->app->bind(RoleInterface::class, RoleRepository::class);

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });
    }
}
