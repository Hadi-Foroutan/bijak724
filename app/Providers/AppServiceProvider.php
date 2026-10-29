<?php

namespace App\Providers;

use App\Interfaces\CityRepositoryInterface;
use App\Interfaces\Company\BijakNumberRepositoryInterface;
use App\Interfaces\Company\CanceledReferralRepositoryInterface;
use App\Interfaces\Company\CargoGroupRepositoryInterface;
use App\Interfaces\Company\CargoRepositoryInterface;
use App\Interfaces\Company\CompanySettingRepositoryInterface;
use App\Interfaces\Company\DashboardRepositoryInterface;
use App\Interfaces\Company\SharedAdditionalFieldRepositoryInterface;
use App\Interfaces\Company\DriverAccountRepositoryInterface;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Interfaces\Company\ReportExportRepositoryInterface;
use App\Interfaces\Company\ReportRepositoryInterface;
use App\Interfaces\Company\ReportWaybillRepositoryInterface;
use App\Interfaces\GlobalSettingRepositoryInterface;
use App\Interfaces\ReportFieldRepositoryInterface;
use App\Interfaces\Company\TechnicalManagerRepositoryInterface;
use App\Repositories\Company\ReportExportRepository;
use App\Repositories\Company\ReportRepository;
use App\Repositories\Company\ReportWaybillRepository;
use App\Repositories\GlobalSettingRepository;
use App\Repositories\ReportFieldRepository;
use App\Repositories\Company\TechnicalManagerRepository;
use App\Interfaces\Company\InsuranceTariffRepositoryInterface;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Interfaces\Company\ReferralNumberRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Interfaces\Company\TransportContractRepositoryInterface;
use App\Interfaces\Company\WaybillCargoRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Interfaces\CompanyInterface;
use App\Interfaces\GeneralOptionRepositoryInterface;
use App\Interfaces\InsuranceCompanyRepositoryInterface;
use App\Interfaces\NotificationRepositoryInterface;
use App\Interfaces\PermissionInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Interfaces\WaybillRepositoryInterface as SharedWaybillRepositoryInterface;
use App\Listeners\SyncPostmanOpenApi;
use App\Models\Company;
use App\Models\User;
use App\Observers\CompanyObserver;
use App\Observers\UserObserver;
use App\Repositories\City\CityRepository;
use App\Repositories\Company\BijakNumberRepository;
use App\Repositories\Company\CanceledReferralRepository;
use App\Repositories\Company\CargoGroupRepository;
use App\Repositories\Company\CargoRepository;
use App\Repositories\Company\CompanyRepository;
use App\Repositories\Company\CompanySettingRepository;
use App\Repositories\Company\DashboardRepository;
use App\Repositories\Company\SharedAdditionalFieldRepository;
use App\Repositories\Company\DriverAccountRepository;
use App\Repositories\Company\DriverRepository;
use App\Repositories\Company\FleetRepository;
use App\Repositories\Company\InsuranceRepository;
use App\Repositories\Company\InsuranceTariffRepository;
use App\Repositories\Company\ProductOwnerRepository;
use App\Repositories\Company\ReferralNumberRepository;
use App\Repositories\Company\ShipmentPartyAddressRepository;
use App\Repositories\Company\ShipmentPartyRepository;
use App\Repositories\Company\TransportContractRepository;
use App\Repositories\Company\WaybillCargoRepository;
use App\Repositories\Company\WaybillRepository;
use App\Repositories\General\GeneralOptionRepository;
use App\Repositories\InsuranceCompanyRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Permission\RoleRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\WaybillRepository as SharedWaybillRepository;
use App\Services\Company\CompanyDataOwnerResolver;
use App\Services\Company\Report\WaybillReportFieldCatalog;
use App\Services\Company\Settings\CompanySettingService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $bindings = [
        UserInterface::class => UserRepository::class,
        CompanyInterface::class => CompanyRepository::class,
        DriverRepositoryInterface::class => DriverRepository::class,
        DriverAccountRepositoryInterface::class => DriverAccountRepository::class,
        FleetRepositoryInterface::class => FleetRepository::class,
        ShipmentPartyRepositoryInterface::class => ShipmentPartyRepository::class,
        ShipmentPartyAddressRepositoryInterface::class => ShipmentPartyAddressRepository::class,
        WaybillRepositoryInterface::class => WaybillRepository::class,
        WaybillCargoRepositoryInterface::class => WaybillCargoRepository::class,
        CargoRepositoryInterface::class => CargoRepository::class,
        BijakNumberRepositoryInterface::class => BijakNumberRepository::class,
        CanceledReferralRepositoryInterface::class => CanceledReferralRepository::class,
        CompanySettingRepositoryInterface::class => CompanySettingRepository::class,
        SharedAdditionalFieldRepositoryInterface::class => SharedAdditionalFieldRepository::class,
        DashboardRepositoryInterface::class => DashboardRepository::class,
        CargoGroupRepositoryInterface::class => CargoGroupRepository::class,
        InsuranceRepositoryInterface::class => InsuranceRepository::class,
        TechnicalManagerRepositoryInterface::class => TechnicalManagerRepository::class,
        InsuranceTariffRepositoryInterface::class => InsuranceTariffRepository::class,
        ProductOwnerRepositoryInterface::class => ProductOwnerRepository::class,
        ReferralNumberRepositoryInterface::class => ReferralNumberRepository::class,
        TransportContractRepositoryInterface::class => TransportContractRepository::class,
        CityRepositoryInterface::class => CityRepository::class,
        GeneralOptionRepositoryInterface::class => GeneralOptionRepository::class,
        InsuranceCompanyRepositoryInterface::class => InsuranceCompanyRepository::class,
        NotificationRepositoryInterface::class => NotificationRepository::class,
        PermissionInterface::class => PermissionRepository::class,
        RoleInterface::class => RoleRepository::class,
        SharedWaybillRepositoryInterface::class => SharedWaybillRepository::class,
        ReportRepositoryInterface::class => ReportRepository::class,
        ReportExportRepositoryInterface::class => ReportExportRepository::class,
        ReportWaybillRepositoryInterface::class => ReportWaybillRepository::class,
        ReportFieldRepositoryInterface::class => ReportFieldRepository::class,
        GlobalSettingRepositoryInterface::class => GlobalSettingRepository::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CompanyDataOwnerResolver::class);
        $this->app->scoped(CompanySettingService::class);
        // One shared instance so every part of a report load/export reads the same admin-managed fields.
        $this->app->singleton(WaybillReportFieldCatalog::class);
        // One instance each: the subscriber remembers which command/job is running, the logger whether it is writing.
        $this->app->singleton(\App\Support\Logging\RunLogger::class);
        $this->app->singleton(\App\Listeners\RunLogSubscriber::class);

        // `php artisan reverb:start` prints who connects, subscribes and leaves (see LoggingPusherServer).
        $this->app->bind(\Laravel\Reverb\Protocols\Pusher\Server::class, \App\Support\Reverb\LoggingPusherServer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(CommandFinished::class, SyncPostmanOpenApi::class);

        // CRON_LOGS=true: every command and queue job logs to its own file (storage/logs/commands, storage/logs/jobs).
        if (config('cron_logs.enabled')) {
            Event::subscribe(\App\Listeners\RunLogSubscriber::class);
        }

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
