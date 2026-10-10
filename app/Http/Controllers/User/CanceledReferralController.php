<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Resources\CanceledReferralResource;
use App\Services\Company\CanceledReferral\CanceledReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CanceledReferralController extends Controller
{
    public function __construct(protected CanceledReferralService $canceledReferralService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->canceledReferralService->index($this->companyId($request), $request->all());

        return ResponseHandler::success(
            $this->resourceCollection($result->data, CanceledReferralResource::class, $request),
        );
    }
}
