# راهنمای تغییرات نمایندگی بیمه برای فرانت‌اند

[← فهرست بخش‌ها](README.md)

این فایل نقطهٔ شروع است. خلاصهٔ **همهٔ تغییرات** قابلیت «نمایندگی بیمه» را برای سه پنل می‌دهد و برای هر تغییر می‌گوید جزئیات کدام فایل است.
اگر با ابزار هوش مصنوعی کد را عوض می‌کنید، این فایل را اول بدهید و بعد فایل‌های «ضروری» پایین را.

## ۰. فایل‌هایی که باید به فرانت داده شود

**ضروری (تغییر کرده یا جدید است):**

| فایل                                                       | چه چیزی دارد                                                                 |
|------------------------------------------------------------|------------------------------------------------------------------------------|
| `36-insurance-frontend-handoff.md` (همین فایل)             | خلاصه، چک‌لیست کار هر پنل، تغییرات ناسازگار                                    |
| `34-insurance-agencies.md`                                 | **جدید** — پنل ادمین: CRUD نمایندگی، اتصال شرکت‌ها، لیست شرکت‌های بیمه         |
| `35-insurance-panel.md`                                    | **جدید** — پنل بیمه: ورود، سطح‌ها، روت‌ها و خروجی‌ها                            |
| `15-insurances.md`                                         | **تغییر کرده** — پنل شرکت: فرم قرارداد بیمه با کد نمایندگی + استعلام            |
| `21-admin-users.md`                                        | **تغییر کرده** — پنل ادمین: فرم کاربر برای نقش بیمه                            |
| `03-auth.md`                                               | **تغییر کرده** — حالت‌های نشست جدید `insurance` و `insurance_support`، کلید `insurance_access`، روت ورود ادمین به پنل نمایندگی |
| `26-enums.md`                                              | **تغییر کرده** — نقش `insurance` و enum `InsuranceLevel`                       |
| `02-permissions.md`                                        | **تغییر کرده** — مجوزهای جدید                                                 |

**مرجع (برای فهم خروجی‌ها و قراردادهای مشترک؛ تغییر محتوایی کوچک یا بدون تغییر):**
`01-general-conventions.md` (قالب پاسخ/لیست‌ها؛ ردیف `/api/insurance` اضافه شد)، `04-profile.md` و `09-general-lookups.md` (یادداشت کوتاه)،
`19-waybills.md` (شکل خروجی بارنامه که پنل بیمه عیناً استفاده می‌کند)، `20-admin-companies.md` و `23-admin-cities.md` (لیست شرکت‌ها و شهرها برای فرم‌های ادمین)، `README.md` (فهرست).

## ۱. خلاصهٔ قابلیت

1. نماینده‌های هر شرکت بیمه یک **درخت** هستند: نماینده کشوری → نماینده استانی (یکی برای هر استان) → نمایندگی‌ها. کشوری و استانی‌ها را دستور `php artisan generate-insurance-agencies` خالی می‌سازد و ادمین پرشان می‌کند؛ ادمین **نمایندگی‌ها** را زیر یک نماینده استانی می‌سازد (حقیقی یا حقوقی، کد یکتا در همان شرکت بیمه، تماس، شهر، وضعیت) و می‌تواند **قراردادِ بیمهٔ** یک شرکت را به هر نمایندگی وصل/قطع کند.
2. شرکت (پنل شرکت) در قرارداد بیمه به‌جای وارد کردن شرکت بیمه و اطلاعات نماینده، فقط **`org_code` شرکت بیمه‌کننده و کد نمایندگی** را می‌نویسد و با **استعلام** اطلاعات را می‌بیند.
3. نقش جدید **`insurance`** با پنل جدا (`/api/insurance`): کاربر شرکت‌هایی را که با نمایندگی‌هایش قرارداد دارند و بارنامه‌های بیمه‌شدهٔ آن‌ها را می‌بیند؛ محدوده با سطح کشوری/استانی/نمایندگی.
4. **ورود (login):** برای کاربر نقش `insurance`، سرور یک توکن مخصوص پنل بیمه می‌سازد و `auth_mode: "insurance"` به‌همراه `insurance_access` (سطح، شرکت بیمه، نمایندگی، شهر و استان) برمی‌گرداند.
5. **ورود ادمین به پنل نمایندگی** (مثل ورود به شرکت): `POST /api/admin/insurance-agencies/{id}/login-as` توکن موقت ۱ روزه با `auth_mode: "insurance_support"` می‌دهد.

## ۲. تغییرات ناسازگار (Breaking) — حتماً بررسی شود

| # | تغییر                                                                                                                                              | اقدام لازم                                                                                 |
|---|----------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------|
| ۱ | فرم قرارداد بیمه (`POST/PUT /api/user/insurances`): فیلد `insurance_company_id` و همهٔ `representative_*` **ورودی نیستند** (نادیده گرفته می‌شوند) و **`insurance_company_org_code` (کد org شرکت بیمه‌کننده) + `insurance_agency_code`** **الزامی** (در ثبت) هستند؛ کد نمایندگی فقط در همان شرکت بیمه معنا دارد | فرم را عوض کنید ([بخش ۱۵](15-insurances.md))                                                 |
| ۲ | قراردادهای قدیمی بدون نمایندگی (`insurance_agency_id = null`) در **اولین ویرایش** باید هر دو (`insurance_company_org_code` و `insurance_agency_code`) را بفرستند | در فرم ویرایش، فیلد را در این حالت الزامی کنید                                                |
| ۳ | دو مقدار جدید برای `auth_mode`: **`insurance`** (ورود کاربر بیمه) و **`insurance_support`** (ادمین داخل پنل نمایندگی)؛ پاسخ ورود/`checkToken` کلید `insurance_access` دارد. کاربر بیمه دیگر `system` نیست | switch روی `auth_mode` را گسترش دهید: `insurance`/`insurance_support` → پنل بیمه ([بخش ۳](03-auth.md)). کدی که هر چیزِ غیر `company*` را ادمین فرض می‌کند باید اصلاح شود |
| ۴ | `UserResource` فیلد جدید `insurance_agency_id` دارد (برای غیر بیمه `null`)؛ شرکت بیمه، شهر و سطح روی کاربر نیستند و از نمایندگی می‌آیند            | فقط افزوده است؛ تایپ‌ها را به‌روز کنید                                                       |
| ۵ | خروجی قرارداد بیمه: `insurance_agency_id` و `insurance_agency{id,code,city_code}` اضافه شد؛ بقیهٔ فیلدها (از جمله `representative_*`) سر جایشان‌اند | فقط افزوده است                                                                              |
| ۶ | نماینده‌ها درختی‌اند (`parent_id`، `level`، `state_code`). ایجاد (`POST /api/admin/insurance-agencies`) فقط سطح `agency` می‌سازد و ورودی‌اش **`parent_id`** (نماینده استانی) است؛ `level`، `insurance_company_id` و `insurance_company_org_code` دیگر ورودی نیستند و شرکت بیمه و استان از نماینده استانی می‌آیند. شهر باید در همان استان باشد. نماینده کشوری/استانی حذف نمی‌شود (`422`) و فیلدهایش (`code`، نام، شهر) ممکن است `null` باشد. خروجی فیلدهای جدید `parent_id`، `title`، `state_code`، `state` دارد و لیست با `tree=1` درختی برمی‌گردد. کاربر بیمه فقط `insurance_agency_id` دارد و می‌تواند به هر سطحی وصل شود | صفحهٔ نماینده‌ها را درختی کنید، فرم نمایندگی را با `parent_id` (به‌جای شرکت بیمه و سطح) بسازید، و انتخاب نمایندهٔ فرم کاربر را tree-select کنید ([بخش ۳۴](34-insurance-agencies.md)) |
| ۷ | **نماینده بیمه حقیقی/حقوقی** (`type`): حقوقی ← `company_name` + `company_national_id`، حقیقی ← `first_name` + `last_name` + `national_code`؛ نماینده کشوری همیشه حقوقی. `code` فقط در همان شرکت بیمه یکتاست (`org_code` + `code` نماینده را مشخص می‌کند). فیلد `description` اضافه شد. استعلام نمایندگی (`agency-inquiry`) حالا `insurance_company_org_code` را هم **الزامی** می‌گیرد (در کنار `code`). `insurance_company` در خروجی قرارداد `org_code` دارد. نماینده‌های تولیدشده نام دارند: «شرکت {بیمه}» و «سرپرستی استان {استان}» | فرم نمایندگی را بر اساس `type` دو حالته کنید ([بخش ۳۴](34-insurance-agencies.md))؛ فرم قرارداد و استعلام شرکت را با فیلد `org_code` کامل کنید ([بخش ۱۵](15-insurances.md)) |

> شهر نماینده: ورودی همچنان `city_code` (کد شهر) است و سرور آن را به `city_id` تبدیل و لینک می‌کند؛ خروجی `city_id`، `city_code` و `city{id,code,name}` دارد و فیلتر `eq-city_code` به `eq-city_id` / `city__code` تغییر کرد.

هیچ روت قدیمی حذف نشده است.

## ۳. چک‌لیست کار هر پنل

### ۳.۱ پنل ادمین (جزئیات: [۳۴](34-insurance-agencies.md)، [۲۱](21-admin-users.md))

- [ ] منوی «نمایندگی‌های بیمه» (مجوز `admin.insurance-agencies.index`). بک‌اند: بعد از دیپلوی (و هر بار شرکت بیمه یا استان جدید اضافه شد) `php artisan generate-insurance-agencies` اجرا شود.
- [ ] دکمهٔ «ورود به پنل نمایندگی» (مجوز `admin.insurance-agencies.loginAs`): `POST /api/admin/insurance-agencies/{id}/login-as` → توکن و زمینه را مثل ورود به شرکت ذخیره و به پنل بیمه هدایت کنید؛ توکن ادمین را نگه دارید تا «بازگشت به پنل ادمین» ممکن باشد؛ نوار «ورود پشتیبان» با شمارش معکوس `insurance_access.expires_at`.
- [ ] صفحهٔ نماینده‌ها **درختی** (`GET /api/admin/insurance-agencies?tree=1`، شناسهٔ واقعی = `id2`، متن = `label`) با فیلتر شرکت بیمه (`eq-insurance_company_id`) و جست‌وجو؛ روی هر گره: ویرایش، فعال/غیرفعال (`PATCH {status}`)، «شرکت‌ها»، «ورود به پنل»؛ روی گرهٔ استانی: «افزودن نمایندگی»؛ حذف (نرم، با تأیید) فقط روی گرهٔ نمایندگی.
- [ ] فرم ایجاد نمایندگی: `parent_id`! (نماینده استانی؛ از گرهٔ درخت)، `type`! (حقیقی/حقوقی؛ حقوقی ← `company_name`! + `company_national_id`!، حقیقی ← `first_name`! + `last_name`! + `national_code`!)، `city_code`! (شهرهای `GET /api/admin/cities?eq-state_id=<state.id نماینده استانی>`، مقدار = `code` شهر)، `code`! (یکتا در همان شرکت بیمه)، `mobile`، `phone`، `fax`، `email`، `address`، `description`، `status`. ویرایش نمایندگی: همین فیلدها (انتقال به استان دیگر با `parent_id` + `city_code` جدید). ویرایش کشوری/استانی: همه اختیاری و `null`‌پذیر، بدون `parent_id`.
- [ ] صفحهٔ قراردادهای یک نمایندگی: لیست (شرکت، قرارداد، `connection`)، افزودن (انتخاب شرکت ← `GET .../companies/{company}/insurances` ← `POST {insurance_id}`)، قطع اتصال (`DELETE .../insurances/{insurance}`)، وصل مجدد (همان `POST`).
- [ ] فرم کاربر (`POST/PUT /api/admin/users`): وقتی نقش `insurance` انتخاب شد، فیلد شرکت را پنهان کنید و فقط `insurance_agency_id` را الزامی نشان دهید: **tree-select** مثل انتخاب کاربر/شرکت والد از `GET /api/admin/insurance-agencies?tree=1`، هر گره (کشوری/استانی/نمایندگی) قابل انتخاب، مقدار = `id2`؛ شرکت بیمه، شهر و سطح را نفرستید (از نمایندگی می‌آیند)؛ برای نقش‌های دیگر `insurance_agency_id` فرستاده نشود.
- [ ] نقش `insurance` در dropdown نقش‌ها؛ گروه مجوزهای جدید در صفحهٔ مجوزها خودکار می‌آید.

### ۳.۲ پنل شرکت (جزئیات: [۱۵](15-insurances.md))

- [ ] در فرم ایجاد/ویرایش بیمه‌نامه: فیلدهای شرکت بیمه و اطلاعات نماینده را بردارید؛ دو فیلد «کد شرکت بیمه‌کننده» (`insurance_company_org_code`، می‌تواند dropdown شرکت‌های بیمه با مقدار `org_code` باشد) و «کد نمایندگی» (`insurance_agency_code`) + دکمهٔ «استعلام».
- [ ] استعلام: `GET /api/user/insurances/agency-inquiry?insurance_company_org_code=...&code=...`؛ موفق → نام (`name`؛ برای حقوقی نام شرکت)، موبایل، تلفن، فکس، ایمیل، آدرس و شرکت بیمه را (فقط‌خواندنی) نشان دهید؛ `404` → زیر فیلد «نمایندگی بیمه یافت نشد»، و ثبت را غیرفعال کنید.
- [ ] هنگام ثبت فقط `insurance_company_org_code` و `insurance_agency_code` بفرستید؛ خطای `422` روی `insurance_agency_code` را زیر فیلد و خطای `errors.error[0]` («ارتباط این قرارداد با نمایندگی بیمه توسط ادمین قطع شده است.») را به‌صورت پیام نشان دهید.
- [ ] ویرایش: `org_code` را با `insurance_company.org_code` و کد را با `insurance_agency.code` پر کنید؛ اگر `insurance_agency_id` خالی بود الزامی کنید.
- [ ] نمایش لیست/جزئیات قرارداد: همان فیلدهای قبلی (`representative_*`, `insurance_company`) کار می‌کنند؛ در صورت نیاز ستون «کد نمایندگی» از `insurance_agency.code`.

### ۳.۳ پنل بیمه — **پنل جدید** (جزئیات: [۳۵](35-insurance-panel.md))

- [ ] بعد از ورود: اگر `auth_mode` برابر `insurance` یا `insurance_support` بود به این پنل هدایت شود. صفحهٔ پروفایل نسازید (روت‌های `/api/user/*` برای او `403` است).
- [ ] از `insurance_access` در هدر استفاده کنید: `level_label` (کشوری/استانی/نمایندگی)، `insurance_company.name`، `agency.title` (نام نماینده؛ برای نماینده خالی هم پر است)، `state.name` (سطح استانی). `city`/`state`/`agency.code` ممکن است `null` باشند. در حالت `insurance_support` نوار هشدار ادمین + شمارش معکوس `expires_at`.
- [ ] `checkToken` در شروع اپ همین ساختار را برمی‌گرداند؛ `401` یعنی خروج.
- [ ] صفحهٔ شرکت‌ها: `GET /api/insurance/companies` (جست‌وجو، صفحه‌بندی) با ستون تعداد قرارداد.
- [ ] صفحهٔ بارنامه‌های شرکت: `GET /api/insurance/companies/{company}/waybills` با همان جدول/فیلترهای بارنامهٔ پنل شرکت، **فقط‌خواندنی**.
- [ ] جزئیات بارنامه: `GET /api/insurance/companies/{company}/waybills/{waybill}`.
- [ ] نمایش سطح کاربر (`insurance_access.level_label`) در هدر؛ مدیریت `403/404` با پیام سرور.
- [ ] توجه: پاسخ بارنامه همهٔ ستون‌ها (از جمله مبالغ) را دارد؛ اگر نمی‌خواهید بیمه‌گر ببیند، فعلاً در UI پنهان کنید.

## ۴. جدول سریع روت‌ها

| پنل   | متد            | مسیر                                                          | مجوز                                         |
|-------|----------------|---------------------------------------------------------------|----------------------------------------------|
| ادمین | GET            | `/api/admin/insurance-companies`                              | `admin.insurance-companies.index`            |
| ادمین | GET            | `/api/admin/insurance-agencies`                               | `admin.insurance-agencies.index`             |
| ادمین | POST           | `/api/admin/insurance-agencies`                               | `admin.insurance-agencies.store`             |
| ادمین | GET            | `/api/admin/insurance-agencies/{id}`                          | `admin.insurance-agencies.show`              |
| ادمین | PUT\|PATCH     | `/api/admin/insurance-agencies/{id}`                          | `admin.insurance-agencies.update`            |
| ادمین | DELETE         | `/api/admin/insurance-agencies/{id}`                          | `admin.insurance-agencies.destroy`           |
| ادمین | POST           | `/api/admin/insurance-agencies/{id}/login-as`                 | `admin.insurance-agencies.loginAs`           |
| ادمین | GET            | `/api/admin/insurance-agencies/{id}/companies`                | `admin.insurance-agencies.companies.index`   |
| ادمین | GET            | `/api/admin/insurance-agencies/{id}/companies/unconnected`    | `admin.insurance-agencies.companies.unconnected` |
| ادمین | GET            | `/api/admin/insurance-agencies/{id}/insurances`               | `admin.insurance-agencies.insurances.index`  |
| ادمین | GET            | `/api/admin/insurance-agencies/{id}/companies/{company}/insurances` | `admin.insurance-agencies.companies.insurances.index` |
| ادمین | POST           | `/api/admin/insurance-agencies/{id}/insurances`               | `admin.insurance-agencies.insurances.store`  |
| ادمین | DELETE         | `/api/admin/insurance-agencies/{id}/insurances/{insurance}`   | `admin.insurance-agencies.insurances.destroy`|
| شرکت  | GET\|POST      | `/api/user/insurances/agency-inquiry`                         | `user.insurances.agency-inquiry`             |
| بیمه  | GET            | `/api/insurance/companies`                                    | `insurance.companies.index`                  |
| بیمه  | GET            | `/api/insurance/companies/{company}/waybills`                 | `insurance.companies.waybills.index`         |
| بیمه  | GET            | `/api/insurance/companies/{company}/waybills/{waybill}`       | `insurance.companies.waybills.show`          |

روت‌های تغییرکرده در خروجی: `POST /api/auth/login`، `GET /api/auth/checkToken` (مقادیر جدید `auth_mode` و کلید `insurance_access`). روت‌های تغییرکرده (همان مسیر، ورودی جدید): `POST|PUT|PATCH /api/user/insurances[/{id}]`، `POST|PUT|PATCH /api/admin/users[/{id}]`، `POST|PUT|PATCH /api/admin/insurance-agencies[/{id}]` (`parent_id` به‌جای شرکت بیمه و سطح، به‌علاوهٔ `type` و فیلدهای هویتی)، `GET|POST /api/user/insurances/agency-inquiry` (`insurance_company_org_code` الزامی). `GET /api/admin/insurance-agencies` پارامتر جدید `tree=1` دارد.

## ۵. پیام‌ها و خطاهای ثابت

| پیام                                                  | کجا                                        | وضعیت |
|-------------------------------------------------------|--------------------------------------------|-------|
| نمایندگی بیمه یافت نشد                                | استعلام (`errors.error[0]`)، ثبت قرارداد (`errors.insurance_agency_code[0]`) | `404` / `422` |
| ارتباط این قرارداد با نمایندگی بیمه توسط ادمین قطع شده است. | ثبت/ویرایش قرارداد با نمایندگی قطع‌شده (`errors.error[0]`)  | `422` |
| نمایندگی بیمه یافت نشد (ترکیب `org_code` + کد اشتباه یا مال شرکت بیمهٔ دیگر) | ثبت/ویرایش قرارداد (`errors.insurance_agency_code[0]`) | `422` |
| نمایندگی فقط زیر یک نماینده استانی تعریف می‌شود.       | ایجاد/انتقال نمایندگی (`errors.parent_id[0]`) | `422` |
| شهر نمایندگی باید در استانِ نماینده استانی آن باشد.     | ایجاد/ویرایش نماینده (`errors.city_code[0]`) | `422` |
| نماینده کشوری و استانی حذف نمی‌شود؛ فقط نمایندگی‌ها قابل حذف هستند. | حذف نماینده کشوری/استانی (`errors.error[0]`) | `422` |
| خطای اعتبارسنجی فیلد                                  | `errors.<field>[0]` (همهٔ فرم‌ها)           | `422` |

## ۶. سناریوی آزمون دستی (پیشنهادی)

0. سرور: `php artisan generate-insurance-agencies`؛ در درخت ادمین زیر «پارسیان» نماینده کشوری و یک نماینده برای هر استان دیده شود.
1. ادمین: زیر «سرپرستی استان تهران» پارسیان یک نمایندگی (حقوقی با نام شرکت و کد شناسه) با کد `AG-1` در شهر تهران بسازد (شهر شیراز باید `422` بدهد)؛ یک نمایندگی حقیقی با کد `AG-1` زیر «سرپرستی استان تهران» شرکت بیمهٔ دیگر هم ساخته شود (مجاز است) ولی دومی با همان کد برای پارسیان `422` بدهد.
2. شرکت: در فرم بیمه‌نامه `org_code` پارسیان و کد `AG-1` را استعلام و قرارداد را ثبت کند (با `org_code` شرکت بیمهٔ دیگر، نمایندگیِ همان شرکت پیدا شود)؛ در ادمین > شرکت‌های نمایندگی، شرکت با وضعیت `active` دیده شود.
3. ادمین: کاربر نقش `insurance` فقط با `insurance_agency_id` همین نمایندگی بسازد (و یک کاربر هم برای «نماینده استان تهران»: باید بارنامه‌های همهٔ نمایندگی‌های زیرش را ببیند)؛ با آن ورود کند: `auth_mode` باید `insurance` باشد و `insurance_access` سطح و نمایندگی را نشان دهد؛ به پنل بیمه برود.
4. شرکت بارنامه‌ای بسازد که «بیمهٔ مسئولیت» (`liability_insurance`) آن همین قرارداد است؛ در پنل بیمه شرکت و بارنامه دیده شود.
5. ادمین اتصال یک قرارداد را قطع کند → بارنامه‌های آن قرارداد از پنل بیمه ناپدید شوند و شرکت نتواند نمایندهٔ آن قرارداد را عوض کند.
6. ادمین در صفحهٔ نمایندگی «ورود به پنل نمایندگی» را بزند: `auth_mode` برابر `insurance_support` باشد، فقط شرکت‌های همان نمایندگی دیده شوند، و با همان توکن صدا زدن هر روت `/api/admin/*` یا `/api/user/*` خطای `403` بدهد.

## ۷. نکته‌های شناخته‌شدهٔ سمت سرور (برای اطلاع فرانت)

- کد نمایندگی حتی بعد از حذف نرم دوباره قابل استفاده نیست؛ endpoint بازیابی یا لیست حذف‌شده‌ها وجود ندارد.
- پنل بیمه محدودیت ستون ندارد (همهٔ ستون‌های بارنامه برمی‌گردد).
- کاربر بیمه پروفایل شخصی ندارد؛ ویرایش اطلاعاتش با ادمین است.
