<?php

namespace App\Http\Middleware;

use App\Enums\GlobalSettingKey;
use App\Enums\SiteStatus;
use App\Helpers\ResponseHandler;
use App\Services\Setting\GlobalSettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes the company and insurance panels while an admin has the site in maintenance. It is a setting rather than
 * `artisan down`, which every deploy would lift, and which would also lock out the admin who has to turn it off.
 */
class EnsureSiteIsAvailable
{
    public function __construct(protected GlobalSettingService $globalSettingService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->globalSettingService->value(GlobalSettingKey::SiteStatus) === SiteStatus::Maintenance->value) {
            return ResponseHandler::error(
                __('public.site_under_maintenance'),
                __('public.site_under_maintenance'),
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return $next($request);
    }
}
