<?php

namespace App\Interfaces\Company;

use App\Models\SmsPanel;

interface SmsPanelRepositoryInterface
{
    public function findForCompany(int $companyId): ?SmsPanel;

    /** @param array<string, mixed> $data */
    public function create(array $data): SmsPanel;

    public function updateWalletBalance(SmsPanel $panel, int|float|string $balance): void;

    public function apiKey(SmsPanel $panel, int $userId): ?string;

    public function saveApiKey(SmsPanel $panel, int $userId, string $apiKey): void;
}
