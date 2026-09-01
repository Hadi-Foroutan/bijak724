<?php

namespace App\Services\User;

use App\Enums\RoleEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Models\User;
use App\Services\Uploads\UserImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserService
{
    /**
     * @var array<string, array{collection: string, remove: string}>
     */
    private const IMAGE_FIELDS = [
        'profile_image' => [
            'collection' => 'profile-images',
            'remove' => 'remove_profile_image',
        ],
        'signature_image' => [
            'collection' => 'signatures',
            'remove' => 'remove_signature_image',
        ],
    ];

    public function __construct(
        protected UserInterface $userRepository,
        protected RoleInterface $roleRepository,
        protected UserImageUploader $imageUploader,
    ) {}

    public function all(array $params): ServiceResult
    {
        return ServiceResult::success($this->userRepository->all($params));
    }

    public function tree(array $params): ServiceResult
    {
        return ServiceResult::success($this->userRepository->tree($params));
    }

    public function store(array $data): ServiceResult
    {
        $images = $this->pullImages($data);
        $role = $this->roleRepository->findById($data['role_id']);

        if (! $role) {
            return ServiceResult::error(
                __('public.not_found', ['attribute' => 'نقش'])
            );
        }

        $isAdmin = in_array($role->name, [
            RoleEnum::ADMIN->value,
            RoleEnum::SUPERADMIN->value,
        ], true);

        if (! $isAdmin && empty($data['company_id'])) {
            return ServiceResult::error(
                __('validation.required', [
                    'attribute' => 'شرکت',
                ]),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->validateParent($data);

        unset($data['role_id'], $data['remove_profile_image'], $data['remove_signature_image']);
        $uploadedPaths = [];

        try {
            $user = DB::transaction(function () use ($data, $images, $role, &$uploadedPaths): ?User {
                $user = $this->userRepository->store($data);

                if (! $user) {
                    return null;
                }

                $this->roleRepository->assignRoleToUser($role, $user);
                $imagePaths = $this->uploadImages($images, $user->id, $uploadedPaths);

                if ($imagePaths !== []) {
                    $user = $this->userRepository->update($imagePaths, $user);
                }

                return $user?->load(['roles', 'parent']);
            });
        } catch (Throwable $throwable) {
            $this->deleteImages($uploadedPaths);

            throw $throwable;
        }

        return $user
            ? ServiceResult::success($user)
            : ServiceResult::error(__('public.not_found', ['attribute' => 'کاربر']));
    }

    public function update(array $data, ?User $user = null): ServiceResult
    {
        $user ??= auth()->user();
        $images = $this->pullImages($data);
        $roleId = $data['role_id'] ?? null;
        $role = $roleId === null
            ? $user->roles()->first()
            : $this->roleRepository->findById((int) $roleId);

        if (! $role) {
            return ServiceResult::error(
                __('public.not_found', ['attribute' => 'نقش']),
                Response::HTTP_NOT_FOUND
            );
        }

        $isAdmin = in_array(
            $role->name,
            [
                RoleEnum::ADMIN->value,
                RoleEnum::SUPERADMIN->value,
            ],
            true
        );

        if (! $isAdmin && empty($data['company_id'] ?? $user->company_id)) {
            return ServiceResult::error(
                __('validation.required', ['attribute' => 'شرکت']),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($isAdmin) {
            $data['company_id'] = $data['company_id'] ?? null;
        }

        if (array_key_exists('company_id', $data)
            && (int) $data['company_id'] !== (int) $user->company_id
            && ! array_key_exists('parent_id', $data)) {
            $data['parent_id'] = null;
        }

        $this->validateParent($data, $user);

        $currentPaths = collect(array_keys(self::IMAGE_FIELDS))
            ->mapWithKeys(fn (string $field): array => [$field => $user->{$field}])
            ->all();
        $uploadedPaths = [];

        foreach (self::IMAGE_FIELDS as $field => $definition) {
            $shouldRemove = (bool) ($data[$definition['remove']] ?? false);
            unset($data[$definition['remove']]);

            if ($shouldRemove && ! isset($images[$field])) {
                $data[$field] = null;
            }
        }
        unset($data['role_id']);

        try {
            $user = DB::transaction(function () use ($data, $images, $role, $roleId, $user, &$uploadedPaths): ?User {
                $data = [
                    ...$data,
                    ...$this->uploadImages($images, $user->id, $uploadedPaths),
                ];
                $updatedUser = $this->userRepository->update($data, $user);

                if ($updatedUser !== null && $roleId !== null) {
                    $this->roleRepository->assignRoleToUser($role, $updatedUser);
                }

                return $updatedUser?->load(['roles', 'parent']);
            });
        } catch (Throwable $throwable) {
            $this->deleteImages($uploadedPaths);

            throw $throwable;
        }

        if ($user === null) {
            return ServiceResult::error(__('public.not_found', ['attribute' => 'کاربر']));
        }

        foreach ($currentPaths as $field => $currentPath) {
            if (array_key_exists($field, $data) || isset($images[$field])) {
                $this->imageUploader->delete($currentPath);
            }
        }

        return ServiceResult::success($user);
    }

    public function destroy(?User $user = null): ServiceResult
    {
        $user ??= auth()->user();
        $imagePaths = collect(array_keys(self::IMAGE_FIELDS))
            ->map(fn (string $field): ?string => $user->{$field})
            ->all();

        DB::transaction(function () use ($user): void {
            $user->children()->update(['parent_id' => null]);
            $this->userRepository->destroy($user);
        });
        $this->deleteImages($imagePaths);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'کاربر']));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, UploadedFile>
     */
    private function pullImages(array &$data): array
    {
        $images = [];

        foreach (array_keys(self::IMAGE_FIELDS) as $field) {
            $image = $data[$field] ?? null;
            unset($data[$field]);

            if ($image instanceof UploadedFile) {
                $images[$field] = $image;
            }
        }

        return $images;
    }

    /**
     * @param  array<string, UploadedFile>  $images
     * @param  array<int, string>  $uploadedPaths
     * @return array<string, string>
     */
    private function uploadImages(array $images, int $userId, array &$uploadedPaths): array
    {
        $imagePaths = [];

        foreach ($images as $field => $image) {
            $path = $this->imageUploader->upload(
                $image,
                $userId,
                self::IMAGE_FIELDS[$field]['collection'],
            );
            $imagePaths[$field] = $path;
            $uploadedPaths[] = $path;
        }

        return $imagePaths;
    }

    /** @param array<int, string|null> $paths */
    private function deleteImages(array $paths): void
    {
        foreach ($paths as $path) {
            $this->imageUploader->delete($path);
        }
    }

    /** @param array<string, mixed> $data */
    private function validateParent(array $data, ?User $user = null): void
    {
        if (! array_key_exists('parent_id', $data) || $data['parent_id'] === null) {
            return;
        }

        $companyId = $data['company_id'] ?? $user?->company_id;
        $parent = $companyId === null
            ? null
            : User::query()
                ->whereKey((int) $data['parent_id'])
                ->where('company_id', $companyId)
                ->first();

        if ($parent === null || $this->wouldCreateParentCycle($parent, $user)) {
            throw ValidationException::withMessages([
                'parent_id' => 'کاربر بالادستی باید یکی از اعضای فعال همان شرکت باشد.',
            ]);
        }
    }

    private function wouldCreateParentCycle(User $parent, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $visitedUserIds = [];
        $candidate = $parent;

        while ($candidate !== null) {
            if ((int) $candidate->id === (int) $user->id
                || in_array((int) $candidate->id, $visitedUserIds, true)) {
                return true;
            }

            $visitedUserIds[] = (int) $candidate->id;
            $candidate = $candidate->parent_id === null
                ? null
                : User::withTrashed()->find($candidate->parent_id);
        }

        return false;
    }
}
