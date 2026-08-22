<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Company\CompanyContextService;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function company(Request $request): Company
    {
        return app(CompanyContextService::class)->company($request);
    }

    protected function companyId(Request $request): int
    {
        return app(CompanyContextService::class)->companyId($request);
    }
}
