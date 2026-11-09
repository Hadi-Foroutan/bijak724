# اطلاعات پایه (فقط‌خواندنی)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۷

فهرست‌های فقط‌خواندنی برای dropdown؛ بدون بدنه.

### ۹.۱ روت‌ها و مجوزها

| #   | متد | مسیر                                    | نوع ورودی    | مجوز لازم                          | کاربرد                        |
|-----|-----|-----------------------------------------|--------------|------------------------------------|-------------------------------|
| ۹.۱ | GET | `/api/user/general/states`              | query string | `user.general.states`              | استان‌ها                       |
| ۹.۲ | GET | `/api/user/general/cities/{state}`      | query string | `user.general.cities`              | شهرهای یک استان               |
| ۹.۳ | GET | `/api/user/general/cargos`              | query string | `user.general.cargos`              | کاتالوگ کالاها                |
| ۹.۴ | GET | `/api/user/general/packaging`           | query string | `user.general.packaging`           | انواع بسته‌بندی                |
| ۹.۵ | GET | `/api/user/general/fleet-types`         | query string | `user.general.fleet-types`         | انواع ناوگان                  |
| ۹.۶ | GET | `/api/user/general/fleet-systems`       | query string | `user.general.fleet-systems`       | سیستم‌های ناوگان (برند کامیون) |
| ۹.۷ | GET | `/api/user/general/insurance-companies` | query string | `user.general.insurance-companies` | شرکت‌های بیمهٔ فعال             |

### ۹.۲ ورودی و خروجی

استان‌ها، شهرهای یک استان (`/cities/{state}`)، کالاها، بسته‌بندی، انواع ناوگان، سیستم‌های ناوگان و شرکت‌های بیمهٔ فعال
را برمی‌گردانند. کدِ کالا و بسته‌بندی همان مقداری است که در بارنامه (`cargo_id`، `packaging_id`) ارسال می‌شود.
هر ردیف شرکت بیمه `logo` (مسیر نسبی) و `logo_url` (نشانی کامل لوگو) هم دارد؛ برای شرکتی که لوگو ندارد هر دو `null` است.

> این فهرست‌ها فقط برای پنل شرکت‌اند. ادمین برای لیست شرکت‌های بیمه از `GET /api/admin/insurance-companies` و برای شهرها از `GET /api/admin/cities` استفاده کند ([بخش ۳۴](34-insurance-agencies.md)).
> در فرم قرارداد بیمهٔ شرکت دیگر به `insurance-companies` نیازی نیست (شرکت بیمه از روی کد نمایندگی تعیین می‌شود، [بخش ۱۵](15-insurances.md)).
