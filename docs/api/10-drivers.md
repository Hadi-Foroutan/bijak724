# رانندگان و حساب‌های بانکی

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۱۱

مدیریت رانندگان و حساب‌های بانکی آن‌ها.

### ۱۰.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۱۰.۱ | GET | `/api/user/drivers` | query string | `user.drivers.index` | لیست رانندگان |
| ۱۰.۲ | POST | `/api/user/drivers` | multipart/form-data | `user.drivers.store` | ایجاد راننده (multipart) |
| ۱۰.۳ | GET\|POST | `/api/user/drivers/inquiry` | query یا JSON | `user.drivers.inquiry` | یافتن راننده با کد ملی |
| ۱۰.۴ | GET | `/api/user/drivers/{driver}` | query string | `user.drivers.show` | جزئیات راننده |
| ۱۰.۵ | POST | `/api/user/drivers/{driver}` | multipart/form-data | `user.drivers.update` | ویرایش راننده (POST تا multipart کار کند) |
| ۱۰.۶ | DELETE | `/api/user/drivers/{driver}` | ندارد | `user.drivers.destroy` | حذف راننده |
| ۱۰.۷ | GET | `/api/user/drivers/{driver}/accounts` | query string | `user.drivers.accounts.index` | حساب‌های بانکی راننده |
| ۱۰.۸ | POST | `/api/user/drivers/{driver}/accounts` | JSON | `user.drivers.accounts.store` | افزودن حساب بانکی |
| ۱۰.۹ | GET | `/api/user/drivers/{driver}/accounts/{account}` | query string | `user.drivers.accounts.show` | جزئیات حساب |
| ۱۰.۱۰ | PUT\|PATCH | `/api/user/drivers/{driver}/accounts/{account}` | JSON | `user.drivers.accounts.update` | ویرایش حساب |
| ۱۰.۱۱ | DELETE | `/api/user/drivers/{driver}/accounts/{account}` | ندارد | `user.drivers.accounts.destroy` | حذف حساب |

### ۱۰.۲ ورودی و خروجی

ویرایش با `POST /user/drivers/{driver}` انجام می‌شود تا multipart کار کند.

| فیلد | نوع | قواعد |
| --- | --- | --- |
| `national_code`! | string | یکتا در شرکت |
| `first_name`!، `last_name`! | string | حداکثر ۲۵۵ |
| `license_type`! | int | شناسهٔ یک نوع گواهینامهٔ موجود |
| `father_name`، `license_number` | string |  |
| `license_expiry_date` | date | `YYYY-MM-DD` |
| `phone_number_1..3` | string | حداکثر ۲۰ |
| `profile_image` | file | jpg، jpeg، png، webp؛ حداکثر ۵۱۲۰ کیلوبایت |
| `remove_profile_image` / `is_profile_delete` | bool | فقط ویرایش |
| `description` | string |  |
| `status` | enum | `Status` |

پاسخ: `id, national_code, first_name, last_name, full_name, father_name, license_number, license_type_id, license_type, default_account, license_expiry_date, phone_number_1..3, profile_image_url, description, status, created_at, updated_at`.

استعلام: `national_code` (دقیقاً ۱۰ رقم) راننده را برمی‌گرداند یا 404.

حساب‌های بانکی — `/user/drivers/{driver}/accounts`: `sheba_number`! (حداکثر ۳۴)، `bank_name`!، `owner_name`!، `is_default`. اولین حساب پیش‌فرض می‌شود؛ پیش‌فرض کردن حساب دیگر، پیش‌فرض قبلی را برمی‌دارد و حذف حساب پیش‌فرض، حساب دیگری را پیش‌فرض می‌کند.
