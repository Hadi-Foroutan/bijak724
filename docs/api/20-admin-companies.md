# شرکت‌ها (ادمین)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل ادمین — **تعداد روت:** ۷

مدیریت شرکت‌ها؛ فقط توکن سیستم.

### ۲۰.۱ روت‌ها و مجوزها

| #    | متد        | مسیر                                      | نوع ورودی    | مجوز لازم                          | کاربرد                    |
|------|------------|-------------------------------------------|--------------|------------------------------------|---------------------------|
| ۲۰.۱ | GET        | `/api/admin/companies`                    | query string | `admin.companies.index`            | لیست شرکت‌ها               |
| ۲۰.۲ | POST       | `/api/admin/companies`                    | JSON         | `admin.companies.store`            | ایجاد شرکت و حساب مدیر آن |
| ۲۰.۳ | GET        | `/api/admin/companies/aggregate-report`   | query string | `admin.companies.aggregate-report` | گزارش تجمیعی شرکت‌ها       |
| ۲۰.۴ | GET        | `/api/admin/companies/{company}`          | query string | `admin.companies.show`             | جزئیات شرکت               |
| ۲۰.۵ | PUT\|PATCH | `/api/admin/companies/{company}`          | JSON         | `admin.companies.update`           | ویرایش شرکت               |
| ۲۰.۶ | DELETE     | `/api/admin/companies/{company}`          | ندارد        | `admin.companies.destroy`          | حذف شرکت                  |
| ۲۰.۷ | POST       | `/api/admin/companies/{company}/login-as` | JSON         | `admin.companies.loginAs`          | صدور توکن پشتیبانی یک‌روزه |

### ۲۰.۲ ورودی و خروجی

| فیلد                                                                                                                                                                                          | نوع    | قواعد                                                                                                     |
|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-----------------------------------------------------------------------------------------------------------|
| `parent_type`!                                                                                                                                                                                | enum   | `CompanyParent`                                                                                           |
| `parent_id`                                                                                                                                                                                   | int    | وقتی `parent_type` برابر `branch` است الزامی است و باید یک شرکت اصلیِ سطح بالا باشد؛ در غیر این صورت ممنوع |
| `organization_code`!                                                                                                                                                                          | string | یکتا                                                                                                      |
| `name`!                                                                                                                                                                                       | string | حداکثر ۲۵۵                                                                                                |
| `national_code`!                                                                                                                                                                              | string | یکتا، حداکثر ۲۰                                                                                           |
| `city_code`!                                                                                                                                                                                  | int    | یک `cities.code` موجود                                                                                    |
| `contact_code1..3`، `technical_contact_first_name`، `technical_contact_last_name`، `technical_contact_phone`، `tel`، `fax`، `postal_code`، `email`، `logo`، `brand`، `address`، `description` | string | اختیاری                                                                                                   |
| `status`                                                                                                                                                                                      | enum   | `Status`                                                                                                  |

پاسخ (`CompanyResource`): همهٔ فیلدهای بالا به‌علاوهٔ `id, panel_code, created_at, updated_at, account` (در صورت
بارگذاری، `UserResource` مدیر شرکت). ایجاد شرکت به‌طور خودکار قرارداد حمل پیش‌فرض، بازه‌های پیش‌فرض بیجک و حواله، بیمهٔ
پیش‌فرض و تنظیمات پیش‌فرض را هم می‌سازد. شعبه‌ها از جداول شرکت مادر استفاده می‌کنند.

`GET /admin/companies/aggregate-report` دستور لیست را به‌علاوهٔ `min-/max-/eq-waybills_count`، `last_waybill_from` و
`last_waybill_to` می‌پذیرد. مرتب‌سازی بر اساس
`id, name, organization_code, panel_code, national_code, status, created_at, waybills_count, last_waybill_at` ممکن است و
خروجی شامل
`id, parent_id, parent_type, panel_code, organization_code, name, national_code, status, waybills_count, last_waybill_at`
است.
