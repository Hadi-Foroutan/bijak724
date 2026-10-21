# قراردادهای حمل

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۸

قراردادهای حمل و آیتم‌های آن.

### ۱۶.۱ روت‌ها و مجوزها

| #    | متد        | مسیر                                                      | نوع ورودی    | مجوز لازم                            | کاربرد                                           |
|------|------------|-----------------------------------------------------------|--------------|--------------------------------------|--------------------------------------------------|
| ۱۶.۱ | GET        | `/api/user/transport-contracts`                           | query string | `user.transport-contracts.index`     | قراردادها، پیش‌فرض‌ها در ابتدا                     |
| ۱۶.۲ | POST       | `/api/user/transport-contracts`                           | JSON         | `user.transport-contracts.store`     | ایجاد قرارداد با ۱۱ آیتم                         |
| ۱۶.۳ | GET        | `/api/user/transport-contracts/options`                   | query string | `user.transport-contracts.options`   | نام آیتم‌ها، انواع، پرچم‌های پیش‌فرض                |
| ۱۶.۴ | GET        | `/api/user/transport-contracts/{transportContract}`       | query string | `user.transport-contracts.show`      | جزئیات قرارداد                                   |
| ۱۶.۵ | PUT\|PATCH | `/api/user/transport-contracts/{transportContract}`       | JSON         | `user.transport-contracts.update`    | ویرایش قرارداد                                   |
| ۱۶.۶ | DELETE     | `/api/user/transport-contracts/{transportContract}`       | ندارد        | `user.transport-contracts.destroy`   | حذف (پس از استفاده در بارنامهٔ صادرشده ممکن نیست) |
| ۱۶.۷ | GET        | `/api/user/transport-contracts/{transportContract}/users` | query string | `user.transport-contracts.users`     | کاربرانی که قرارداد به آن‌ها محدود است            |
| ۱۶.۸ | PUT        | `/api/user/transport-contracts/{transportContract}/users` | JSON         | `user.transport-contracts.syncUsers` | محدود کردن به کاربران؛ `[]` آن را عمومی می‌کند    |

### ۱۶.۲ ورودی و خروجی

هر قرارداد دقیقاً ۱۱ آیتم دارد، یکی برای هر `TransportContractItemName`. هر آیتم مشخص می‌کند برای کدام نوع مالکیت ناوگان
اعمال می‌شود و مبلغش چگونه محاسبه می‌شود. `primary_value` درصدی از کرایهٔ پایه است (۰ تا ۱۰۰)؛ استثنا `base_freight` است
که در آن نرخ هر تن است و می‌تواند بیش از ۱۰۰ باشد. `secondary_value` یک مبلغ ثابت است (≥ ۰).

| فیلد                                                                     | نوع            | قواعد                                            |
|--------------------------------------------------------------------------|----------------|--------------------------------------------------|
| `title`!، `contract_number`!                                             | string         | شماره در شرکت یکتاست                             |
| `contract_date`!                                                         | date           | `YYYY-MM-DD`                                     |
| `customer_name`!                                                         | string         |                                                  |
| `status`!                                                                | enum           | `Status`                                         |
| `default_owned`!، `default_rental`!، `default_free`!، `default_unknown`! | bool           | بخش پرچم‌های پیش‌فرض را ببینید                     |
| `description`                                                            | string         |                                                  |
| `items`!                                                                 | array          | دقیقاً ۱۱، یکی برای هر نام آیتم، نام‌ها بدون تکرار |
| `items.*.name`                                                           | enum           | `TransportContractItemName`                      |
| `items.*.is_owned`، `is_rental`، `is_free`، `is_unknown`                 | bool           | برای `base_freight` همه `false`                  |
| `items.*.charge_recipient`                                               | bool           |                                                  |
| `items.*.primary_value`                                                  | number \| null | ۰ تا ۱۰۰، به‌جز `base_freight` (≥ ۰)              |
| `items.*.secondary_value`                                                | number \| null | ≥ ۰                                              |

در ویرایش، همهٔ فیلدهای سطح بالا اختیاری‌اند. `items` در PUT الزامی و در PATCH اختیاری است و در صورت ارسال، هر ۱۱ آیتم
را جایگزین می‌کند.

**پرچم‌های پیش‌فرض.** هر پرچم، قرارداد پیش‌فرض شرکت را برای یک نوع مالکیت ناوگان (ملکی، استیجاری، آزاد، نامشخص) مشخص
می‌کند. هر پرچم در هر شرکت فقط می‌تواند روی یک قرارداد باشد: ذخیرهٔ قراردادی با `default_rental: true` در همان تراکنش
`default_rental` را از همهٔ قراردادهای دیگر برمی‌دارد. یک قرارداد می‌تواند چند پرچم داشته باشد؛ قراردادی که هر شرکت جدید
دریافت می‌کند هر چهار پرچم را دارد. لیست‌ها قراردادهای دارای پرچم را اول نشان می‌دهند. فیلد `is_default` دیگر وجود
ندارد.

**پاسخ آیتم** (`TransportContractItemResource`):
`id, name, name_label, field_name, is_owned, is_rental, is_free, is_unknown, charge_recipient, primary_value, primary_value_label, secondary_value, secondary_value_label, editable_fields{is_owned, is_rental, is_free, is_unknown, charge_recipient, primary_value, secondary_value}`.
`field_name` ستون مبلغ بارنامه است که آیتم به آن مربوط می‌شود، مثلاً `commission` → `commission_amount`. از
`editable_fields` برای غیرفعال کردن ورودی‌ها استفاده کنید؛ در کرایهٔ پایه، چک‌باکس‌های نوع و `charge_recipient` قفل
هستند.

**گزینه‌ها** — `GET /user/transport-contracts/options` این‌ها را برمی‌گرداند:

- `items[]`: `{ id (1–11), value, label, field_name, primary_value_label, secondary_value_label, editable_fields }`.
- `types[]`: `{ field: "is_owned"…, value, label }`.
- `defaults[]`: `{ field: "default_unknown"…, value, label }` به ترتیب نامشخص، آزاد، استیجاری، ملکی.

فرم قرارداد را از همین پاسخ بسازید، نه با فهرست ثابت در کد.

**دسترسی.** قراردادها به‌طور پیش‌فرض عمومی‌اند (`is_public: true`). `PUT /{id}/users` با `{ user_ids: number[] }`
قرارداد را به همان کاربران محدود می‌کند؛ `[]` دوباره آن را عمومی می‌کند. کاربرانی که اجازهٔ مشاهدهٔ همهٔ قراردادها را
دارند (مدیران) همیشه همهٔ قراردادها را می‌بینند. پاسخ شامل `is_public` و در نمایش جزئیات، `user_ids` است.

قراردادی که در یک بارنامهٔ صادرشده یا باطل‌شده استفاده شده قابل حذف نیست (422).
