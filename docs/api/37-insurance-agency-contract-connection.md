# ۳۷. اتصال نمایندهٔ بیمه به قرارداد بیمهٔ شرکت (تغییرات برای فرانت)

> این فایل فقط **تغییرات این بخش** را برای فرانت جمع کرده است. جزئیات کامل هر فیلد در فایل‌های مرجع (انتهای همین فایل) است.

## ۳۷.۱ چه چیزی عوض شد

- **قبلاً:** ادمین یک *شرکت* را به نماینده وصل می‌کرد؛ این فقط یک رکورد بین شرکت و نماینده بود و هیچ قراردادی به نماینده وصل نمی‌شد.
- **الان:** نماینده به **یک قرارداد بیمهٔ مشخصِ یک شرکت** وصل می‌شود. وضعیت اتصال (`فعال` / `قطع‌شده`) روی **خود قرارداد** است.
- یک شرکت می‌تواند چند قرارداد داشته باشد؛ قطع‌کردن یک قرارداد روی قراردادهای دیگر همان شرکت اثری ندارد.
- یک قرارداد فقط به **یک** نماینده وصل است و فقط نمایندهٔ **همان شرکت بیمه** و فقط از **سطح «نمایندگی»** (نه کشوری/استانی) را می‌پذیرد.

## ۳۷.۲ روت‌های حذف‌شده (باید از UI حذف یا جایگزین شوند)

| روت قدیمی | جایگزین |
|---|---|
| `GET /api/admin/insurance-agencies/{id}/companies` (با خروجی قدیمی `status`) | همین مسیر، با **خروجی جدید** (۳۷.۳)؛ لیست اتصال‌ها حالا `GET .../insurances` است |
| `POST /api/admin/insurance-agencies/{id}/companies` با `{company_id}` | `POST .../insurances` با `{insurance_id}` |
| `DELETE /api/admin/insurance-agencies/{id}/companies/{company}` | `DELETE .../insurances/{insurance}` |

نام permissionها هم عوض شد: `admin.insurance-agencies.companies.store` و `admin.insurance-agencies.companies.destroy` دیگر وجود ندارند.

## ۳۷.۳ روت‌های ادمین (همه زیر `/api/admin/insurance-agencies/{id}`)

| # | متد | مسیر | permission | کاربرد |
|---|---|---|---|---|
| ۱ | GET | `/companies` | `admin.insurance-agencies.companies.index` | شرکت‌هایی که قرارداد متصل به این نماینده دارند |
| ۲ | GET | `/companies/unconnected?search=` | `admin.insurance-agencies.companies.unconnected` | شرکت‌های هنوز متصل‌نشده (همهٔ شرکت‌ها؛ فیلتر شرکت بیمه در مرحلهٔ قراردادها است) |
| ۳ | GET | `/companies/{company}/insurances` | `admin.insurance-agencies.companies.insurances.index` | قراردادهای یک شرکت که می‌شود وصل کرد |
| ۴ | GET | `/insurances` | `admin.insurance-agencies.insurances.index` | قراردادهای متصل به نماینده |
| ۵ | POST | `/insurances` | `admin.insurance-agencies.insurances.store` | اتصال / اتصال مجدد یک قرارداد |
| ۶ | DELETE | `/insurances/{insurance}` | `admin.insurance-agencies.insurances.destroy` | قطع اتصال یک قرارداد |

### خروجی ۱ — `GET /companies`
```json
{ "success": true, "data": [
  { "company_id": 12, "name": "شرکت نمونه", "organization_code": "2488955", "insurances_count": 2, "active_insurances_count": 1 }
] }
```

### خروجی ۲ — `GET /companies/unconnected`
```json
{ "success": true, "data": [ { "company_id": 15, "name": "شرکت آزاد", "organization_code": "1234567" } ] }
```
`search` روی نام یا کد سازمانی. بدون صفحه‌بندی.

### خروجی ۳ و ۴ و ۵ و ۶ (ساختار یکسان قرارداد)
```json
{ "success": true, "message": "...", "data": [ {
  "insurance_id": 31, "title": "بیمه باربری", "contract_number": "C-1", "status": "active",
  "start_date": "2026-01-01", "end_date": "2026-12-31",
  "company_id": 12, "company_name": "شرکت نمونه", "organization_code": "2488955",
  "connection": "active", "connected_insurance_agency_id": 5
} ] }
```
- `connection`: `active` وصل به همین نماینده · `inactive` اتصال را ادمین قطع کرده · `null` به این نماینده وصل نیست.
- `connected_insurance_agency_id`: نمایندهٔ فعلی قرارداد (`null` = بدون نماینده). اگر مقدارش غیر از نمایندهٔ جاری بود، قرارداد را در لیست انتخاب **غیرفعال** نشان دهید.
- `POST` و `DELETE` بعد از موفقیت، **لیست به‌روز قراردادهای متصل** (همان ساختار ردیف ۴) را برمی‌گردانند.

### بدنهٔ ۵
```json
{ "insurance_id": 31 }
```

### خطاها
| وضعیت | شرط |
|---|---|
| `422` روی `insurance_id` | قرارداد وجود ندارد |
| `422` (`errors.error[0]`) | شرکت بیمهٔ قرارداد با نماینده یکی نیست |
| `422` (`errors.error[0]`) | قرارداد به نمایندهٔ دیگری وصل است |
| `422` (`errors.error[0]`) | نماینده سطح «نمایندگی» نیست |
| `404` | در `DELETE`، قرارداد مال این نماینده نیست |

## ۳۷.۴ روند پیشنهادی فرم ادمین

1. صفحهٔ نماینده → تب «شرکت‌ها/قراردادها»: جدول `GET /insurances` (شرکت، قرارداد، وضعیت اتصال، دکمهٔ **قطع** وقتی `active` / **وصل مجدد** وقتی `inactive`).
2. دکمهٔ «افزودن»:
   1. لیست `GET /companies/unconnected` را (با جست‌وجو) نشان دهید؛ شرکت را انتخاب کند.
   2. با `GET /companies/{company}/insurances` قراردادهای آن شرکت را بیاورید. اگر خالی بود: «این شرکت قرارداد بیمه‌ای با این شرکت بیمه ندارد».
   3. قرارداد را انتخاب و `POST /insurances` بزنید. قراردادهای با `connected_insurance_agency_id` غیر از این نماینده قابل انتخاب نباشند.
3. برای شرکتی که از قبل متصل است و قرارداد دیگری هم دارد: از جدول مرحلهٔ ۱ مستقیم به `GET /companies/{company}/insurances` بروید.
4. وصل مجدد = همان `POST` با همان `insurance_id`.

## ۳۷.۵ پنل شرکت: جزئیات قرارداد (`GET /api/user/insurances/{id}` و لیست)

فیلد جدید: **`insurance_agency_status`**

| مقدار | معنی | `insurance_agency` | `representative_*` |
|---|---|---|---|
| `active` | قرارداد به نماینده وصل است | `{id, code, city_code}` | از اطلاعات نماینده |
| `inactive` | ادمین اتصال را قطع کرده | `null` | از دادهٔ خود قرارداد |
| `null` | قرارداد نماینده ندارد | `null` | از دادهٔ خود قرارداد |

- در جزئیات قرارداد، وقتی `active` است **اطلاعات نماینده** (نام، موبایل، تلفن، فکس، ایمیل، آدرس) را نشان دهید.
- وقتی `inactive` است یک نشان «اتصال به نماینده قطع شده» نشان دهید.
- ثبت/ویرایش قرارداد با `insurance_company_org_code` + `insurance_agency_code` همان قبل است و قرارداد را همان لحظه `active` وصل می‌کند.
- ویرایش نمایندهٔ قرارداد قطع‌شده `422` می‌دهد: «ارتباط این قرارداد با نمایندگی بیمه توسط ادمین قطع شده است.» (`errors.error[0]`) — پیام قبلی («ارتباط شرکت شما…») عوض شده.

## ۳۷.۶ پنل بیمه

بارنامه‌های قرارداد **قطع‌شده** از پنل بیمهٔ آن نماینده (و استانی/کشوری بالادستش) دیده نمی‌شوند. قبلاً این رفتار روی کل شرکت بود؛ الان فقط روی همان قرارداد است. تغییری در روت‌های پنل بیمه نیست.

## ۳۷.۷ چک‌لیست فرانت

- [ ] مسیرهای قدیمی `POST/DELETE .../companies` را حذف و با `POST/DELETE .../insurances` جایگزین کنید.
- [ ] خروجی `GET .../companies` عوض شده (دیگر `status` ندارد؛ `insurances_count` و `active_insurances_count` دارد).
- [ ] فرم افزودن: شرکت‌های متصل‌نشده ← قراردادهای شرکت ← اتصال.
- [ ] جدول قراردادهای متصل با دکمهٔ قطع/وصل مجدد.
- [ ] پنل شرکت: نمایش اطلاعات نماینده با `insurance_agency_status` و نشان «قطع شده».
- [ ] پیام خطای جدید ویرایش قرارداد قطع‌شده.
- [ ] permissionهای جدید را در لیست نقش‌ها/دسترسی‌ها ببینید (روی سرور `generate-permissions` اجرا می‌شود).

## ۳۷.۸ فایل‌های مرجع (این‌ها را به فرانت بدهید)

| اولویت | فایل | چرا |
|---|---|---|
| ۱ | [37-insurance-agency-contract-connection.md](37-insurance-agency-contract-connection.md) | همین فایل — خلاصهٔ تغییرات |
| ۲ | [34-insurance-agencies.md](34-insurance-agencies.md) (بخش ۳۴.۶ و ۳۴.۷) | مرجع کامل روت‌های ادمین نماینده‌ها |
| ۳ | [15-insurances.md](15-insurances.md) | فیلد `insurance_agency_status` و نمایش نماینده در قرارداد (پنل شرکت) |
| ۴ | [36-insurance-frontend-handoff.md](36-insurance-frontend-handoff.md) | جدول روت‌ها و چک‌لیست کلی نمایندگی بیمه |
| ۵ | [02-permissions.md](02-permissions.md) | نام permissionهای جدید |
