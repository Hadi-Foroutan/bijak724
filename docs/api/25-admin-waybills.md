# بارنامه‌های همهٔ شرکت‌ها (ادمین)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل ادمین — **تعداد روت:** ۱

لیست بارنامه‌ها در همهٔ شرکت‌ها.

### ۲۵.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۲۵.۱ | GET | `/api/admin/waybills` | query string | `admin.waybills.index` | بارنامه‌های همهٔ شرکت‌ها |

### ۲۵.۲ ورودی و خروجی

فیلترها: `company_id`، `company_name`، `status`، `created_at_from`، `created_at_to`، `serial_number`، `referral_number`، `bijak_tracking_code` به‌علاوهٔ دستور لیست مشترک (بخش ۱).
