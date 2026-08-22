<?php

return [
    'groups' => [
        'admin.companies.' => 'مدیریت شرکت‌های ادمین',
        'admin.cities.' => 'مدیریت شهرهای ادمین',
        'admin.users.' => 'مدیریت کاربران ادمین',
        'admin.permissions.' => 'مدیریت پرمیشن‌های ادمین',
        'admin.roles.' => 'مدیریت نقش‌های ادمین',
        'user.' => 'مدیریت پنل کاربر',
        'profile.' => 'مدیریت پروفایل کاربر',
    ],

    'roles' => [
        'superAdmin' => ['*'],
        'admin' => ['admin.*'],
        'user' => ['user.*', 'profile.*'],
    ],

    'non_default_permissions' => [

    ],
];
