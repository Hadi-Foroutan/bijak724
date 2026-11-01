<?php

return [
    // DASHBOARD_CACHE=false in the .env: the dashboard is read from the database every time, with no cache. After changing the
    // .env run `php artisan optimize:clear` (or deploy) so the cached config sees it.
    'cache_enabled' => (bool)env('DASHBOARD_CACHE', true),

    // How long a cached dashboard number lives (only when the cache is enabled).
    'cache_ttl_seconds' => (int)env('DASHBOARD_CACHE_TTL_SECONDS', 7200),
];
