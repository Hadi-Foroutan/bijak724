<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHandler;
use App\Models\User;
use App\Services\Company\CompanyContextService;
use App\Services\Company\CompanySupportTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnforceCompanySupportTokenScope
{
    /** @var array<int, string> */
    private const COMPANY_PANEL_ROUTES = [
        'user.*',
        'profile.*',
    ];

    public function __construct(
        protected CompanySupportTokenService $supportTokenService,
        protected CompanyContextService $companyContextService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();
        $isCompanyPanelRoute = $routeName
            && Str::is(self::COMPANY_PANEL_ROUTES, $routeName);

        if ($isCompanyPanelRoute) {
            $this->companyContextService->company($request);

            return $next($request);
        }

        if ($user instanceof User && $this->supportTokenService->isCompanyToken($user)) {
            return $this->accessDenied();
        }

        return $next($request);
    }

    private function accessDenied(): Response
    {
        return ResponseHandler::error(
            __('public.access_denied', ['attribute' => 'شرکت']),
            status: Response::HTTP_FORBIDDEN,
        );
    }
}
