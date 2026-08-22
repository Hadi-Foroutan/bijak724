<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Interfaces\CompanyInterface;
use App\Interfaces\PermissionInterface;
use App\Interfaces\RoleInterface;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function __construct(
        protected RoleInterface       $roleRepository,
        protected PermissionInterface $permissionRepository,
        protected CompanyInterface    $companyRepository,
    )
    {
    }

    public function run(): void
    {
        $users = [
            [
                'first_name' => 'میکائیل',
                'last_name' => 'نوبخت ادمین',
                'full_name' => 'میکائیل نوبخت ادمین',
                'username' => 'mikaiil',
                'password' => Hash::make('admin'),
                'phone' => '09934142558',
                'national_code' => '1870675274',
            ],
            [
                'first_name' => 'میکائیل',
                'last_name' => 'نوبخت یوزر',
                'full_name' => 'میکائیل نوبخت یوزر',
                'username' => 'mikaiil1380',
                'password' => Hash::make('user'),
                'phone' => '09037402114',
                'national_code' => '1870675275',
            ],
            [
                'first_name' => 'رضا',
                'last_name' => 'پاکزاد ادمین',
                'full_name' => 'رضا پاکزاد ادمین',
                'username' => 'reza1899',
                'password' => Hash::make('1020663'),
                'phone' => '09137603370',
                'national_code' => '0828454241',
            ],
            [
                'first_name' => 'رضا',
                'last_name' => 'پاکزاد یوزر',
                'full_name' => 'رضا پاکزاد یوزر',
                'username' => 'reza1900',
                'password' => Hash::make('1020663'),
                'phone' => '09216996281',
                'national_code' => '0828454240',
            ],
            [
                'first_name' => 'بهار',
                'last_name' => 'کشوری',
                'full_name' => 'بهار کشوری',
                'username' => 'bahar1385',
                'password' => Hash::make('bahar1385'),
                'phone' => '09909967286',
                'national_code' => '1275014811',
            ]
        ];

        foreach ($users as $userData) {

            if (! str_contains($userData['last_name'], 'ادمین')) {
                $company = $this->companyRepository->findByNationalCode('1026622603');

                if ($company) {
                    $userData['company_id'] = $company->id;
                }
            }

            $user = User::create($userData);

            $roleAdmin = $this->roleRepository->findByName(RoleEnum::ADMIN->value);
            $roleUser = $this->roleRepository->findByName(RoleEnum::USER->value);

            if (str_contains($user->last_name, 'ادمین')) {

                if ($roleAdmin) {
                    $this->roleRepository->assignRoleToUser($roleAdmin, $user);
                    $this->roleRepository->syncPermissionsToUser($user, $roleAdmin);
                }

            } else {

                if ($roleUser) {
                    $this->roleRepository->assignRoleToUser($roleUser, $user);
                    $this->roleRepository->syncDefaultPermissionsToUser($user, $roleUser);
                }
            }
        }
    }
}
