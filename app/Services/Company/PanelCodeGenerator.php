<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\User;
use RuntimeException;

/** The one place a company's panel code is made: a random number of {@see self::LENGTH} digits. */
class PanelCodeGenerator
{
    public const LENGTH = 6;

    private const MAX_ATTEMPTS = 50;

    /**
     * A panel code no company (deleted ones included) has, and that does not begin any existing username: usernames
     * are `<panel_code><sequence>`, so such a code could make a company's usernames clash with someone else's.
     */
    public function generate(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $code = (string)random_int(10 ** (self::LENGTH - 1), 10 ** self::LENGTH - 1);

            if (!$this->isTaken($code)) {
                return $code;
            }
        }

        throw new RuntimeException('Could not generate a free panel code.');
    }

    public function isValid(string $code): bool
    {
        return preg_match('/^[1-9]\d{' . (self::LENGTH - 1) . '}$/', $code) === 1;
    }

    private function isTaken(string $code): bool
    {
        return Company::withTrashed()->where('panel_code', $code)->exists()
            || User::withTrashed()->where('username', 'like', $code . '%')->exists();
    }
}
