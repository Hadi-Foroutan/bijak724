<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\SmsPanelRepositoryInterface;
use App\Models\SmsPanel;
use App\Models\SmsPanelUser;

class SmsPanelRepository implements SmsPanelRepositoryInterface
{
    public function findForCompany(int $companyId): ?SmsPanel
    {
        return SmsPanel::query()->where('company_id', $companyId)->first();
    }

    public function create(array $data): SmsPanel
    {
        return SmsPanel::query()->create($data);
    }

    public function updateWalletBalance(SmsPanel $panel, int|float|string $balance): void
    {
        $panel->update(['wallet_balance' => $balance, 'wallet_updated_at' => now()]);
    }

    public function apiKey(SmsPanel $panel, int $userId): ?string
    {
        return SmsPanelUser::query()
            ->where('sms_panel_id', $panel->getKey())
            ->where('user_id', $userId)
            ->first()
            ?->api_key;
    }

    public function saveApiKey(SmsPanel $panel, int $userId, string $apiKey): void
    {
        SmsPanelUser::query()->updateOrCreate(
            ['sms_panel_id' => $panel->getKey(), 'user_id' => $userId],
            ['api_key' => $apiKey],
        );
    }
}
