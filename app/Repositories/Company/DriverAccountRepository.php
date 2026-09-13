<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\DriverAccountRepositoryInterface;
use App\Models\Company\DriverAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DriverAccountRepository extends CompanyModelRepository implements DriverAccountRepositoryInterface
{
    protected string $tableKey = 'driver_accounts';

    public function searchForDriver(
        int $companyId,
        int $driverId,
        array $filters,
    ): Collection|LengthAwarePaginator {
        $filters['eq-driver_id'] = $driverId;

        return $this->search($companyId, $filters);
    }

    public function driverExists(int $companyId, int $driverId): bool
    {
        return $this->tableRegistry->query($companyId, 'drivers')
            ->whereKey($driverId)
            ->exists();
    }

    public function findForDriverOrFail(
        int $companyId,
        int $driverId,
        int $accountId,
    ): DriverAccount {
        /** @var DriverAccount $account */
        $account = $this->query($companyId)
            ->where('driver_id', $driverId)
            ->findOrFail($accountId);

        /** @var DriverAccount */
        return $this->loadRelations($account);
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

            /** @var DriverAccount */
            return $this->create($companyId, $data);
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
            unset($data['driver_id']);

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

            /** @var DriverAccount */
            return $this->loadRelations($account->refresh());
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
        /** @var DriverAccount|null */
        return $this->query($companyId)
            ->where('driver_id', $driverId)
            ->whereKeyNot($exceptAccountId)
            ->oldest('id')
            ->first();
    }
}
