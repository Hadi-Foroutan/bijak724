<?php

namespace App\Services\Company\Waybill;

use App\Enums\StatusEnum;
use App\Models\Company\Waybill;
use App\Models\User;
use App\Services\Company\CompanySupportTokenService;
use Illuminate\Database\Eloquent\Builder;

/**
 * تعیین می‌کند هر کاربر کدام بارنامه‌ها را می‌تواند ببیند.
 *
 * همیشه بارنامه‌های خود کاربر دیده می‌شود. هر کدام از سه دسترسی زیر، بارنامه‌های بیشتری اضافه می‌کند:
 *  - view-all-users:   بارنامه‌های همهٔ کاربران شرکت
 *  - view-peer-users:  بارنامه‌های کاربران هم‌رده (همان والد خودش را دارند)
 *  - view-parent-user: بارنامه‌های والد (سرپرست) مستقیم کاربر
 */
class WaybillVisibilityService
{
    public const VIEW_ALL = 'user.waybills.view-all-users';

    public const VIEW_PEERS = 'user.waybills.view-peer-users';

    public const VIEW_PARENT = 'user.waybills.view-parent-user';

    public function __construct(
        protected CompanySupportTokenService $companySupportTokenService,
    )
    {
    }

    /**
     * @param Builder<Waybill> $query
     * @return Builder<Waybill>
     */
    public function scope(Builder $query, User $user): Builder
    {
        if ($this->canViewAll($user)) {
            return $query;
        }

        $creatorIds = $this->visibleCreatorIds($user);

        return $query->whereIn('created_by', $creatorIds);
    }

    /**
     * The creators whose waybills the user sees, or null for everyone's. Unlike scope() the answer is plain data, so it
     * can be taken in the request (where the support token is known) and used later by a queue worker.
     *
     * @return list<int>|null
     */
    public function creatorIds(User $user): ?array
    {
        return $this->canViewAll($user) ? null : $this->visibleCreatorIds($user);
    }

    public function canView(Waybill $waybill, User $user): bool
    {
        if ($this->canViewAll($user)) {
            return true;
        }

        return $waybill->created_by !== null
            && in_array((int)$waybill->created_by, $this->visibleCreatorIds($user), true);
    }

    public function canViewAll(User $user): bool
    {
        return $this->companySupportTokenService->isSupportToken($user)
            || $this->hasPermission($user, self::VIEW_ALL);
    }

    /**
     * شناسهٔ کاربرانی که بارنامه‌هایشان برای این کاربر قابل مشاهده است (خودش همیشه هست).
     *
     * @return list<int>
     */
    public function visibleCreatorIds(User $user): array
    {
        $ids = [(int)$user->getKey()];

        if ($this->hasPermission($user, self::VIEW_PEERS)) {
            $ids = [...$ids, ...User::query()
                ->where('company_id', $user->company_id)
                ->when(
                    $user->parent_id === null,
                    fn(Builder $query): Builder => $query->whereNull('parent_id'),
                    fn(Builder $query): Builder => $query->where('parent_id', $user->parent_id),
                )
                ->pluck('id')
                ->map(fn($id): int => (int)$id)
                ->all()];
        }

        if ($user->parent_id !== null && $this->hasPermission($user, self::VIEW_PARENT)) {
            $ids[] = (int)$user->parent_id;
        }

        return array_values(array_unique($ids));
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->permissions()
            ->where('name', $permission)
            ->where('status', StatusEnum::ACTIVE->value)
            ->exists();
    }
}
