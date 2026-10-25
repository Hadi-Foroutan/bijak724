# مدیران فنی

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۵

هر شرکت مدیران فنی خودش را (در یک جدول مشترک با فیلد `company_id`) مدیریت می‌کند. برای صدور بارنامه انتخاب یک مدیر فنی فعال اجباری است و هر مدیر فنی سقف روزانه دارد.

### ۲۷.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۲۷.۱ | GET | `/api/user/technical-managers` | query string | `user.technical-managers.index` | لیست مدیران فنی |
| ۲۷.۲ | POST | `/api/user/technical-managers` | JSON | `user.technical-managers.store` | ایجاد مدیر فنی |
| ۲۷.۳ | GET | `/api/user/technical-managers/{technicalManager}` | query string | `user.technical-managers.show` | جزئیات مدیر فنی |
| ۲۷.۴ | PUT\|PATCH | `/api/user/technical-managers/{technicalManager}` | JSON | `user.technical-managers.update` | ویرایش مدیر فنی |
| ۲۷.۵ | DELETE | `/api/user/technical-managers/{technicalManager}` | ندارد | `user.technical-managers.destroy` | حذف مدیر فنی |

### ۲۷.۲ ورودی

| فیلد | نوع | قواعد |
| --- | --- | --- |
| `full_name`! | string | نام و نام خانوادگی، حداکثر ۲۵۵ |
| `national_code`! | string | ۱۰ رقم؛ در هر شرکت یکتا (در ویرایش، نگه داشتن کد خودش مجاز است) |
| `mobile`! | string | قالب `09xxxxxxxxx` |
| `status`! | enum | `active` یا `inactive` |
| `daily_limit`! | int | حداکثر تعداد بارنامهٔ قابل صدور در یک روز، ۰ تا ۱۰۰۰۰۰ |

### ۲۷.۳ خروجی

`id, company_id, full_name, national_code, mobile, status, daily_limit, issued_today, remaining_today, created_at, updated_at`

- `issued_today`: تعداد بارنامه‌های صادرشده (و ابطال‌شده) با این مدیر فنی در امروز.
- `remaining_today`: `daily_limit - issued_today` (حداقل ۰).
- حذف مدیر فنی‌ای که در بارنامهٔ صادرشده یا باطل‌شده استفاده شده، خطای `422` می‌دهد.

### ۲۷.۴ نقش مدیر فنی در بارنامه

- در ایجاد/ویرایش بارنامه فیلد `technical_manager_id` ارسال می‌شود. برای `status = completed` (صدور بارنامه) **الزامی** است؛ برای ناتمام و حواله اختیاری است.
- مدیر فنی باید متعلق به همین شرکت و در زمان صدور **فعال** باشد.
- اگر در روز صدور (`issued_at`) سقف `daily_limit` پر شده باشد، پاسخ `422` با کلید خطای `technical_manager_id` و پیام «حداکثر تعداد مجاز بارنامه در روز برای این مدیر فنی تمام شده است.» برمی‌گردد. سهمیه از روز بعد دوباره از ابتدا حساب می‌شود.
- هر بارنامهٔ صادرشده یک واحد از سهمیهٔ همان روز را مصرف می‌کند؛ ابطال بارنامه سهمیه را برنمی‌گرداند.
- هنگام صدور، **اسنپ‌شات** مدیر فنی در بارنامه ذخیره می‌شود: `technical_manager_full_name` و `technical_manager_mobile`. تغییر بعدی مشخصات مدیر فنی روی بارنامه‌های صادرشده اثری ندارد.
- پاسخ بارنامه علاوه بر `technical_manager_id` و اسنپ‌شات‌ها، شیء `technical_manager` را هم دارد.
- `GET /api/user/waybills/options` کلید `technical_managers` دارد: فقط مدیران فنی فعال با `daily_limit`، `issued_today` و `remaining_today` برای پر کردن لیست انتخاب.
