<?php

namespace App\Providers;

use App\Interfaces\CityRepositoryInterface;
use App\Interfaces\Company\CargoGroupRepositoryInterface;
use App\Interfaces\Company\CargoRepositoryInterface;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Interfaces\Company\InsuranceTariffRepositoryInterface;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Interfaces\Company\TransportContractRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Interfaces\CompanyInterface;
use App\Interfaces\GeneralOptionRepositoryInterface;
use App\Interfaces\PermissionInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Models\Company;
use App\Models\User;
use App\Observers\CompanyObserver;
use App\Observers\UserObserver;
use App\Repositories\City\CityRepository;
use App\Repositories\Company\CargoGroupRepository;
use App\Repositories\Company\CargoRepository;
use App\Repositories\Company\CompanyDataRepository;
use App\Repositories\Company\CompanyRepository;
use App\Repositories\Company\DriverRepository;
use App\Repositories\Company\FleetRepository;
use App\Repositories\Company\InsuranceRepository;
use App\Repositories\Company\InsuranceTariffRepository;
use App\Repositories\Company\ProductOwnerRepository;
use App\Repositories\Company\ShipmentPartyAddressRepository;
use App\Repositories\Company\ShipmentPartyRepository;
use App\Repositories\Company\TransportContractRepository;
use App\Repositories\Company\WaybillRepository;
use App\Repositories\General\GeneralOptionRepository;
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
    /** @var array<class-string, class-string> */
    public $bindings = [
        UserInterface::class => UserRepository::class,
        CompanyDataRepositoryInterface::class => CompanyDataRepository::class,
        CompanyInterface::class => CompanyRepository::class,
        DriverRepositoryInterface::class => DriverRepository::class,
        FleetRepositoryInterface::class => FleetRepository::class,
        ShipmentPartyRepositoryInterface::class => ShipmentPartyRepository::class,
        ShipmentPartyAddressRepositoryInterface::class => ShipmentPartyAddressRepository::class,
        WaybillRepositoryInterface::class => WaybillRepository::class,
        CargoRepositoryInterface::class => CargoRepository::class,
        CargoGroupRepositoryInterface::class => CargoGroupRepository::class,
        InsuranceRepositoryInterface::class => InsuranceRepository::class,
        InsuranceTariffRepositoryInterface::class => InsuranceTariffRepository::class,
        ProductOwnerRepositoryInterface::class => ProductOwnerRepository::class,
        TransportContractRepositoryInterface::class => TransportContractRepository::class,
        CityRepositoryInterface::class => CityRepository::class,
        GeneralOptionRepositoryInterface::class => GeneralOptionRepository::class,
        PermissionInterface::class => PermissionRepository::class,
        RoleInterface::class => RoleRepository::class,
    ];

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

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });
    }
}
