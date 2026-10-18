# تنظیمات شرکت

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۲

خواندن و ویرایش تنظیمات شرکت.

### ۶.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۶.۱ | GET | `/api/user/settings` | query string | `user.settings.show` | تنظیمات شرکت |
| ۶.۲ | PUT\|PATCH | `/api/user/settings` | JSON | `user.settings.update` | ویرایش تنظیمات شرکت |

### ۶.۲ ورودی و خروجی

فعلاً یک کلید وجود دارد: `general.assign_first_available_waybill_number` (بولی، پیش‌فرض `false`). آن را به این شکل بفرستید: `{ "general": { "assign_first_available_waybill_number": true } }`.
