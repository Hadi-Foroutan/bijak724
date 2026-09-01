<?php

namespace App\Providers;

use App\Interfaces\CityRepositoryInterface;
use App\Interfaces\Company\CargoRepositoryInterface;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
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
use App\Repositories\Company\CargoRepository;
use App\Repositories\Company\CompanyDataRepository;
use App\Repositories\Company\CompanyRepository;
use App\Repositories\Company\DriverRepository;
use App\Repositories\Company\FleetRepository;
use App\Repositories\Company\ProductOwnerRepository;
use App\Repositories\Company\ShipmentPartyAddressRepository;
use App\Repositories\Company\ShipmentPartyRepository;
use App\Repositories\Company\WaybillRepository;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Permission\RoleRepository;
use App\Repositories\User\UserRepository;
use App\Services\Company\CompanyDataOwnerResolver;
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
        $this->app->scoped(CompanyDataOwnerResolver::class);
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
        $this->app->bind(DriverRepositoryInterface::class, DriverRepository::class);
        $this->app->bind(FleetRepositoryInterface::class, FleetRepository::class);
        $this->app->bind(ShipmentPartyRepositoryInterface::class, ShipmentPartyRepository::class);
        $this->app->bind(ShipmentPartyAddressRepositoryInterface::class, ShipmentPartyAddressRepository::class);
        $this->app->bind(WaybillRepositoryInterface::class, WaybillRepository::class);
        $this->app->bind(CargoRepositoryInterface::class, CargoRepository::class);
        $this->app->bind(ProductOwnerRepositoryInterface::class, ProductOwnerRepository::class);

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
