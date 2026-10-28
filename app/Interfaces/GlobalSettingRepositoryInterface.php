<?php

namespace App\Interfaces;

/** Settings shared by the whole system (table `settings`), managed by admins. */
interface GlobalSettingRepositoryInterface
{
    /** The stored text of a setting, or null when it was never saved. */
    public function value(string $key): ?string;

    public function upsert(string $key, ?string $value, int $updatedBy): void;
}
