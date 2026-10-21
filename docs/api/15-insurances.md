# بیمه‌ها و تعرفه‌ها

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۱۱

بیمه‌نامه‌ها، تعرفه‌ها و محاسبهٔ حق بیمه.

### ۱۵.۱ روت‌ها و مجوزها

| #     | متد        | مسیر                                                | نوع ورودی     | مجوز لازم                         | کاربرد               |
|-------|------------|-----------------------------------------------------|---------------|-----------------------------------|----------------------|
| ۱۵.۱  | GET        | `/api/user/insurances`                              | query string  | `user.insurances.index`           | بیمه‌نامه‌ها           |
| ۱۵.۲  | POST       | `/api/user/insurances`                              | JSON          | `user.insurances.store`           | ایجاد بیمه‌نامه       |
| ۱۵.۳  | GET\|POST  | `/api/user/insurances/inquiry`                      | query یا JSON | `user.insurances.inquiry`         | محاسبهٔ بیمه/تعرفه    |
| ۱۵.۴  | GET        | `/api/user/insurances/{insurance}`                  | query string  | `user.insurances.show`            | جزئیات بیمه‌نامه      |
| ۱۵.۵  | PUT\|PATCH | `/api/user/insurances/{insurance}`                  | JSON          | `user.insurances.update`          | ویرایش بیمه‌نامه      |
| ۱۵.۶  | DELETE     | `/api/user/insurances/{insurance}`                  | ندارد         | `user.insurances.destroy`         | حذف بیمه‌نامه         |
| ۱۵.۷  | GET        | `/api/user/insurances/{insurance}/tariffs`          | query string  | `user.insurances.tariffs.index`   | تعرفه‌های یک بیمه‌نامه |
| ۱۵.۸  | POST       | `/api/user/insurances/{insurance}/tariffs`          | JSON          | `user.insurances.tariffs.store`   | افزودن تعرفه         |
| ۱۵.۹  | GET        | `/api/user/insurances/{insurance}/tariffs/{tariff}` | query string  | `user.insurances.tariffs.show`    | جزئیات تعرفه         |
| ۱۵.۱۰ | PUT\|PATCH | `/api/user/insurances/{insurance}/tariffs/{tariff}` | JSON          | `user.insurances.tariffs.update`  | ویرایش تعرفه         |
| ۱۵.۱۱ | DELETE     | `/api/user/insurances/{insurance}/tariffs/{tariff}` | ندارد         | `user.insurances.tariffs.destroy` | حذف تعرفه            |

### ۱۵.۲ ورودی و خروجی

بیمه: `insurance_company_id`!، `title`!، `contract_number`! (یکتا در شرکت)، `is_default`!، `status`!، `start_date`!،
`end_date`! (≥ تاریخ شروع)، `description` و فیلدهای `representative_first_name`، `_last_name`، `_mobile`، `_phone`،
`_fax`، `_email` و `_address`. هر شرکت فقط یک بیمهٔ پیش‌فرض دارد؛ پیش‌فرض کردن یکی، قبلی را برمی‌دارد. پاسخ علاوه بر
این‌ها `insurance_company{id, name}`، `representative_full_name` و `tariffs[]` را دارد.

تعرفه — `/user/insurances/{insurance}/tariffs`: `cargo_group_id` (null یعنی همهٔ گروه‌ها)، `cargo_value_from`!،
`cargo_value_to` (≥ از، null یعنی بدون سقف)، `fixed_premium` یا `premium_percentage` (۰ تا ۱۰۰؛ یکی الزامی است)،
`excess_amount`، `description`. تعرفه‌ها بازه‌های ارزش برای هر گروه کالا هستند.

استعلام: `{ insurance_id, cargos: [{ id: <کد کالا>, value }] }` حق بیمه‌ای را که تعرفه‌های منطبق برای آن کالاها تولید
می‌کنند برمی‌گرداند.
