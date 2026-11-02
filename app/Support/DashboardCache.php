<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * The cache of the dashboards (the company's and the admin's), switched by DASHBOARD_CACHE in the .env:
 * off = every call reads the database and nothing is read from or written to the cache. The lifetime is
 * DASHBOARD_CACHE_TTL_SECONDS (config/dashboard.php).
 */
class DashboardCache
{
    private const VERSION = 'v1';

    /**
     * @param array<int, string|int|null> $parts what the number is about, e.g. `['company-3', 'summary', '2026-11-01']` (empty ones are skipped)
     * @param Closure(): array $callback reads the number from the database
     * @return array<string, mixed>
     */
    public function remember(array $parts, Closure $callback): array
    {
        if (!config('dashboard.cache_enabled', true)) {
            return $callback();
        }

        $key = implode(':', array_filter(['dashboard', self::VERSION, ...$parts]));

        return Cache::remember($key, max(1, (int)config('dashboard.cache_ttl_seconds', 7200)), $callback);
    }
}
