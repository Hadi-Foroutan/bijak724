# ۲۸ — گزارش‌ساز

کاربر از میان فیلدهای بارنامه چند فیلد انتخاب و به‌صورت یک «گزارش» ذخیره می‌کند. بعداً با شناسهٔ گزارش، بارنامه‌های شرکت فقط با همان
فیلدها لود می‌شود یا خروجی Excel/PDF آن در صف ساخته می‌شود. همهٔ کاربران یک شرکت همهٔ گزارش‌ها و خروجی‌های آن شرکت را می‌بینند (دسترسی هر عملیات با مجوز مسیر کنترل می‌شود).

بارنامه‌هایی که در لیست/خروجی می‌آیند همان‌هایی‌اند که کاربر طبق مجوزهای «نمایش بارنامه» اجازهٔ دیدنشان را دارد.

## ساختار: سه جدول جدا

| جدول | چه کسی مدیریت می‌کند | محتوا |
|------|-----------------------|-------|
| `report_fields` | **ادمین** | فیلدهایی که کاربر اجازهٔ انتخابشان را دارد (کلید، عنوان، گروه، مسیر داده، فرمت، فعال/غیرفعال، ترتیب) |
| `report_definitions` | **کاربران شرکت** | خود گزارش: نام، شرکت و سازنده؛ همهٔ کاربران همان شرکت همهٔ گزارش‌ها را می‌بینند |
| `report_definition_fields` | سیستم | پیوند هر گزارش با فیلدهایش و ترتیب ستون‌ها |

ادمین با غیرفعال کردن یک فیلد، آن را از لیست انتخاب کاربران و از لود/خروجی همهٔ گزارش‌هایی که قبلاً انتخابش کرده‌اند برمی‌دارد
(پیوند باقی می‌ماند و با فعال شدن دوباره برمی‌گردد). حذف فیلد، پیوند آن را از همهٔ گزارش‌ها هم پاک می‌کند.

## ۱. فیلدهای قابل انتخاب (کاربر)

`GET /api/user/reports/fields` — مجوز: `user.reports.fields`

فقط فیلدهای **فعال** به ترتیب ادمین. خروجی: آرایه‌ای از `{ id, key, label, group, type, multiple }`

- `id`: همان مقداری که در `field_ids` گزارش ارسال می‌شود.
- `key`: نام ستون در پیش‌نمایش (`GET reports/{id}/waybills`).
- `group`: برای دسته‌بندی در فرم انتخاب (مشخصات بارنامه، فرستنده، گیرنده، رانندگان، ناوگان، مبالغ، محموله‌ها، ...).
- `type`: `text | number | bool | datetime | status | plate`
- `multiple: true` یعنی فیلد از یک رابطهٔ چندتایی می‌آید (مثلاً کالاهای یک بارنامه)؛ در پیش‌نمایش آرایه و در Excel/PDF با «،» به
  هم چسبانده می‌شود.

فیلدهای رابطه‌ای (مثل `cargo_names`، `sender_city`، `fleet_type`) خودشان رابطه را لود می‌کنند؛ لازم نیست فرانت چیزی بفرستد.

## ۲. CRUD گزارش‌ها (کاربر)

| متد | مسیر | مجوز |
|-----|------|------|
| GET | `/api/user/reports` | `user.reports.index` |
| POST | `/api/user/reports` | `user.reports.store` |
| GET | `/api/user/reports/{report}` | `user.reports.show` |
| PUT/PATCH | `/api/user/reports/{report}` | `user.reports.update` |
| DELETE | `/api/user/reports/{report}` | `user.reports.destroy` |

ورودی (ایجاد؛ در PATCH هر دو اختیاری):

| فیلد | قاعده |
|------|-------|
| `name`! | رشته، حداکثر ۲۵۵ |
| `field_ids`! | آرایهٔ ۱ تا ۶۰ شناسهٔ یکتا از `report_fields` فعال؛ **ترتیب آرایه = ترتیب ستون‌ها** |

| `field_options` | اختیاری. آرایه‌ای از `{field_id, align, width}` برای چیدمان هر ستون (`field_id` باید در `field_ids` باشد) |
| `field_options.*.align` | `right` (پیش‌فرض) \| `left` \| `center` — تراز متن ستون در Excel و PDF |
| `field_options.*.width` | عرض ستون به پیکسل (۲۰ تا ۱۰۰۰)، یا `null` / `"auto"` برای هم‌اندازه شدن با محتوا (پیش‌فرض) |

در PATCH اگر `field_options` یک فیلد دوباره فرستاده نشود، چیدمان قبلی همان فیلد حفظ می‌شود.

خروجی هر گزارش: `id, name, fields[{id, key, label, group, is_active, align, width}], created_at, updated_at`
(`width: null` یعنی خودکار). `columns` در `GET /reports/{report}/waybills` هم `align` و `width` دارد.

لیست از جستجوی پیشرفته پشتیبانی می‌کند: `search` (در نام)، `eq-name`، `min-created_at`، `order_field`، `order_type`، `paginate`،
`itemsPerPage`، `page`.

## ۳. بارنامه‌ها با فیلدهای گزارش

`GET /api/user/reports/{report}/waybills` — مجوز: `user.reports.waybills`

همان فیلترهای لیست بارنامه‌ها (`eq-status`، `min-issued_at`، `search`، ...) و صفحه‌بندی (پیش‌فرض صفحه‌بندی‌شده است).

```json
{
  "data": {
    "columns": [{"key": "bijak_number", "label": "شماره بیجک"}, {"key": "cargo_names", "label": "نام کالا"}],
    "records": {
      "data": [{"id": 12, "bijak_number": "555", "cargo_names": ["سیمان"]}],
      "current_page": 1, "total": 1
    }
  }
}
```

هر ردیف فقط `id` و فیلدهای انتخابی را دارد؛ مقدارها تایپ‌دار هستند (عدد، `true/false`، مقدار enum مثل `completed`).

## فیلترهای گزارش (پنجرهٔ «فیلترها»)

این کلیدها هم در `GET /api/user/reports/{report}/waybills` (query string) و هم در `filters` درخواست خروجی
(`POST reports/{report}/exports`) پذیرفته می‌شوند و **هر دو جا دقیقاً یکسان** اعمال می‌شوند؛ پس لیستی که کاربر می‌بیند همان
چیزی است که در Excel/PDF می‌آید. مقدار خالی نادیده گرفته می‌شود و همهٔ فیلترها با «و» ترکیب می‌شوند. فیلترهای پیشرفتهٔ عمومی
(`eq-...`، `min-...`، `search`) هم هنوز کار می‌کنند.

| بخش | کلید | نوع | توضیح |
|-----|------|-----|-------|
| بارنامه | `bijak_number_from`، `bijak_number_to` | عدد | از/تا شمارهٔ بارنامه |
| | `serial_number` | متن | سری بارنامه (شامل) |
| | `branch_id` | شناسه | نمایندگی/شعبه (شرکتی که بارنامه را صادر کرده) |
| | `user_id` | شناسه | کاربر صادرکننده |
| | `tracking_code` | متن | کد رهگیری |
| | `issued_from`، `issued_to` | تاریخ‌زمان | از/تا زمان صدور؛ **میلادی** مثل `2026-10-01 00:00` (تاریخ شمسی را فرانت تبدیل کند) |
| | `referral_number_from`، `referral_number_to` | عدد | از/تا شمارهٔ حواله |
| | `description` | متن | توضیحات (شامل) |
| | `transport_contract_id` | شناسه | قرارداد حمل |
| | `insurance_id` | شناسه | بیمهٔ مسئولیت |
| | `status` | enum | `incomplete | completed | referral | canceled` |
| | `freight_at_origin` | bool | نوع کرایه: `1` کرایه در مبدا، `0` غیر آن |
| | `weight_from`، `weight_to` | عدد | وزن (وزن ارجاع) |
| | `quantity_from`، `quantity_to` | عدد | تعداد |
| فرستنده | `sender_id` | شناسه | کد فرستنده |
| | `sender_name` | متن | نام |
| | `sender_national_identifier` | متن | کد/شناسهٔ ملی |
| | `sender_postal_code` | متن | کد پستی |
| | `sender_state_id` | شناسه | استان مبدا |
| | `sender_city_code` | عدد | شهر مبدا (`code` شهر) |
| | `sender_address` | متن | آدرس مبدا |
| گیرنده | `receiver_id`، `receiver_name`، `receiver_national_identifier`، `receiver_postal_code`، `receiver_state_id`، `receiver_city_code`، `receiver_address` | | همان فیلترهای فرستنده، برای مقصد |
| راننده | `driver_national_code`، `driver_name` | متن | کد ملی و نام راننده |
| | `driver_roles[]` | آرایه | نقش‌های تیک‌خورده: `driver1` (راننده اول)، `driver2` (راننده دوم)، `referral_driver` (راننده حواله). اگر هیچ‌کدام نباشد هر سه بررسی می‌شود |
| ناوگان | `smart_card_number` | متن | شمارهٔ هوشمند |
| | `plate_first_number`، `plate_second_letter`، `plate_third_number`، `plate_fourth_number` | متن | چهار بخش پلاک |
| | `loading_type_id` | شناسه | نوع بارگیر |
| | `ownership_type` | enum | نوع مالکیت: `owned | leased | free | unknown` |
| محموله | `cargo_id` | شناسه | محموله (کالا) |
| | `cargo_description` | متن | شرح محموله (عنوان یا توضیح) |
| | `packaging_id` | شناسه | بسته‌بندی |
| | `product_owner_id` | شناسه | صاحب کالا |
| مرتب‌سازی | `sort_by` | متن | ستون مرتب‌سازی (مثل `bijak_number`، `issued_at`، `status`) |
| | `sort_direction` | `asc | desc` | جهت |

فیلترهای محموله روی **یک** ردیف محمولهٔ بارنامه با هم ترکیب می‌شوند (مثلاً «گندم» و «کیسه» باید در یک محموله باشند).
مقدار نامعتبر (مثل `status=abc`) خطای `422` می‌دهد. مثال:

```
GET /api/user/reports/3/waybills?issued_from=2026-10-01 00:00&status=completed&driver_national_code=1234&driver_roles[]=driver1&driver_roles[]=driver2&paginate=1
```

```json
POST /api/user/reports/3/exports
{ "format": "xlsx", "filters": { "issued_from": "2026-10-01 00:00", "status": "completed", "sender_state_id": 5 } }
```

## فیلتر پیشرفتهٔ عمومی (کلیدهای ستونی در query string)

علاوه بر کلیدهای «پنجرهٔ فیلترها» در بالا، هر ستونِ بارنامه را می‌شود مستقیم با نام خودش فیلتر کرد. همین کلیدها در
`GET /api/user/reports/{report}/waybills` (query string) و در `filters` درخواست خروجی کار می‌کنند و در هر دو یکسان‌اند.
کلید ناشناخته یا غیرمجاز **بی‌صدا نادیده گرفته می‌شود** (خطا نمی‌دهد)، پس نام ستون را دقیق بنویسید.

### نحوهٔ نوشتن

| الگو | معنی | مثال |
|------|------|------|
| `ستون=مقدار` | شامل (`LIKE %مقدار%`) | `description=فوری` |
| `eq-ستون=مقدار` | مساوی دقیق | `eq-status=completed` ، `eq-created_by=5` |
| `notEq-ستون=مقدار` | مخالف | `notEq-status=canceled` |
| `min-ستون=مقدار` | بزرگ‌تر یا مساوی (از) | `min-created_at=2026-10-01` |
| `max-ستون=مقدار` | کوچک‌تر یا مساوی (تا) | `max-created_at=2026-10-31 23:59:59` |
| `رابطه__ستون` | ستونِ یک رابطه (دو underscore) | `fleet__smart_card_number=123` ، `eq-cargos__cargo__code=1001` |
| `search=متن` | جستجوی کلی در ستون‌های متنی بارنامه | `search=احمدی` |
| `order_field=ستون` | مرتب‌سازی (پیش‌فرض `id`) | `order_field=issued_at` |
| `order_type=ASC|DESC` | جهت مرتب‌سازی (پیش‌فرض `DESC`) | `order_type=ASC` |
| `paginate=1` ، `itemsPerPage` ، `page` | صفحه‌بندی (حداکثر ۱۰۰ در صفحه) | `paginate=1&itemsPerPage=20&page=2` |

تاریخ‌ها را **میلادی** بفرستید (`2026-10-01` یا `2026-10-01 08:30:00`). `min-`/`max-` روی ستون‌های تاریخ و عدد معنی دارند.
در درخواست خروجی همین کلیدها داخل آبجکت `filters` می‌آیند: `"filters": { "min-created_at": "2026-10-01", "eq-status": "completed" }`.

### ستون‌های قابل فیلتر

**سیستمی:** `id`، `created_at` (زمان ثبت)، `updated_at` (آخرین ویرایش)، `owner_company_id` (شرکت/نمایندگی صادرکننده)، `created_by` (کاربر ثبت‌کننده)

**بارنامه:** `bijak_number`، `serial_number`، `referral_number`، `referral_serial`، `bijak_tracking_code`، `status`، `issued_at` (زمان صدور)، `issued_by_print_name`، `description`، `loading_started_at`، `loading_ended_at`، `referral_weight`، `quantity`، `freight_at_origin`، `is_fixed`، `transport_contract_id`، `liability_insurance` (شناسهٔ بیمه)

**فرستنده:** `sender_id`، `sender_full_name`، `sender_national_identifier`، `sender_mobile`، `sender_address_id`، `sender_address_postal_code`، `sender_address_phone`، `sender_address_city_code`، `sender_address_address`، `sender_address_description`

**گیرنده:** `receiver_id`، `receiver_full_name`، `receiver_national_identifier`، `receiver_mobile`، `receiver_address_id`، `receiver_address_postal_code`، `receiver_address_phone`، `receiver_address_city_code`، `receiver_address_address`، `receiver_address_description`

**رانندگان:** `driver1_id`، `driver1_national_code`، `driver1_full_name`، `driver1_phone` — همین چهار مورد برای `driver2_` و `referral_driver_` هم هست.

**مدیر فنی:** `technical_manager_id`، `technical_manager_full_name`، `technical_manager_mobile`

**مبالغ:** `base_freight_amount`، `advance_freight_amount`، `weighbridge_amount`، `loading_amount`، `warehousing_amount`، `unloading_amount`، `excess_tonnage_amount`، `commission_amount`، `insurance_amount`، `insurance_tax_amount`، `detention_amount`، `driver_receivable_amount`، `payable_amount`

**فیلدهای اضافی:** `additional_field_1` تا `additional_field_6`

**ناوگان و رابطه‌ها:** `fleet_id`، `fleet__smart_card_number`، `fleet__plate_first_number`، `fleet__plate_second_letter`، `fleet__plate_third_number`، `fleet__plate_fourth_number`، `firstDriver__full_name`، `origin__city__code`، `destination__city__code`، `cargos__cargo__code`

مرتب‌سازی (`order_field`) روی همهٔ ستون‌های بالا (بدون `__`) کار می‌کند.

### مثال‌ها

```
# بارنامه‌های ثبت‌شده در اکتبر ۲۰۲۶ که تکمیل شده‌اند، جدیدترین اول
GET /api/user/reports/3/waybills?min-created_at=2026-10-01&max-created_at=2026-10-31 23:59:59&eq-status=completed&order_field=created_at&order_type=DESC&paginate=1

# کرایهٔ پایه حداقل ۱ میلیون و مبلغ قابل پرداخت حداکثر ۵ میلیون
GET /api/user/reports/3/waybills?min-base_freight_amount=1000000&max-payable_amount=5000000

# ترکیب با کلیدهای پنجرهٔ فیلترها
GET /api/user/reports/3/waybills?min-created_at=2026-10-01&sender_state_id=5&driver_national_code=1234
```

```json
POST /api/user/reports/3/exports
{ "format": "pdf", "filters": { "min-created_at": "2026-10-01", "eq-status": "completed", "cargo_id": 12 } }
```

## ۴. درخواست خروجی (صف)

`POST /api/user/reports/{report}/exports` — مجوز: `user.reports.exports.store` — پاسخ `202`

| فیلد | توضیح |
|------|-------|
| `format`! | `xlsx` یا `pdf` |
| `filters` | آبجکت فیلترهای جستجوی پیشرفتهٔ بارنامه، مثل `{"eq-status": "completed", "min-issued_at": "2026-10-01"}` |

درخواست فوراً ثبت می‌شود و ساخت فایل در صف انجام می‌شود (به‌نوبت، یکی‌یکی). هر کاربر حداکثر ۵ درخواست فعال (در صف/در حال ساخت)
دارد؛ بیشتر از آن خطای `422` می‌گیرد. سقف تعداد بارنامه: ۳۰٬۰۰۰ برای Excel و ۵٬۰۰۰ برای PDF؛ بیشتر از آن خروجی «ناموفق» با پیام
مناسب می‌شود (فیلتر را محدودتر کنید).

## ۵. گزارش‌های اخیر

| متد | مسیر | مجوز |
|-----|------|------|
| GET | `/api/user/report-exports` | `user.report-exports.index` |
| GET | `/api/user/report-exports/{reportExport}` | `user.report-exports.show` |
| DELETE | `/api/user/report-exports/{reportExport}` | `user.report-exports.destroy` |

جدیدترین‌ها اول می‌آیند. فیلتر: `eq-status`، `eq-format`، `search` (نام)، صفحه‌بندی.

خروجی هر مورد:

| فیلد | توضیح |
|------|-------|
| `id`, `report_definition_id`, `name`, `format` | `name` و `fields` هنگام ثبت کپی می‌شوند؛ ویرایش/حذف گزارش روی خروجیِ در صف اثری ندارد |
| `status`, `status_label` | `queued` در صف · `processing` در حال آماده‌سازی · `completed` تکمیل · `failed` ناموفق · `expired` منقضی |
| `rows_count`, `file_size`, `error` | |
| `started_at`, `completed_at`, `expires_at`, `created_at` | |
| `download_url` | لینک موقت امضاشده؛ فقط برای `completed` و تا `expires_at` معتبر است، وگرنه `null` |

`download_url` را مستقیم باز کنید (بدون توکن)؛ فایل دانلود می‌شود. پس از انقضا لینک `403` و فایل پاک‌شده است (`410`).

## ۶. رویدادهای سوکت

کانال خصوصی: `private-users.{userId}.reports` (احراز هویت از `/api/broadcasting/auth`). چون پیام لینک دانلود دارد، فقط به خود
کاربری که گزارش را خواسته می‌رسد، نه بقیهٔ کاربران شرکت.

| نام رویداد | زمان |
|------------|------|
| `report-export.queued` | درخواست ثبت و در صف قرار گرفت |
| `report-export.processing` | صف شروع به ساخت کرد (در حال آماده‌سازی) |
| `report-export.completed` | گزارش ساخته شد؛ `download_url` در پیام هست |
| `report-export.failed` | ساخت ناموفق بود؛ `error` علت را دارد |

در Laravel Echo نام رویداد با پیشوند نقطه گوش داده می‌شود (`.report-export.completed`). بدنهٔ پیام:

```json
{ "company_id": 3, "export": { "id": 7, "status": "completed", "download_url": "https://...", "...": "..." } }
```

## ۷. مدیریت فیلدهای گزارش‌ساز (ادمین)

| متد | مسیر | مجوز |
|-----|------|------|
| GET | `/api/admin/report-fields` | `admin.report-fields.index` |
| POST | `/api/admin/report-fields` | `admin.report-fields.store` |
| GET | `/api/admin/report-fields/{reportField}` | `admin.report-fields.show` |
| PUT/PATCH | `/api/admin/report-fields/{reportField}` | `admin.report-fields.update` |
| DELETE | `/api/admin/report-fields/{reportField}` | `admin.report-fields.destroy` |

| فیلد | قاعده |
|------|-------|
| `key`! | یکتا، انگلیسی کوچک و `_` (مثل `sender_city`) |
| `label`! | عنوانی که کاربر می‌بیند و سر ستون Excel/PDF می‌آید |
| `group`! | دستهٔ نمایش در فرم انتخاب |
| `path`! | مسیر داده روی بارنامه: نام ستون بارنامه (`bijak_number`) یا ستونِ یک رابطهٔ مجاز (`senderAddress.city.name`، `cargos.*.cargo.name`؛ `*` یعنی رابطهٔ چندتایی). رابطه‌های مجاز در `config/waybill_report.php` (`allowed_relations`) هستند؛ مسیر نامعتبر `422` می‌دهد |
| `format`! | `text | number | bool | datetime | status | plate` (فرمت `plate` مسیر `fleet` می‌خواهد) |
| `is_active` | پیش‌فرض `true`؛ `false` یعنی کاربر نبیند و در گزارش‌های قبلی هم نیاید |
| `sort_order` | ترتیب نمایش؛ اگر نیاید آخر لیست می‌رود |

خروجی هر مورد: `id, key, label, group, path, format, multiple, is_active, sort_order, reports_count` (تعداد گزارش‌هایی که از این فیلد استفاده می‌کنند؛ قبل از حذف نشانش دهید).
در ویرایش (PATCH) اگر `path` یا `format` تغییر می‌کند هر دو را بفرستید (مسیر نسبت به فرمت بررسی می‌شود).
لیست فیلتر پیشرفته دارد (`eq-group`، `eq-is_active`، `search`...) و پیش‌فرض بر اساس `sort_order` مرتب است.

فیلدهای پیش‌فرض (۷۶ مورد) با مایگریشن ساخته می‌شوند؛ `php artisan db:seed --class=ReportFieldSeeder` فقط فیلدهای گم‌شده را اضافه می‌کند و
تغییرات ادمین را دست نمی‌زند.

## ۸. نگهداری موقت فایل (تنظیم ادمین)

فایل هر گزارش پس از «مدت نگهداری» از سرور پاک و وضعیت آن `expired` می‌شود (هر ۵ دقیقه بررسی می‌شود). پیش‌فرض ۸ ساعت است و ادمین
بدون تغییر کد عوض می‌کند:

| متد | مسیر | مجوز |
|-----|------|------|
| GET | `/api/admin/settings` | `admin.settings.show` |
| PUT/PATCH | `/api/admin/settings` | `admin.settings.update` |

بدنه: `{ "reports": { "retention_hours": 12 } }` (عدد صحیح ۱ تا ۷۲۰). تغییر فقط روی گزارش‌هایی که از آن پس ساخته می‌شوند اثر دارد.

## نکتهٔ استقرار

صف گزارش‌ها جداست و باید یک worker برایش اجرا باشد (یکی، تا گزارش‌ها به‌نوبت ساخته شوند)، و scheduler برای پاک‌سازی:

```bash
php artisan queue:work --queue=reports
php artisan schedule:work   # یا cron: * * * * * php artisan schedule:run
```


## فونت PDF

فایل فونت (`.ttf` یا `.otf`) را در پوشهٔ `storage/app/report-fonts` بگذارید (مسیر با `REPORT_PDF_FONT_DIRECTORY` در `.env`
عوض می‌شود). PDFهای بعدی با همین فونت ساخته می‌شوند؛ اگر چند فایل باشد جدیدترین فایل استفاده می‌شود و با عوض کردن یا
جایگزین کردن فایل، فونت هم عوض می‌شود. اگر پوشه خالی باشد یا فایل خراب باشد، فونت پیش‌فرض (DejaVu Sans) استفاده می‌شود.
فونت باید حروف فارسی را داشته باشد.

## تاریخ‌ها

در فایل‌های Excel و PDF همهٔ تاریخ‌ها شمسی چاپ می‌شوند: `1405/08/06 14:30:05` (اگر ساعت دقیقاً ۰۰:۰۰:۰۰ باشد فقط
`1405/08/06`). در پاسخ JSON لیست بارنامه‌ها (`GET /reports/{report}/waybills`) تاریخ‌ها همچنان میلادی و ماشین‌خوان
(`2026-10-28 14:30:05`) هستند تا فرانت خودش تبدیل کند.

## عنوان و جمع کل فایل

- بالای فایل (عنوان PDF و ردیف اول Excel) به‌جای نام گزارش، **نام شرکت و کد پنل** آن نوشته می‌شود: `نام شرکت - کد پنل: 12345`.
- اگر گزارش فیلد عددی (فرمت `number`، مثل مبالغ، وزن و تعداد) داشته باشد، **آخرین رکورد جمع** این فیلدهاست؛ کلمهٔ «جمع» در
  اولین ستون غیرعددی نوشته می‌شود. جمع روی همهٔ بارنامه‌های فیلتر شده حساب می‌شود، نه فقط یک صفحه.

## اجرا روی سرور (دپلوی خودکار)

نیازی به استارت دستی نیست. workflow دپلوی (`.github/workflows/deploy-production.yml`) بعد از هر push روی `main`:

1. دستور `queue:restart` و `reverb:restart` را می‌زند تا worker و Reverb با کد جدید برگردند.
2. با `deploy/bin/install-cron.sh` دو cron در crontab کاربر دپلوی می‌گذارد (تکراری نمی‌شوند): `schedule:run` هر دقیقه، و
   `deploy/bin/keep-services-alive.sh` هر دقیقه.
3. `keep-services-alive.sh` را یک‌بار همان لحظه اجرا می‌کند.

`keep-services-alive.sh` هر پروسه‌ای (worker صف و Reverb) که در حال اجرا نباشد را راه می‌اندازد و هر کدام که در حال اجراست
را دست نمی‌زند؛ پس اگر پروسه‌ای crash کند یا بعد از دپلوی خارج شود، حداکثر تا یک دقیقه بعد دوباره بالا می‌آید.
لاگ‌ها در `storage/logs/queue-worker.log` و `storage/logs/reverb.log` است.
