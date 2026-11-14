<?php

namespace App\Repositories\Company;

use App\Enums\SmsRecipientParty;
use App\Interfaces\Company\SmsTemplateRepositoryInterface;
use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Collection;

class SmsTemplateRepository implements SmsTemplateRepositoryInterface
{
    public function forCompany(int $companyId): Collection
    {
        return SmsTemplate::query()->where('company_id', $companyId)->orderBy('id')->get();
    }

    public function upsert(int $companyId, SmsRecipientParty $party, string $body, int $updatedBy): SmsTemplate
    {
        return SmsTemplate::query()->updateOrCreate(
            ['company_id' => $companyId, 'party' => $party->value],
            ['body' => $body, 'last_updated_by' => $updatedBy],
        );
    }

    public function delete(int $companyId, SmsRecipientParty $party): bool
    {
        return SmsTemplate::query()
            ->where('company_id', $companyId)
            ->where('party', $party->value)
            ->delete() > 0;
    }
}
