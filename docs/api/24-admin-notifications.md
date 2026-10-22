# اعلان‌ها (ادمین)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل ادمین — **تعداد روت:** ۳

ارسال اعلان لحظه‌ای و تاریخچه.

### ۲۴.۱ روت‌ها و مجوزها

| #    | متد  | مسیر                                      | نوع ورودی    | مجوز لازم                   | کاربرد             |
|------|------|-------------------------------------------|--------------|-----------------------------|--------------------|
| ۲۴.۱ | GET  | `/api/admin/notifications`                | query string | `admin.notifications.index` | اعلان‌های ارسال‌شده  |
| ۲۴.۲ | POST | `/api/admin/notifications`                | JSON         | `admin.notifications.store` | ارسال اعلان لحظه‌ای |
| ۲۴.۳ | GET  | `/api/admin/notifications/{notification}` | query string | `admin.notifications.show`  | جزئیات اعلان       |

### ۲۴.۲ ورودی و خروجی

بدنهٔ ارسال: `{ type, message, should_remove_previous?, user_id?, company_id? }` (`type`: success، error، warning، info).

- بدون `user_id` و `company_id`: اعلان **عمومی** برای همه (کانال `notifications`).
- `user_id`: اعلان **اختصاصی** برای یک کاربر (کانال `users.{id}.notifications`).
- `company_id`: اعلان **اختصاصی** برای همهٔ کاربران یک شرکت (کانال `companies.{id}.notifications`).
- ارسال همزمان `user_id` و `company_id` خطای ۴۲۲ می‌دهد.
- خروجی علاوه بر فیلدهای قبلی: `is_private`، `recipient_user{id, full_name}` و `recipient_company{id, name}`. جزئیات پخش لحظه‌ای در
بخش «اعلان‌ها (کاربر شرکت)».
