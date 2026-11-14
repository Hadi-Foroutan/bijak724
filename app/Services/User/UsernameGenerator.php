<?php

namespace App\Services\User;

use App\Models\Company;
use App\Models\User;

class UsernameGenerator
{
    /**
     * The next username of a company's user: its panel code followed by a sequence starting at 1000
     * (5656 -> 56561000, 56561001, ...). The sequence is kept on the company and never goes back, so the username of
     * a deleted user is not issued again. Call it inside the transaction that creates the user.
     */
    public function nextFor(int $companyId): string
    {
        $company = Company::query()->lockForUpdate()->findOrFail($companyId);
        $sequence = max((int)$company->last_user_sequence, 999);

        do {
            $sequence++;
            $username = $company->panel_code . $sequence;
            // Deleted users still hold their username in the unique index, so they count as taken too.
        } while (User::withTrashed()->where('username', $username)->exists());

        $company->forceFill(['last_user_sequence' => $sequence])->save();

        return $username;
    }
}
