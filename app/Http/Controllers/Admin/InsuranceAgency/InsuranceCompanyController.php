<?php

namespace App\Http\Controllers\Admin\InsuranceAgency;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Services\General\GeneralOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsuranceCompanyController extends Controller
{
    public function __construct(protected GeneralOptionService $generalOptionService)
    {
    }

    /** The active insurers, for the admin's agency and insurance-user forms (the company panel has its own list under /user/general) */
    public function index(Request $request): JsonResponse
    {
        $result = $this->generalOptionService->insuranceCompanies($request->all());

        return ResponseHandler::success($result->data);
    }
}
