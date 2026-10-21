# ناوگان

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۶

مدیریت ناوگان (خودروها).

### ۱۱.۱ روت‌ها و مجوزها

| #    | متد        | مسیر                       | نوع ورودی     | مجوز لازم             | کاربرد                    |
|------|------------|----------------------------|---------------|-----------------------|---------------------------|
| ۱۱.۱ | GET        | `/api/user/fleets`         | query string  | `user.fleets.index`   | لیست ناوگان (خودروها)     |
| ۱۱.۲ | POST       | `/api/user/fleets`         | JSON          | `user.fleets.store`   | ایجاد ناوگان              |
| ۱۱.۳ | GET\|POST  | `/api/user/fleets/inquiry` | query یا JSON | `user.fleets.inquiry` | یافتن ناوگان با پلاک کامل |
| ۱۱.۴ | GET        | `/api/user/fleets/{fleet}` | query string  | `user.fleets.show`    | جزئیات ناوگان             |
| ۱۱.۵ | PUT\|PATCH | `/api/user/fleets/{fleet}` | JSON          | `user.fleets.update`  | ویرایش ناوگان             |
| ۱۱.۶ | DELETE     | `/api/user/fleets/{fleet}` | ندارد         | `user.fleets.destroy` | حذف ناوگان                |

### ۱۱.۲ ورودی و خروجی

| فیلد                                                                                                  | نوع    | قواعد                                      |
|-------------------------------------------------------------------------------------------------------|--------|--------------------------------------------|
| `plate_first_number`!                                                                                 | string | ۲ رقم                                      |
| `plate_second_letter`!                                                                                | string | در ایجاد ۱ کاراکتر، در ویرایش تا ۳ کاراکتر |
| `plate_third_number`!                                                                                 | string | ۳ رقم                                      |
| `plate_fourth_number`!                                                                                | string | ۲ رقم (کد منطقه)                           |
| `smart_card_number`                                                                                   | string | یکتا در شرکت، حداکثر ۵۰                    |
| `status`، `ownership_type`                                                                            | enum   | `Status`، `FleetOwnershipType`             |
| `manufacture_year`                                                                                    | int    | ۱۳۰۰ تا سال آینده                          |
| `driver_license_type_id`، `loading_type_id`، `system_id`، `tip_code`                                  | int    | دو مورد اول باید شناسهٔ موجود باشند         |
| `owner_mobile`                                                                                        | string | `09` و سپس ۹ رقم                           |
| `insurance_policy_number`، `chassis_number`، `engine_number`، `vin`، `document_number`، `description` | string |                                            |
| `document_date`، `insurance_date`، `technical_inspection_valid_until`                                 | date   | `YYYY-MM-DD`                               |
| `has_violation`                                                                                       | bool   |                                            |

پاسخ علاوه بر این‌ها `plate{first_number, second_letter, third_number, fourth_number}`، `is_loading_type_fixed` و اشیای
تودرتوی `driver_license_type`، `loading_type`، `brand` و `type` را دارد. استعلام چهار بخش پلاک را می‌گیرد (حرف ۱ تا ۳
کاراکتر) و ناوگانِ شرکت با همان پلاک دقیق را برمی‌گرداند.
