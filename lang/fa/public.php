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
    'bijak_not_found' => 'شماره بیجک فعالی یافت نشد.',
    'bijak_issuance_unavailable' => 'شماره بیجک فعالی برای صدور بارنامه وجود ندارد.',
    'bijak_range_invalid' => 'بازه شماره بیجک با آخرین شماره ثبت‌شده سازگار نیست.',
    'bijak_active_exists' => 'برای این شرکت یک بازه شماره بیجک فعال وجود دارد.',
    'referral_not_found' => 'شماره حوالهٔ فعالی یافت نشد.',
    'referral_issuance_unavailable' => 'شماره حوالهٔ فعالی برای صدور بارنامه وجود ندارد.',
    'referral_range_invalid' => 'بازهٔ شماره حواله با آخرین شمارهٔ ثبت‌شده سازگار نیست.',
    'referral_active_exists' => 'برای این شرکت یک شماره حوالهٔ فعال وجود دارد.',
    'waybill_referral_number_used' => 'شماره حوالهٔ :number با سریال :serial قبلاً برای بارنامه دیگری استفاده شده است.',
    'waybill_referral_range_invalid' => 'شماره حواله واردشده در بازه فعال شماره حواله قرار ندارد.',
    'waybill_referral_number_minimum' => 'شماره حواله باید از آخرین شماره ثبت‌شده بزرگ‌تر و حداقل :number باشد.',
    'waybill_referral_number_immutable' => 'شماره حواله تخصیص‌یافته را نمی‌توان ویرایش کرد؛ ابتدا آن را ابطال کنید.',
    'waybill_referral_already_assigned' => 'این بارنامه قبلاً شماره حواله دریافت کرده است؛ برای دریافت شماره جدید ابتدا حواله فعلی را ابطال کنید.',
    'waybill_referral_not_assigned' => 'این بارنامه شماره حواله‌ای برای ابطال ندارد.',
    'waybill_referral_assignment_status_invalid' => 'برای بارنامه صادرشده یا باطل‌شده نمی‌توان شماره حواله دریافت کرد.',
    'waybill_referral_assigned_success' => 'شماره حواله با موفقیت به بارنامه تخصیص داده شد.',
    'waybill_referral_canceled_success' => 'شماره حواله بارنامه با موفقیت ابطال شد.',
    'waybill_bijak_number_used' => 'شماره بیجک :number با سریال :serial قبلاً برای بارنامه دیگری استفاده شده است.',
    'waybill_referral_number_duplicate' => 'ترکیب سریال و شماره حواله قبلاً استفاده شده است.',
    'waybill_number_duplicate' => 'ترکیب سریال و شماره بارنامه قبلاً استفاده شده است.',
    'waybill_bijak_range_invalid' => 'بازه شماره بیجک انتخاب شده معتبر نیست.',
    'waybill_first_available_bijak_required' => 'ابتدا باید شماره بیجک آزاد :number صادر شود.',
    'waybill_edit_forbidden' => 'بارنامه صادرشده یا باطل‌شده قابل ویرایش نیست.',
    'waybill_direct_cancel_forbidden' => 'برای ابطال بارنامه باید از عملیات ابطال استفاده کنید.',
    'waybill_cancel_status_invalid' => 'فقط بارنامه صادرشده قابل ابطال است.',
    'waybill_canceled_success' => 'بارنامه با موفقیت باطل شد.',
    'waybill_status_incomplete' => 'ناتمام',
    'waybill_status_completed' => 'تمام‌شده',
    'waybill_status_referral' => 'صدور حواله',
    'waybill_status_canceled' => 'باطل‌شده',
    'issued_waybill_reference_delete_forbidden' => ':attribute به یک بارنامه صادرشده یا باطل‌شده متصل است و قابل حذف نیست.',
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
