<?php

return [
    'groups' => [
        'admin.companies.' => 'مدیریت شرکت‌های ادمین',
        'admin.cities.' => 'مدیریت شهرهای ادمین',
        'admin.users.' => 'مدیریت کاربران ادمین',
        'admin.permissions.' => 'مدیریت پرمیشن‌های ادمین',
        'admin.roles.' => 'مدیریت نقش‌های ادمین',
        'user.dashboard.' => 'مدیریت داشبورد کاربر',
        'user.drivers.' => 'مدیریت رانندگان',
        'user.fleets.' => 'مدیریت ناوگان',
        'profile.' => 'مدیریت پروفایل کاربر',
    ],

    'roles' => [
        'superAdmin' => ['*'],
        'admin' => ['admin.*'],
        'companyManager' => ['user.*', 'profile.*'],
        'user' => ['user.*', 'profile.*'],
    ],

    'default_only_roles' => [
        'user',
    ],

    'non_default_permissions' => [
        //        'user.fleets.*'
    ],
];
