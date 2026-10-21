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

بدنهٔ ارسال: `{ type, message, should_remove_previous? }` (`type`: success، error، warning، info). جزئیات پخش لحظه‌ای در
بخش «اعلان‌ها (کاربر شرکت)».
