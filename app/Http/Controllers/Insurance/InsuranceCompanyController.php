<?php

namespace App\Http\Controllers\Insurance;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Resources\InsurancePanelCompanyResource;
use App\Services\InsurancePanel\InsurancePanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsuranceCompanyController extends Controller
{
    public function __construct(protected InsurancePanelService $panelService)
    {
    }

    /** The companies that recorded insurance contracts the user may see */
    public function index(Request $request): JsonResponse
    {
        $result = $this->panelService->companies($request->user(), $request->all());

        return ResponseHandler::success($this->resourceCollection($result->data, InsurancePanelCompanyResource::class, $request));
    }
}
