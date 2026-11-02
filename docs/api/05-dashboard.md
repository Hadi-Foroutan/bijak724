# داشبورد

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۵

آمار فقط‌خواندنی شرکت.

### ۵.۱ روت‌ها و مجوزها

| #   | متد | مسیر                                   | نوع ورودی    | مجوز لازم                         | کاربرد                 |
|-----|-----|----------------------------------------|--------------|-----------------------------------|------------------------|
| ۵.۱ | GET | `/api/user/dashboard`                  | query string | `user.dashboard.index`            | شمارنده‌های خلاصه       |
| ۵.۲ | GET | `/api/user/dashboard/waybills/daily`   | query string | `user.dashboard.waybills.daily`   | بارنامه‌ها به تفکیک روز |
| ۵.۳ | GET | `/api/user/dashboard/waybills/monthly` | query string | `user.dashboard.waybills.monthly` | بارنامه‌ها به تفکیک ماه |
| ۵.۴ | GET | `/api/user/dashboard/cargos/top`       | query string | `user.dashboard.cargos.top`       | پرحمل‌ترین کالاها       |
| ۵.۵ | GET | `/api/user/dashboard/drivers/top`      | query string | `user.dashboard.drivers.top`      | پرکارترین رانندگان     |

### ۵.۲ ورودی و خروجی

آمار فقط‌خواندنی شرکت: شمارنده‌های خلاصه، تعداد بارنامه‌های روزانه و ماهانه، پرحمل‌ترین کالاها و پرکارترین رانندگان.
آن‌ها را به شکل نمودار نمایش دهید؛ بدنهٔ درخواست ندارند.

---

## داشبورد ادمین

هر بخش متد و مسیر خودش را دارد. مجوزها: `admin.dashboard.index` و `admin.dashboard.companies.top`.

### خلاصه — `GET /api/admin/dashboard`

```json
{ "data": { "total_waybills": 1250, "today_waybills": 38, "active_companies_today": 9, "total_companies": 54, "total_users": 210 } }
```

- `total_waybills`: تعداد کل بیجک‌های همهٔ شرکت‌ها.
- `today_waybills`: بیجک‌هایی که امروز ثبت شده‌اند.
- `active_companies_today`: شرکت‌هایی که امروز دست‌کم یک بیجک ثبت کرده‌اند.
- `total_companies`: تعداد شرکت‌ها.
- `total_users`: تعداد کاربران شرکت‌ها (کاربران پنل ادمین، که شرکت ندارند، شمرده نمی‌شوند).

### ۷ شرکت برتر این ماه — `GET /api/admin/dashboard/companies/top`

```json
{ "data": { "from": "2026-11-01", "to": "2026-11-30",
            "items": [ { "company_id": 3, "name": "شرکت نمونه", "organization_code": "10470", "count": 120 } ] } }
```

`count` تعداد بارنامه‌های **صادرشده** (تکمیل‌شده، با تاریخ صدور در این ماه) است، از پرتعداد به کم‌تعداد، حداکثر ۷ شرکت.

### کش

داشبورد کاربر و ادمین یک کلید دارند: `DASHBOARD_CACHE=false` در `.env` یعنی هر بار از دیتابیس خوانده می‌شود، `true` (پیش‌فرض) یعنی کش با عمر `DASHBOARD_CACHE_TTL_SECONDS`. بعد از تغییر `.env` دستور `php artisan optimize:clear` را بزنید.
