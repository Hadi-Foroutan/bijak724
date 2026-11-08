# بیمه‌ها و تعرفه‌ها

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۱۲

بیمه‌نامه‌ها (قراردادهای بیمه)، تعرفه‌ها و محاسبهٔ حق بیمه.

> **تغییر مهم (نمایندگی بیمه):** شرکت دیگر شرکت بیمه و اطلاعات نماینده را دستی وارد نمی‌کند. فقط **`org_code` شرکت بیمه‌کننده** و **کد نمایندگی** را می‌فرستد
> (کد نمایندگی فقط برای همان شرکت بیمه یکتاست و شرکت‌های بیمهٔ دیگر می‌توانند همان کد را داشته باشند)، با استعلام اطلاعات نماینده را می‌بیند
> و قرارداد به آن نمایندگی وصل می‌شود. نمایندگی‌ها را ادمین می‌سازد ([بخش ۳۴](34-insurance-agencies.md)).

### ۱۵.۱ روت‌ها و مجوزها

| #     | متد        | مسیر                                                | نوع ورودی     | مجوز لازم                         | کاربرد                 |
|-------|------------|-----------------------------------------------------|---------------|-----------------------------------|------------------------|
| ۱۵.۱  | GET        | `/api/user/insurances`                              | query string  | `user.insurances.index`           | بیمه‌نامه‌ها             |
| ۱۵.۲  | POST       | `/api/user/insurances`                              | JSON          | `user.insurances.store`           | ایجاد بیمه‌نامه         |
| ۱۵.۳  | GET\|POST  | `/api/user/insurances/inquiry`                      | query یا JSON | `user.insurances.inquiry`         | محاسبهٔ بیمه/تعرفه      |
| ۱۵.۱۲ | GET\|POST  | `/api/user/insurances/agency-inquiry`               | query یا JSON | `user.insurances.agency-inquiry`  | استعلام نمایندگی با `org_code` شرکت بیمه + کد نمایندگی |
| ۱۵.۴  | GET        | `/api/user/insurances/{insurance}`                  | query string  | `user.insurances.show`            | جزئیات بیمه‌نامه        |
| ۱۵.۵  | PUT\|PATCH | `/api/user/insurances/{insurance}`                  | JSON          | `user.insurances.update`          | ویرایش بیمه‌نامه        |
| ۱۵.۶  | DELETE     | `/api/user/insurances/{insurance}`                  | ندارد         | `user.insurances.destroy`         | حذف بیمه‌نامه           |
| ۱۵.۷  | GET        | `/api/user/insurances/{insurance}/tariffs`          | query string  | `user.insurances.tariffs.index`   | تعرفه‌های یک بیمه‌نامه   |
| ۱۵.۸  | POST       | `/api/user/insurances/{insurance}/tariffs`          | JSON          | `user.insurances.tariffs.store`   | افزودن تعرفه           |
| ۱۵.۹  | GET        | `/api/user/insurances/{insurance}/tariffs/{tariff}` | query string  | `user.insurances.tariffs.show`    | جزئیات تعرفه           |
| ۱۵.۱۰ | PUT\|PATCH | `/api/user/insurances/{insurance}/tariffs/{tariff}` | JSON          | `user.insurances.tariffs.update`  | ویرایش تعرفه           |
| ۱۵.۱۱ | DELETE     | `/api/user/insurances/{insurance}/tariffs/{tariff}` | ندارد         | `user.insurances.tariffs.destroy` | حذف تعرفه              |

روت جدید `user.insurances.agency-inquiry` با `php artisan generate-permissions` ساخته و به `companyManager` و `user` داده می‌شود؛ برای کاربران موجود هم بعد از اجرای دستور فعال است.

### ۱۵.۲ بیمه‌نامه (قرارداد بیمه): ورودی

```json
{
  "insurance_company_org_code": 201,
  "insurance_agency_code": "101",
  "title": "بیمه باربری ۱۴۰۵",
  "contract_number": "C-1405-001",
  "is_default": true,
  "status": "active",
  "start_date": "2026-03-21",
  "end_date": "2027-03-20",
  "description": "..."
}
```

| فیلد                        | قاعده                                                                                                  |
|-----------------------------|--------------------------------------------------------------------------------------------------------|
| `insurance_company_org_code`! | **`org_code` شرکت بیمه‌کننده** (عدد؛ از `insurance_company.org_code` یا لیست شرکت‌های بیمه). شرکت بیمه از روی همین کد پیدا می‌شود |
| `insurance_agency_code`!    | **کد نمایندگی** (رشته). باید نمایندگی **فعال** و حذف‌نشده‌ای از **همان شرکت بیمه** با این کد باشد (کد نمایندگیِ شرکت بیمهٔ دیگر پذیرفته نمی‌شود)؛ در ویرایش اختیاری (پایین) |
| `title`!                    | رشته، حداکثر ۲۵۵                                                                                       |
| `contract_number`!          | رشته، حداکثر ۲۵۵، یکتا در شرکت                                                                         |
| `is_default`!               | بولی؛ هر شرکت فقط یک بیمهٔ پیش‌فرض دارد، پیش‌فرض‌کردن یکی قبلی را برمی‌دارد                              |
| `status`!                   | `active` / `inactive`                                                                                  |
| `start_date`!، `end_date`!  | `YYYY-MM-DD`؛ تاریخ پایان ≥ تاریخ شروع                                                                  |
| `description`               | متن اختیاری                                                                                            |

**فیلدهایی که حذف شدند:** `insurance_company_id` و همهٔ `representative_*` (نام، نام خانوادگی، موبایل، تلفن، فکس، ایمیل، آدرس) دیگر ورودی نیستند؛ اگر ارسال شوند **نادیده گرفته می‌شوند**.
شرکت بیمه و اطلاعات نماینده از روی نمایندگی پیدا شده با `org_code` + کد تعیین می‌شود. فیلدهای متناظر در فرم باید برداشته و به دو فیلد «کد شرکت بیمه‌کننده (`org_code`)» و «کد نمایندگی» + نمایش فقط‌خواندنیِ نتیجهٔ استعلام تبدیل شوند. نام نماینده (`representative_full_name`) برای نمایندگی حقوقی نام شرکت و برای حقیقی نام و نام خانوادگی است.

### ۱۵.۳ استعلام نمایندگی (`GET|POST /api/user/insurances/agency-inquiry`)

ورودی: `{ "insurance_company_org_code": 201, "code": "101" }` (query یا JSON) — هر دو الزامی‌اند. فرانت بعد از وارد کردن `org_code` و کد (با دکمهٔ «استعلام» یا هنگام خروج از فیلد) این را صدا بزند و اطلاعات نماینده را نمایش دهد:

```json
{
  "success": true,
  "message": "...",
  "data": {
    "id": 5, "code": "101",
    "type": "legal", "type_label": "حقوقی", "name": "کارگزاری سپهر",
    "company_name": "کارگزاری سپهر", "company_national_id": "14001234567",
    "first_name": null, "last_name": null, "full_name": "",
    "mobile": "09121111111", "phone": "02122222222", "fax": "02133333333", "email": "info@sepehr.example", "address": "تهران، ...", "description": "...",
    "city_code": 1101,
    "insurance_company_id": 3, "insurance_company": { "id": 3, "org_code": 201, "name": "پارسیان" },
    "status": "active",
    "created_at": "...", "updated_at": "..."
  }
}
```

- نمایش: اگر `type` برابر `legal` است `name`/`company_name` (و `company_national_id`) را نشان دهید، وگرنه `full_name`. کد ملی نماینده (`national_code`) در این خروجی **نیست**.
- نمایندگی فعالی از آن شرکت بیمه با این کد نباشد (کد اشتباه، `org_code` اشتباه، غیرفعال یا حذف‌شده): **`404`** با پیام «نمایندگی بیمه یافت نشد» در `errors.error[0]`. در UI زیر فیلد کد نشان دهید و دکمهٔ ثبت را غیرفعال نگه دارید.
- `code` یا `insurance_company_org_code` خالی: `422` روی همان فیلد.
- نتیجهٔ استعلام فقط برای نمایش است؛ هنگام ثبت باز هم **فقط `insurance_company_org_code` و `insurance_agency_code`** فرستاده می‌شود (سرور دوباره بررسی می‌کند).

### ۱۵.۴ قواعد ثبت و ویرایش

- **ثبت (`POST`)** بدون `insurance_company_org_code` یا `insurance_agency_code` `422` است (خطا روی فیلد خالی). با ترکیب نامعتبر (کد وجود ندارد، یا مال شرکت بیمهٔ دیگری است، یا نمایندگی غیرفعال/حذف‌شده است) هم `422` است و خطا روی `insurance_agency_code` می‌آید («نمایندگی بیمه یافت نشد»).
- شرکت با ثبت قرارداد به نمایندگی **وصل** می‌شود ([بخش ۳۴](34-insurance-agencies.md)). اگر ادمین اتصال شرکت را قطع کرده باشد، ثبت یا تغییر نمایندگی `422` با پیام «ارتباط شرکت شما با این نمایندگی بیمه قطع شده است.» می‌دهد (در `errors.error[0]`).
- **ویرایش (`PUT|PATCH`)**: ارسال `org_code` و کد اختیاری است و نفرستادنشان نمایندگی فعلی را نگه می‌دارد؛ ولی **هر دو با هم** فرستاده می‌شوند (یکی بدون دیگری `422` روی فیلد نفرستاده است). استثنا: قراردادهای **قدیمی که هنوز نمایندگی ندارند** (`insurance_agency_id: null`) باید در اولین ویرایش هر دو را بفرستند، وگرنه `422`.
  ارسال ترکیب دیگر، قرارداد را به نمایندگی (و شرکت بیمهٔ) تازه منتقل می‌کند.
- در فرم ویرایش: `org_code` را با `insurance_company.org_code` و کد را با `insurance_agency.code` پر کنید؛ اگر `insurance_agency_id` خالی بود فیلد را **الزامی** و با برچسب «برای قراردادهای قدیمی باید کد نمایندگی وارد شود» نشان دهید.

### ۱۵.۵ بیمه‌نامه: خروجی

```json
{
  "id": 8,
  "company_id": 12,
  "insurance_company_id": 3,
  "insurance_company": { "id": 3, "name": "پارسیان" },
  "title": "بیمه باربری ۱۴۰۵",
  "contract_number": "C-1405-001",
  "is_default": true,
  "status": "active",
  "start_date": "2026-03-21",
  "end_date": "2027-03-20",
  "description": null,
  "insurance_agency_id": 5,
  "insurance_agency_status": "active",
  "insurance_agency": { "id": 5, "code": "AG-1020", "city_code": 1101 },
  "representative_first_name": "علی",
  "representative_last_name": "احمدی",
  "representative_full_name": "علی احمدی",
  "representative_mobile": "09121111111",
  "representative_phone": "02122222222",
  "representative_fax": "02133333333",
  "representative_email": "ali@example.com",
  "representative_address": "تهران، ...",
  "tariffs": []
}
```

- `insurance_agency_status`: `active` = قرارداد به نمایندگی وصل است (اطلاعات نماینده و `insurance_agency` پر است)، `inactive` = ادمین اتصال را قطع کرده (`insurance_agency: null` و `representative_*` از دادهٔ خود قرارداد)، `null` = بدون نماینده. ادمین از [بخش ۳۴.۷](34-insurance-agencies.md) قرارداد را به نماینده وصل/قطع می‌کند.
- فیلدهای جدید: `insurance_agency_id` و `insurance_agency{id, code, city_code}` (برای قراردادهای قدیمی بدون نمایندگی هر دو `null`). `insurance_company` حالا `org_code` هم دارد: `{id, org_code, name}`.
- `representative_first_name` / `representative_last_name` برای نمایندگی حقوقی `null` است و `representative_full_name` نام شرکت (`company_name`) را می‌دهد.
- `insurance_company_id/insurance_company` و `representative_*` **هنوز در خروجی هستند** (از روی نمایندگی). قراردادهای قدیمی بدون نمایندگی همان مقدار ذخیره‌شدهٔ خود را نشان می‌دهند. پس صفحات نمایش/لیست قبلی بدون تغییر کار می‌کنند؛ فقط **فرم ایجاد/ویرایش** عوض می‌شود.
- `tariffs` در لیست و جزئیات پر است.

لیست (`GET /api/user/insurances`) از دستور مشترک لیست پیروی می‌کند؛ ستون «کد نمایندگی» را می‌توانید از `insurance_agency.code` بسازید.

### ۱۵.۶ تعرفه‌ها و استعلام حق بیمه (بدون تغییر)

تعرفه — `/user/insurances/{insurance}/tariffs`: `cargo_group_id` (null یعنی همهٔ گروه‌ها)، `cargo_value_from`!،
`cargo_value_to` (≥ از، null یعنی بدون سقف)، `fixed_premium` یا `premium_percentage` (۰ تا ۱۰۰؛ یکی الزامی است)،
`excess_amount`، `description`. تعرفه‌ها بازه‌های ارزش برای هر گروه کالا هستند.

استعلام: `{ insurance_id, cargos: [{ id: <کد کالا>, value }] }` حق بیمه‌ای را که تعرفه‌های منطبق برای آن کالاها تولید
می‌کنند برمی‌گرداند.

### ۱۵.۷ خطاها

| وضعیت | چه زمانی                                                   | بدنه                                                                                  |
|-------|------------------------------------------------------------|---------------------------------------------------------------------------------------|
| `422` | `org_code` یا کد نمایندگی نیست/نامعتبر (ایجاد)؛ فیلدهای دیگر نامعتبر | `errors.insurance_agency_code[0] = "نمایندگی بیمه یافت نشد"` یا خطای فیلد              |
| `422` | اتصال شرکت به نمایندگی قطع شده                              | `{ success:false, message, errors: { error: ["ارتباط شرکت شما با این نمایندگی بیمه قطع شده است."] } }` |
| `404` | استعلام: نمایندگی فعال با این کد نیست                       | `{ success:false, message, errors: { error: ["نمایندگی بیمه یافت نشد"] } }`              |
