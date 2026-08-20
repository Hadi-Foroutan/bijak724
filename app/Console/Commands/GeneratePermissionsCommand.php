<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionsGroup;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\UserPermission;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GeneratePermissionsCommand extends Command
{
    protected $signature = 'generate-permissions';

    protected $description = 'Sync permissions with routes and assign to roles';

    private array $excludedRoutes = [
        'sanctum.csrf-cookie',
        'storage.*',
        'scramble.*',
        'boost.*',
        '*.login',
        '*.logout',
        '*.checkToken',
    ];

    public function handle(): int
    {
        $this->info('Start syncing permissions...');

        $routeNames = $this->routeNames();

        $this->warn('Resetting permission tables...');
        $this->resetPermissionTables();
        $this->info('Tables cleared and IDs reset.');

        $permissions = $this->createPermissions($routeNames);
        $this->info("Created {$permissions->count()} permissions from routes.");

        $groups = $this->createPermissionGroups($permissions);
        $this->info("Created {$groups->count()} permission groups.");
        $this->displayPermissionGroups($groups);

        $this->info('Assigning permissions to roles...');
        $this->syncRolePermissions($permissions);

        $this->info('All permissions synced successfully!');

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, string>
     */
    private function routeNames(): Collection
    {
        return collect(Route::getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter(fn (?string $name): bool => filled($name))
            ->reject(fn (string $name): bool => $this->isExcludedRoute($name))
            ->unique()
            ->sort()
            ->values();
    }

    private function isExcludedRoute(string $name): bool
    {
        return collect($this->excludedRoutes)
            ->contains(fn (string $pattern): bool => Str::is($pattern, $name));
    }

    private function resetPermissionTables(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($this->permissionTables() as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * @return array<int, string>
     */
    private function permissionTables(): array
    {
        return [
            (new UserPermission)->getTable(),
            (new PermissionsGroup)->getTable(),
            (new RolePermission)->getTable(),
            (new PermissionGroup)->getTable(),
            (new Permission)->getTable(),
        ];
    }

    /**
     * @param  Collection<int, string>  $routeNames
     * @return Collection<string, Permission>
     */
    private function createPermissions(Collection $routeNames): Collection
    {
        return $routeNames->mapWithKeys(function (string $name): array {
            $displayName = $this->permissionDisplayName($name);

            $permission = Permission::query()->create([
                'name' => $name,
                'display_name' => $displayName,
                'description' => $displayName,
                'is_default' => $this->isDefaultPermission($name),
            ]);

            return [$name => $permission];
        });
    }

    private function permissionDisplayName(string $name): string
    {
        $translationKey = "permissions_description.{$name}";
        $description = trans($translationKey);

        if ($description === $translationKey) {
            return "دسترسی به {$name}";
        }

        return $description;
    }

    private function isDefaultPermission(string $name): bool
    {
        return ! Str::is($this->nonDefaultPermissionPatterns(), $name);
    }

    /**
     * @return array<int, string>
     */
    private function nonDefaultPermissionPatterns(): array
    {
        return config('permission_groups.non_default_permissions', []);
    }

    /**
     * @param  Collection<string, Permission>  $permissions
     * @return Collection<int, array{name: string, permissions_count: int}>
     */
    private function createPermissionGroups(Collection $permissions): Collection
    {
        $createdGroups = collect();

        foreach ($this->permissionGroupDefinitions() as $pattern => $groupName) {
            $matchedPermissions = $this->permissionsMatchingPattern($permissions, $pattern);

            if ($matchedPermissions->isEmpty()) {
                continue;
            }

            $group = PermissionGroup::query()->create([
                'name' => $groupName,
            ]);

            $this->attachPermissionsToGroup($group, $matchedPermissions);

            $createdGroups->push([
                'name' => $group->name,
                'permissions_count' => $matchedPermissions->count(),
            ]);
        }

        return $createdGroups;
    }

    /**
     * @return array<string, string>
     */
    private function permissionGroupDefinitions(): array
    {
        return config('permission_groups.groups', []);
    }

    /**
     * @param  Collection<string, Permission>  $permissions
     * @return Collection<string, Permission>
     */
    private function permissionsMatchingPattern(Collection $permissions, string $pattern): Collection
    {
        $normalizedPattern = Str::contains($pattern, '*') ? $pattern : "{$pattern}*";

        return $permissions->filter(
            fn (Permission $permission, string $name): bool => Str::is($normalizedPattern, $name)
        );
    }

    /**
     * @param  Collection<string, Permission>  $permissions
     */
    private function attachPermissionsToGroup(PermissionGroup $group, Collection $permissions): void
    {
        $group->permissions()->syncWithoutDetaching(
            $permissions->pluck('id')->toArray()
        );
    }

    /**
     * @param  Collection<int, array{name: string, permissions_count: int}>  $groups
     */
    private function displayPermissionGroups(Collection $groups): void
    {
        if ($groups->isEmpty()) {
            $this->warn('No permission groups matched current routes.');

            return;
        }

        $this->newLine();
        $this->info('Permission groups:');

        $groups->each(function (array $group): void {
            $this->line("- {$group['name']}: {$group['permissions_count']} permissions");
        });

        $this->newLine();
    }

    /**
     * @param  Collection<string, Permission>  $permissions
     */
    private function syncRolePermissions(Collection $permissions): void
    {
        foreach ($this->rolePermissionPatterns() as $roleName => $patterns) {
            $role = Role::query()->where('name', $roleName)->first();

            if (! $role) {
                $this->warn("Role [{$roleName}] not found, skipping role permissions.");

                continue;
            }

            $permissionIds = collect($patterns)
                ->flatMap(fn (string $pattern): Collection => $this->permissionsMatchingPattern($permissions, $pattern)->pluck('id'))
                ->unique()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
            $this->line('Synced '.count($permissionIds)." permissions to role: {$role->name}");
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rolePermissionPatterns(): array
    {
        return config('permission_groups.roles', []);
    }
}
