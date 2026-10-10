<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\DriverAccountRepositoryInterface;
use App\Models\Company\DriverAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DriverAccountRepository implements DriverAccountRepositoryInterface
{
    public function query(int $companyId): Builder
    {
        return DriverAccount::queryForCompany($companyId);
    }

    public function searchForDriver(
        int $companyId,
        int $driverId,
        array $filters,
    ): Collection|LengthAwarePaginator {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;

        return DriverAccount::searchRecordsForCompany(
            $companyId,
            $filters,
            fn (Builder $query): Builder => $query->where('driver_id', $driverId),
        );
    }

    public function findForDriverOrFail(
        int $companyId,
        int $driverId,
        int $accountId,
    ): DriverAccount {
        return DriverAccount::findForCompanyOrFail(
            $companyId,
            $accountId,
            fn (Builder $query): Builder => $query->where('driver_id', $driverId),
        );
    }

    public function createForDriver(int $companyId, int $driverId, array $data): DriverAccount
    {
        return DB::transaction(function () use ($companyId, $driverId, $data): DriverAccount {
            $hasAccounts = DriverAccount::queryForCompany($companyId)
                ->where('driver_id', $driverId)
                ->exists();
            $data['driver_id'] = $driverId;
            $data['is_default'] = ! $hasAccounts || (bool) ($data['is_default'] ?? false);

            if ($data['is_default']) {
                $this->clearDefaultAccounts($companyId, $driverId);
            }

            return DriverAccount::createForCompany($companyId, $data);
        });
    }

    public function updateForDriver(
        int $companyId,
        int $driverId,
        int $accountId,
        array $data,
    ): DriverAccount {
        return DB::transaction(function () use ($companyId, $driverId, $accountId, $data): DriverAccount {
            unset($data['driver_id'], $data['owner_company_id']);

            $account = $this->findForDriverOrFail($companyId, $driverId, $accountId);

            if (array_key_exists('is_default', $data) && (bool) $data['is_default']) {
                $this->clearDefaultAccounts($companyId, $driverId, $accountId);
            } elseif ($account->is_default && array_key_exists('is_default', $data)) {
                $replacement = $this->firstAccountExcept($companyId, $driverId, $accountId);

                if ($replacement === null) {
                    $data['is_default'] = true;
                } else {
                    $replacement->update(['is_default' => true]);
                }
            }

            return DriverAccount::updateForCompany(
                $companyId,
                $accountId,
                $data,
                fn (Builder $query): Builder => $query->where('driver_id', $driverId),
            );
        });
    }

    public function deleteForDriver(int $companyId, int $driverId, int $accountId): void
    {
        DB::transaction(function () use ($companyId, $driverId, $accountId): void {
            $account = $this->findForDriverOrFail($companyId, $driverId, $accountId);
            $wasDefault = $account->is_default;
            DriverAccount::deleteForCompany(
                $companyId,
                $accountId,
                fn (Builder $query): Builder => $query->where('driver_id', $driverId),
            );

            if ($wasDefault) {
                $this->firstAccountExcept($companyId, $driverId, $accountId)
                    ?->update(['is_default' => true]);
            }
        });
    }

    private function clearDefaultAccounts(
        int $companyId,
        int $driverId,
        ?int $exceptAccountId = null,
    ): void {
        DriverAccount::queryForCompany($companyId)
            ->where('driver_id', $driverId)
            ->where('is_default', true)
            ->when(
                $exceptAccountId !== null,
                fn ($query) => $query->whereKeyNot($exceptAccountId),
            )
            ->update(['is_default' => false]);
    }

    private function firstAccountExcept(
        int $companyId,
        int $driverId,
        int $exceptAccountId,
    ): ?DriverAccount {
        return DriverAccount::queryForCompany($companyId)
            ->where('driver_id', $driverId)
            ->whereKeyNot($exceptAccountId)
            ->oldest('id')
            ->first();
    }
}
