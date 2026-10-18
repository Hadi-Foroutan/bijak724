# بازه‌های شمارهٔ بیجک

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۶

بازه‌های شمارهٔ بیجک.

### ۱۷.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۱۷.۱ | GET | `/api/user/bijak-numbers` | query string | `user.bijak-numbers.index` | بازه‌های شمارهٔ بیجک |
| ۱۷.۲ | POST | `/api/user/bijak-numbers` | JSON | `user.bijak-numbers.store` | افزودن بازه |
| ۱۷.۳ | GET\|POST | `/api/user/bijak-numbers/inquiry` | query یا JSON | `user.bijak-numbers.inquiry` | شمارهٔ بیجک آزاد بعدی |
| ۱۷.۴ | GET | `/api/user/bijak-numbers/{bijakNumber}` | query string | `user.bijak-numbers.show` | جزئیات بازه |
| ۱۷.۵ | PUT\|PATCH | `/api/user/bijak-numbers/{bijakNumber}` | JSON | `user.bijak-numbers.update` | ویرایش بازه |
| ۱۷.۶ | DELETE | `/api/user/bijak-numbers/{bijakNumber}` | ندارد | `user.bijak-numbers.destroy` | حذف بازه |

### ۱۷.۲ ورودی و خروجی

هر دو فیلدهای یکسان دارند: `title`!، `serial_number`!، `from_number`! (≥ ۱)، `to_number`! (≥ از)، `last_number` (فقط در ایجاد؛ در ویرایش ممنوع) و `status` (`active`، `inactive` یا `completed`). شرکت‌های جدید یک بازهٔ حوالهٔ پیش‌فرضِ فعال با ۹۰۰۰۰۰ شماره دریافت می‌کنند. استعلام، شمارهٔ بعدی قابل استفاده را برمی‌گرداند: بیجک بدنه ندارد، حواله `{ waybill_id }` می‌گیرد. `GET /user/canceled-referrals` شماره‌های حوالهٔ ابطال‌شده را همراه با JSONهای `waybill_snapshot` و `cargos_snapshot` لیست می‌کند.
