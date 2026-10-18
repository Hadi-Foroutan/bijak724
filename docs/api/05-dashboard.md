# داشبورد

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۵

آمار فقط‌خواندنی شرکت.

### ۵.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۵.۱ | GET | `/api/user/dashboard` | query string | `user.dashboard.index` | شمارنده‌های خلاصه |
| ۵.۲ | GET | `/api/user/dashboard/waybills/daily` | query string | `user.dashboard.waybills.daily` | بارنامه‌ها به تفکیک روز |
| ۵.۳ | GET | `/api/user/dashboard/waybills/monthly` | query string | `user.dashboard.waybills.monthly` | بارنامه‌ها به تفکیک ماه |
| ۵.۴ | GET | `/api/user/dashboard/cargos/top` | query string | `user.dashboard.cargos.top` | پرحمل‌ترین کالاها |
| ۵.۵ | GET | `/api/user/dashboard/drivers/top` | query string | `user.dashboard.drivers.top` | پرکارترین رانندگان |

### ۵.۲ ورودی و خروجی

آمار فقط‌خواندنی شرکت: شمارنده‌های خلاصه، تعداد بارنامه‌های روزانه و ماهانه، پرحمل‌ترین کالاها و پرکارترین رانندگان. آن‌ها را به شکل نمودار نمایش دهید؛ بدنهٔ درخواست ندارند.
