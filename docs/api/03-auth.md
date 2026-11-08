# احراز هویت

[← فهرست بخش‌ها](README.md)

**پنل:** همه — **تعداد روت:** ۳ (به‌علاوهٔ دو روت «ورود ادمین به پنل»)

ورود، بررسی توکن و خروج. به مجوز نیاز ندارند.

### ۳.۱ روت‌ها و مجوزها

| #   | متد  | مسیر                   | نوع ورودی    | مجوز لازم              | کاربرد                               |
|-----|------|------------------------|--------------|------------------------|--------------------------------------|
| ۳.۱ | POST | `/api/auth/login`      | JSON         | بدون مجوز              | ورود با username و password          |
| ۳.۲ | GET  | `/api/auth/checkToken` | query string | بدون مجوز              | بررسی توکن و دریافت زمینهٔ احراز هویت |
| ۳.۳ | POST | `/api/auth/logout`     | ندارد        | بدون مجوز (توکن معتبر) | خروج و باطل کردن توکن جاری           |

### ۳.۲ ورودی و خروجی

با نام کاربری و رمز عبور وارد شوید، توکن سنکتوم برگشتی را ذخیره کنید و آن را به‌عنوان bearer token بفرستید. abilityهای
توکن تعیین می‌کنند نشست مربوط به ادمین سیستم است، کاربر شرکت، یا ادمینی که به‌جای شرکت وارد شده.

##### endpointها

| متد  | مسیر                                      | بدنه                     | خروجی                                                                                                                                |
|------|-------------------------------------------|--------------------------|--------------------------------------------------------------------------------------------------------------------------------------|
| POST | `/api/auth/login`                         | `{ username, password }` | زمینهٔ احراز هویت + `token`                                                                                                           |
| GET  | `/api/auth/checkToken`                    | ندارد                    | زمینهٔ احراز هویت (بدون توکن جدید)                                                                                                    |
| POST | `/api/auth/logout`                        | ندارد                    | `data: []`؛ فقط توکن جاری را باطل می‌کند                                                                                              |
| POST | `/api/admin/companies/{company}/login-as` | ندارد                    | `201` + زمینهٔ احراز هویت + `token` پشتیبانی برای آن شرکت؛ مجوز `admin.companies.loginAs`، فراخوان باید سوپرادمین یا نقش `admin` باشد |
| POST | `/api/admin/insurance-agencies/{insuranceAgency}/login-as` | ندارد    | `201` + زمینهٔ احراز هویت + `token` پشتیبانی برای پنل آن نمایندگی بیمه؛ مجوز `admin.insurance-agencies.loginAs`، فراخوان باید سوپرادمین یا نقش `admin` باشد ([بخش ۳۴](34-insurance-agencies.md)) |

##### زمینهٔ احراز هویت (`data`)

```json
{
  "token": "12|plain-text-token",
  "user": { "id": 7, "company_id": 1000, "full_name": "...", "username": "...", "status": "active", "profile_image_url": null, "...": "UserResource" },
  "role": { "id": 3, "name": "companyManager", "display_name": "..." },
  "permissions": ["user.waybills.index", "user.waybills.store"],
  "auth_mode": "company",
  "company_access": {
    "access_type": "company_user",
    "company": { "id": 1000, "name": "...", "...": "CompanyResource" },
    "issued_by": { "id": 7, "full_name": "..." },
    "permissions": [],
    "expires_at": null
  }
}
```

`token` فقط در پاسخ ورود و پاسخ توکن پشتیبانی وجود دارد. وقتی `auth_mode` برابر `system` است، `company_access` و `insurance_access` وجود
ندارند. در دو حالت بیمه (`insurance` و `insurance_support`) به‌جای `company_access` کلید **`insurance_access`** می‌آید:

```json
{
  "token": "15|plain-text-token",
  "user": { "id": 31, "company_id": null, "insurance_agency_id": 5, "...": "UserResource" },
  "role": { "id": 6, "name": "insurance", "display_name": "نمایندگی بیمه" },
  "permissions": ["insurance.companies.index", "insurance.companies.waybills.index", "insurance.companies.waybills.show"],
  "auth_mode": "insurance",
  "insurance_access": {
    "access_type": "insurance_user",
    "level": "agency",
    "level_label": "نمایندگی",
    "insurance_company": { "id": 3, "name": "پارسیان" },
    "agency": { "id": 5, "code": "AG-1020", "full_name": "علی احمدی", "city_code": 1101, "level": "agency", "status": "active", "...": "InsuranceAgencyResource" },
    "city": { "code": 1101, "name": "تهران" },
    "state": { "id": 1, "code": 11, "name": "تهران" },
    "issued_by": { "id": 31, "full_name": "..." },
    "permissions": ["insurance.companies.index", "insurance.companies.waybills.index", "insurance.companies.waybills.show"],
    "expires_at": null
  }
}
```

- `access_type`: `insurance_user` (ورود خود کاربر بیمه) یا `support` (ادمین داخل پنل یک نمایندگی).
- `level`: `country` / `province` / `agency` و `level_label` برچسب فارسی. `level` سطحِ نمایندگیِ کاربر است. `agency` نمایندگی‌ای است که کاربر به آن وصل است (در حالت `support`، نمایندگی توکن)؛ اگر کاربر به نمایندگی وصل نباشد `null` است.
- `state`: استانِ نماینده استانی/نمایندگی (برای نماینده کشوری از شهر دفترش، وگرنه `null`)؛ `city`: شهر دفتر نماینده یا `null` وقتی هنوز پر نشده؛ `insurance_company`: شرکت بیمهٔ نماینده. کاربرِ نماینده کشوری/استانیِ تازه‌ساخته (خالی) هم وارد می‌شود؛ برای نام نماینده `agency.title` را نشان دهید.
- `issued_by`: صاحب توکن (کاربر بیمه یا ادمینِ واردشده). `expires_at` برای ورود عادی `null` و برای حالت `support` یک روز بعد است.
- در حالت `support` فیلدهای `role` (نقش `insurance`) و `permissions` (فقط مجوزهای `insurance.*`) جایگزین نقش و مجوزهای خود ادمین می‌شوند، مثل ورود پشتیبان به شرکت.

##### حالت‌های نشست

| `auth_mode`       | چه کسی                            | abilityهای توکن                   | انقضا | مسیرهای مجاز                                                         |
|-------------------|-----------------------------------|-----------------------------------|-------|----------------------------------------------------------------------|
| `system`          | کاربر بدون `company_id` و بدون نقش بیمه (ادمین‌ها) | `*`                               | ندارد | `/api/admin/*`؛ `/api/user/*` به دلیل نبودِ زمینهٔ شرکت 403 برمی‌گرداند |
| `company`         | کاربر عضو یک شرکت                 | `company-user`، `company:{id}`    | ندارد | فقط `/api/user/*`؛ مسیرهای ادمین 403                                 |
| `company_support` | ادمینی که توکن پشتیبانی صادر کرده | `company-support`، `company:{id}` | ۱ روز | `/api/user/*` همان شرکت، محدود به مجوزهای فعالِ نقش `companyManager`  |
| `insurance`       | کاربر نقش `insurance` (بدون شرکت) | `insurance-user`                  | ندارد | `/api/insurance/*` (مجوزهای نقش `insurance`)؛ `/api/admin/*` و `/api/user/*` ۴۰۳ |
| `insurance_support` | ادمینی که وارد پنل یک نمایندگی بیمه شده | `insurance-support`، `insurance-agency:{id}` | ۱ روز | فقط `/api/insurance/*` با محدودهٔ همان نمایندگی (با سطح خودش)؛ هر مسیر دیگر ۴۰۳ |

##### جریان در فرانت‌اند

1. `POST /auth/login`؛ مقادیر `token`، `auth_mode`، `permissions` و `company_access` را ذخیره کنید.
2. تشخیص پنل با `auth_mode`: `system` → پنل ادمین؛ `company` و `company_support` → پنل شرکت؛ **`insurance` و `insurance_support` → پنل بیمه** ([بخش ۳۵](35-insurance-panel.md)).
   در حالت `insurance_support` نوار «شما به‌عنوان ادمین در پنل نمایندگی … هستید» با شمارش معکوس از `insurance_access.expires_at` نشان دهید (مثل پشتیبانی شرکت).
3. هنگام شروع اپ، `GET /auth/checkToken` را برای بازیابی نشست صدا بزنید؛ 401 یعنی خروج محلی.
4. برای «ورود به پنل نمایندگی بیمه به‌عنوان ادمین»، `POST /api/admin/insurance-agencies/{id}/login-as` را با توکن ادمین صدا بزنید و پاسخ را مثل ورود عادی ذخیره کنید؛ توکن ادمین را هم نگه دارید و برای بازگشت توکن پشتیبانی را دور بریزید. (توکن پشتیبانی نمایندگی در مسیرهای ادمین رد می‌شود.)
5. برای «ورود به شرکت به‌عنوان پشتیبان»، endpoint توکن پشتیبانی را با توکن ادمین صدا بزنید. توکن ادمین را نگه دارید:
   توکن پشتیبانی در مسیرهای ادمین رد می‌شود. برای بازگشت، توکن پشتیبانی را دور بریزید.
6. برای نشست پشتیبانی، شمارش معکوس از `company_access.expires_at` (یا `insurance_access.expires_at`) نمایش دهید؛ پس از آن همهٔ درخواست‌ها 401 برمی‌گردانند.
7. `POST /auth/logout` فقط همان توکنی را باطل می‌کند که با آن صدا زده شده.

خطاهای ورود با کد 400 و پیام فارسی برمی‌گردند: کاربر پیدا نشد، رمز اشتباه، کاربر غیرفعال (403)، کاربر بدون نقش (403) یا
شرکت غیرفعال (403).
