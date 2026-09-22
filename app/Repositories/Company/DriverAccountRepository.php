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
    public function __construct(protected DriverAccount $driverAccount) {}

    public function query(int $companyId): Builder
    {
        return $this->driverAccount->newQueryForCompany($companyId);
    }

    public function searchForDriver(
        int $companyId,
        int $driverId,
        array $filters,
    ): Collection|LengthAwarePaginator {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $filters['eq-driver_id'] = $driverId;
        $query = $this->query($companyId)->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function findForDriverOrFail(
        int $companyId,
        int $driverId,
        int $accountId,
    ): DriverAccount {
        return $this->query($companyId)
            ->where('driver_id', $driverId)
            ->findOrFail($accountId);
    }

    public function createForDriver(int $companyId, int $driverId, array $data): DriverAccount
    {
        return DB::transaction(function () use ($companyId, $driverId, $data): DriverAccount {
            $hasAccounts = $this->query($companyId)
                ->where('driver_id', $driverId)
                ->exists();
            $data['driver_id'] = $driverId;
            $data['is_default'] = ! $hasAccounts || (bool) ($data['is_default'] ?? false);

            if ($data['is_default']) {
                $this->clearDefaultAccounts($companyId, $driverId);
            }

            $account = $this->driverAccount
                ->newInstanceForCompany($companyId)
                ->newQuery()
                ->create([...$data, 'owner_company_id' => $companyId]);

            return $account->loadDefaultRelations();
        });
    }

    public function updateForDriver(
        int $companyId,
        int $driverId,
        int $accountId,
        array $data,
    ): DriverAccount {
        return DB::transaction(function () use ($companyId, $driverId, $accountId, $data): DriverAccount {
            $account = $this->findForDriverOrFail($companyId, $driverId, $accountId);
            unset($data['driver_id'], $data['owner_company_id']);

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

            $account->update($data);

            return $account->refresh()->loadDefaultRelations();
        });
    }

    public function deleteForDriver(int $companyId, int $driverId, int $accountId): void
    {
        DB::transaction(function () use ($companyId, $driverId, $accountId): void {
            $account = $this->findForDriverOrFail($companyId, $driverId, $accountId);
            $wasDefault = $account->is_default;
            $account->delete();

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
        $this->query($companyId)
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
        return $this->query($companyId)
            ->where('driver_id', $driverId)
            ->whereKeyNot($exceptAccountId)
            ->oldest('id')
            ->first();
    }
}
