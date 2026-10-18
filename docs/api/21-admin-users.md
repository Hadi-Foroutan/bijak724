# کاربران (ادمین)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل ادمین — **تعداد روت:** ۷

مدیریت همهٔ کاربران؛ فقط توکن سیستم.

### ۲۱.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۲۱.۱ | GET | `/api/admin/users` | query string | `admin.users.index` | لیست کاربران |
| ۲۱.۲ | POST | `/api/admin/users` | JSON | `admin.users.store` | ایجاد کاربر |
| ۲۱.۳ | GET | `/api/admin/users/{user}` | query string | `admin.users.show` | جزئیات کاربر |
| ۲۱.۴ | PUT\|PATCH | `/api/admin/users/{user}` | JSON | `admin.users.update` | ویرایش کاربر |
| ۲۱.۵ | DELETE | `/api/admin/users/{user}` | ندارد | `admin.users.destroy` | حذف کاربر |
| ۲۱.۶ | GET | `/api/admin/users/{user}/permissions` | query string | `admin.users.permissions` | مجوزهای یک کاربر |
| ۲۱.۷ | POST | `/api/admin/users/permissions/{user}` | JSON | `admin.users.syncPermissions` | جایگزینی مجوزهای کاربر |

### ۲۱.۲ ورودی و خروجی

همان فیلدهای کاربران شرکت، به‌علاوهٔ `role_id`! (یک نقش موجود) و `company_id` (برای نقش‌های شرکتی الزامی). `status`! الزامی است و پرچم‌های حذف تصویر `remove_profile_image`/`is_profile_delete` و `remove_signature_image`/`is_signature_delete` هستند. `POST /admin/users/permissions/{user}` بدنهٔ `{ permissions: number[] }` (الزامی، شناسه‌های موجود) می‌گیرد.
