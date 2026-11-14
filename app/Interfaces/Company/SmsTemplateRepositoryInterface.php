<?php

namespace App\Interfaces\Company;

use App\Enums\SmsRecipientParty;
use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Collection;

interface SmsTemplateRepositoryInterface
{
    /** @return Collection<int, SmsTemplate> */
    public function forCompany(int $companyId): Collection;

    public function upsert(int $companyId, SmsRecipientParty $party, string $body, int $updatedBy): SmsTemplate;

    public function delete(int $companyId, SmsRecipientParty $party): bool;
}
