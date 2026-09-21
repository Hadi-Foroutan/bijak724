<?php

namespace App\Http\Middleware;

use App\Enums\StatusEnum;
use App\Models\User;
use App\Services\Company\CompanySupportTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    protected array $excludedRoutes = [
        '*.login',
        '*.logout',
        '*.checkToken',
    ];

    public function __construct(
        protected CompanySupportTokenService $supportTokenService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // ۱. دریافت کاربر جاری (می‌تواند User یا Admin باشد)
        $currentUser = $request->user();

        if (! $currentUser) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $routeName = Route::currentRouteName();

        if (! $routeName || $this->isExcluded($routeName)) {
            return $next($request);
        }

        if ($currentUser instanceof User
            && $this->supportTokenService->isSupportToken($currentUser)) {
            $token = $currentUser->currentAccessToken();

            return $token instanceof PersonalAccessToken
                && $this->supportTokenService->canAccessSupportPermission($token, $routeName)
                ? $next($request)
                : response()->json([
                    'message' => __('public.access_denied', ['attribute' => 'صفحه']),
                ], Response::HTTP_FORBIDDEN);
        }

        // چک کردن سوپر ادمین بودن (اگر برای یوزر هم تعریف شده باشد)
        if (method_exists($currentUser, 'isSuperAdmin') && $currentUser->isSuperAdmin()) {
            return $next($request);
        }

        // دسترسی نهایی سیستم از پرمیشن‌های مستقیم کاربر خوانده می‌شود.
        $hasPermission = $currentUser->permissions()
            ->where('name', $routeName)
            ->where('status', StatusEnum::ACTIVE->value)
            ->exists();

        // ۳. نتیجه نهایی
        if ($hasPermission) {
            return $next($request);
        }

        return response()->json([
            'message' => __('public.access_denied', ['attribute' => 'صفحه']),
        ], Response::HTTP_FORBIDDEN);
    }

    private function isExcluded(string $routeName): bool
    {
        foreach ($this->excludedRoutes as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;

    }
}
