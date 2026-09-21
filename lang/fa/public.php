<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Language Lines
    |--------------------------------------------------------------------------
    |
    */

    'throttle' => 'درخواست بیش از حد مجاز! لطفا بعد از :seconds ثانیه دوباره امتحان کنید',
    'external_error' => 'خطا در ارتباط با سرویس خارجی',
    'validation_error' => 'ورودی نامعتبر است',
    'invalid_credentials' => 'نام کاربری یا رمزعبور اشتباه است',

    'internal_error' => 'خطای :attribute رخ داده است',

    'created_success' => ':attribute با موفقیت ایجاد شد',
    'update_success' => ':attribute با موفقیت بروزرسانی شد',
    'delete_success' => ':attribute حذف شد',
    'assigned_success' => ':attribute با موفقیت تخصیص داده شد',

    'delete_fail' => 'حذف امکان پذیر نمیباشد',

    'not_found' => ':attribute یافت نشد',
    'not_available' => ':attribute در دسترس نیست',
    'access_denied' => 'شما به این :attribute دسترسی ندارید',
    'image_upload_success' => 'عکس :attribute با موفقیت آپلود شد',
    'already_exists' => ':attribute از قبل وجود دارد',
    'status_changed' => 'وضعیت :attribute با موفقیت تغییر کرد',
    'status_not_allowed' => 'وضعیت :attribute برای این عملیات معتبر نیست',
    'permissions_assigned' => 'نقش تخصیص داده شد',
    'roles_assigned' => 'نقش تخصیص داده شد',
    'not_allowed_superadmin_user' => 'شما دسترسی انجام عملیات روی کاربر سوپر ادمین را ندارید',
    'has_relation' => 'این رکورد دارای :attribute فعال است',

    'accepted' => ':attribute با موفقیت پذیرفته شد',
    'rejected' => ':attribute با موفقیت رد شد',

    'invalid_field' => ':attribute معتبر نیست',

    // system
    'approved_by_system' => 'تایید شده توسط سیستم',

    'token_generated' => 'توکن موقت پشتیبانی صادر شد',

    'operation_success' => 'عملیات موفق بود',
    'operation_error' => 'خطایی رخ داده است',
    'unauthenticated' => 'احراز هویت نشده',
    'address_not_found' => 'آدرس یافت نشد',
    'driver_inactive' => 'راننده غیرفعال است.',
    'fleet_inactive' => 'ناوگان غیرفعال است.',
    'sender_inactive' => 'فرستنده غیرفعال است.',
    'receiver_inactive' => 'گیرنده غیرفعال است.',
    'duplicate_fleet_plate' => 'این پلاک قبلاً برای ناوگان دیگری ثبت شده است.',
    'fleet_system_required' => 'برای تیپ انتخاب‌شده، سیستم ناوگان الزامی است.',
    'fleet_tip_system_mismatch' => 'تیپ انتخاب‌شده متعلق به سیستم ناوگان نیست.',
    'shipment_party_role_required' => 'طرف حمل باید حداقل فرستنده یا گیرنده باشد.',
    'referral_not_found' => 'شماره حوالهٔ فعالی یافت نشد.',
    'referral_issuance_unavailable' => 'شماره حوالهٔ فعالی برای صدور بارنامه وجود ندارد.',
    'referral_range_invalid' => 'بازهٔ شماره حواله با آخرین شمارهٔ ثبت‌شده سازگار نیست.',
    'referral_active_exists' => 'برای این شرکت یک شماره حوالهٔ فعال وجود دارد.',
    'parent_user_invalid' => 'کاربر بالادستی باید یکی از اعضای فعال همان شرکت باشد.',
    'admin_company_forbidden' => 'برای کاربران ادمین و سوپر ادمین، انتخاب شرکت مجاز نیست.',
    'base_freight_type_forbidden' => 'برای آیتم کرایه پایه، انتخاب نوع ملکی، استیجاری، آزاد یا نامشخص مجاز نیست.',
    'insurance_end_date_invalid' => 'تاریخ پایان باید بعد از یا مساوی تاریخ شروع باشد.',
    'insurance_premium_required' => 'مبلغ ثابت یا درصد حق بیمه الزامی است.',
    'insurance_tariff_not_found' => 'تعرفه بیمه برای محموله یافت نشد.',
    'notification_type_success' => 'موفقیت‌آمیز',
    'notification_type_error' => 'خطا',
    'notification_type_warning' => 'هشدار',
    'notification_type_info' => 'اطلاعات',
    'notification_welcome_message' => 'به سامانه خوش آمدید.',
    'cargo_value_range_invalid' => 'حد بالای ارزش محموله نباید کمتر از حد پایین باشد.',
    'permission_name_invalid' => 'نام دسترسی باید فقط شامل حروف انگلیسی و خط تیره باشد و با دو یا سه نقطه بخش‌بندی شود.',
];
