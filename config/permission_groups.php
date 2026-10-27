<?php

return [
    'groups' => [
        'admin.companies.' => 'مدیریت شرکت‌های ادمین',
        'admin.cities.' => 'مدیریت شهرهای ادمین',
        'admin.notifications.' => 'مدیریت اعلان‌های ادمین',
        'admin.waybills.' => 'مشاهده بارنامه‌های شرکت‌ها',
        'admin.report-fields.' => 'مدیریت فیلدهای گزارش‌ساز',
        'admin.settings.' => 'تنظیمات کلی سیستم',
        'admin.users.' => 'مدیریت کاربران ادمین',
        'admin.permissions.' => 'مدیریت پرمیشن‌های ادمین',
        'admin.roles.' => 'مدیریت نقش‌های ادمین',
        'user.dashboard.' => 'مدیریت داشبورد کاربر',
        'user.notifications.' => 'مشاهده اعلان‌های کاربر',
        'user.settings.' => 'مدیریت تنظیمات شرکت',
        'user.shared-additional-fields.' => 'مدیریت فیلدهای اضافی مشترک',
        'user.users.' => 'مدیریت کاربران شرکت',
        'user.drivers.' => 'مدیریت رانندگان',
        'user.fleets.' => 'مدیریت ناوگان',
        'user.bijak-numbers.' => 'مدیریت شماره بیجک‌ها',
        'user.referral-numbers.' => 'مدیریت شماره حواله‌ها',
        'user.canceled-referrals.' => 'مشاهده حواله‌های باطل‌شده',
        'user.shipment-parties.' => 'مدیریت فرستندگان و گیرندگان',
        'user.addresses.' => 'مدیریت آدرس‌های فرستندگان و گیرندگان',
        'user.waybills.view-' => 'نمایش بارنامه',
        'user.waybills.' => 'مدیریت بارنامه‌ها',
        'user.cargos.' => 'مدیریت محموله‌ها',
        'user.product-owners.' => 'مدیریت صاحبان کالا',
        'user.transport-contracts.' => 'مدیریت قراردادهای حمل',
        'user.insurances.' => 'مدیریت بیمه‌ها و تعرفه‌ها',
        'user.technical-managers.' => 'مدیریت مدیران فنی',
        'user.reports.' => 'گزارش‌ساز',
        'user.report-exports.' => 'گزارش‌های اخیر و خروجی‌ها',
        'user.cargo-groups*' => 'مدیریت گروه‌بندی کالاها',
        'user.general.' => 'اطلاعات عمومی کاربران',
        'profile.' => 'مدیریت پروفایل کاربر',
        'user.permissions.' => 'مدیریت دسترسی ها',
    ],

    /*
     * دسترسی‌هایی که مسیر (route) ندارند و شرط‌های داخل کد هستند.
     * توضیح فارسی هر کدام باید در lang/fa/permissions_description.php باشد.
     */
    'extra_permissions' => [
        'user.waybills.view-all-users',
        'user.waybills.view-peer-users',
        'user.waybills.view-parent-user',
    ],

    'roles' => [
        'superAdmin' => ['*'],
        'admin' => ['admin.*', 'profile.*'],
        'companyManager' => ['user.*', 'profile.*'],
        'user' => ['user.*', 'profile.*'],
        // دمو: همهٔ دسترسی‌های پنل شرکت، به‌جز موارد `role_exclusions` پایین.
        'demo' => ['user.*', 'profile.*'],
        // در دیتابیس فعلی نقش دمو با نام فارسی «دمو» ساخته شده است؛ همین الگو برای آن هم اعمال شود.
        'دمو' => ['user.*', 'profile.*'],
    ],

    /*
     * دسترسی‌هایی که یک نقش با وجود الگوی بالا **نمی‌گیرد** (الگوها مثل `roles`؛ `*` برای هر مقدار).
     * دمو فقط مشاهده و ایجاد/ویرایش داده‌های کاری را دارد؛ تغییر پروفایل، اطلاعات شرکت، مدیریت کاربران و
     * دسترسی‌ها و هر نوع حذف را ندارد.
     */
    'role_exclusions' => [
        'demo' => [
            'profile.update',
            'user.settings.update',
            'user.users.store',
            'user.users.update',
            'user.users.destroy',
            'user.permissions.update',
            '*.destroy',
        ],
        'دمو' => [
            'profile.update',
            'user.settings.update',
            'user.users.store',
            'user.users.update',
            'user.users.destroy',
            'user.permissions.update',
            '*.destroy',
        ],
    ],

    'default_only_roles' => [
        'user',
    ],

    'non_default_permissions' => [
        'user.users.*',
        'user.technical-managers.*',
        // مدیریت دسترسی‌ها فقط با تیک دستی مدیر داده می‌شود، نه به هر کاربر ساده.
        'user.permissions.*',
        // فقط با تیک دستی مدیر داده می‌شوند؛ کاربر ساده به‌صورت پیش‌فرض آن‌ها را ندارد.
        'user.waybills.view-*',
        //        'user.fleets.*'
    ],
];
