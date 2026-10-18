# بارنامه‌ها (بیجک‌ها)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۸

ایجاد، ویرایش، ابطال و لیست بارنامه‌ها.

### ۱۹.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۱۹.۱ | GET | `/api/user/waybills` | query string | `user.waybills.index` | لیست بارنامه‌ها |
| ۱۹.۲ | POST | `/api/user/waybills` | JSON | `user.waybills.store` | ایجاد بارنامه (ناتمام یا کامل) |
| ۱۹.۳ | GET | `/api/user/waybills/options` | query string | `user.waybills.options` | وضعیت‌ها و گزینه‌های فرم |
| ۱۹.۴ | GET | `/api/user/waybills/{waybill}` | query string | `user.waybills.show` | جزئیات بارنامه |
| ۱۹.۵ | PUT\|PATCH | `/api/user/waybills/{waybill}` | JSON | `user.waybills.update` | ویرایش بارنامه |
| ۱۹.۶ | DELETE | `/api/user/waybills/{waybill}` | ندارد | `user.waybills.destroy` | حذف بارنامه (سرور وضعیت را بررسی نمی‌کند) |
| ۱۹.۷ | PATCH | `/api/user/waybills/{waybill}/cancel` | ندارد | `user.waybills.cancel` | ابطال بارنامهٔ صادرشده |
| ۱۹.۸ | PATCH | `/api/user/waybills/{waybill}/referral/cancel` | ندارد | `user.waybills.referral.cancel` | ابطال شمارهٔ حوالهٔ آن |

### ۱۹.۲ ورودی و خروجی

بارنامه (بیجک) از `incomplete` به `referral` و سپس `completed` می‌رود. هر مرحله فیلدهای بیشتری لازم دارد و سرور شماره‌های حواله و بیجک را رزرو می‌کند. فقط بارنامه‌های `incomplete` و `referral` قابل ویرایش‌اند.

```mermaid
stateDiagram-v2
    direction LR
    incomplete: incomplete — ناتمام (قابل ویرایش)
    referral: referral — صدور حواله (قابل ویرایش، دارای شمارهٔ حواله)
    completed: completed — تمام‌شده (فقط‌خواندنی، شمارهٔ بیجک ثبت شده)
    canceled: canceled — باطل‌شده (فقط‌خواندنی)
    incomplete --> referral: status = referral
    referral --> completed: status = completed
    incomplete --> completed: status = completed (شمارهٔ حواله هم رزرو می‌شود)
    referral --> incomplete: PATCH /referral/cancel
    completed --> canceled: PATCH /cancel
```

ایجاد هر وضعیتی به‌جز `canceled` را می‌پذیرد. ابطال حواله، شمارهٔ آن را بایگانی می‌کند و بارنامه را به `incomplete` برمی‌گرداند.

#### وضعیت‌ها

| وضعیت | برچسب | قابل ویرایش | نحوهٔ رسیدن |
| --- | --- | --- | --- |
| `incomplete` | ناتمام | بله | ایجاد یا ویرایش با این وضعیت؛ همچنین نتیجهٔ ابطال حواله |
| `referral` | صدور حواله | بله | ایجاد یا ویرایش با این وضعیت؛ سرور `referral_number` و `referral_serial` را تخصیص می‌دهد |
| `completed` | تمام‌شده | خیر | ایجاد یا ویرایش با این وضعیت؛ سرور شمارهٔ بیجک و کد رهگیری را رزرو می‌کند |
| `canceled` | باطل‌شده | خیر | فقط با `PATCH /user/waybills/{id}/cancel` روی بارنامهٔ تمام‌شده |

قواعدی که UI باید رعایت کند:

- ارسال `status: "canceled"` در ایجاد یا ویرایش 422 برمی‌گرداند؛ از endpoint ابطال استفاده کنید.
- ویرایش بارنامهٔ `completed` یا `canceled` خطای 422 می‌دهد («بارنامه صادرشده یا باطل‌شده قابل ویرایش نیست»).
- بارنامهٔ `referral` که با `status: "incomplete"` ویرایش شود، همچنان `referral` می‌ماند. برای برگشت، `PATCH /{id}/referral/cancel` را صدا بزنید: شماره را در حواله‌های ابطال‌شده بایگانی می‌کند، آن را پاک می‌کند و بارنامه را `incomplete` می‌کند.
- `referral_number`، `referral_serial` و `bijak_tracking_code` فقط‌خواندنی‌اند و سرور در ویرایش آن‌ها را نادیده می‌گیرد.
- اگر تنظیم `general.assign_first_available_waybill_number` روشن باشد، تکمیل بارنامه با شماره‌ای غیر از اولین شمارهٔ آزاد بیجک، خطای 422 با ذکر شمارهٔ درست برمی‌گرداند.

#### فیلدهای الزامی بر اساس وضعیت

| گروه فیلد | `incomplete` | `referral` | `completed` |
| --- | --- | --- | --- |
| `status` | الزامی | الزامی | الزامی |
| `sender_id`، `sender_address_id`، `receiver_id`، `receiver_address_id` | اختیاری | الزامی | الزامی |
| `driver1_id`، `referral_driver_id`، `fleet_id` | اختیاری | الزامی | الزامی |
| `loading_started_at`، `loading_ended_at` (≥ شروع) | اختیاری | الزامی | اختیاری |
| `bijak_number`، `serial_number`، `issued_at`، `liability_insurance` | نادیده گرفته می‌شود | نادیده گرفته می‌شود | الزامی |
| `transport_contract_id`، `base_freight_amount` (≥ ۱)، `insurance_amount` (≥ ۱)، `insurance_tax_amount`، `payable_amount` | اختیاری | اختیاری | الزامی |
| `cargos` (۱ تا ۱۰ ردیف) | اختیاری، ۰ تا ۱۰ ردیف | الزامی | الزامی |
| `cargos.*.cargo_id`، `cargos.*.packaging_id` | اختیاری | الزامی | الزامی |
| `cargos.*.value`، `cargos.*.quantity` (≥ ۱) | اختیاری | اختیاری | الزامی |
| `cargos.*.origin_weight` | در هر ردیف الزامی | الزامی | الزامی |

سایر فیلدها:

- در همهٔ وضعیت‌ها اختیاری: `driver2_id` (باید با `driver1_id` متفاوت باشد)، `referral_weight`، `quantity`، `description`، `advance_freight_amount`، `weighbridge_amount`، `loading_amount`، `warehousing_amount`، `unloading_amount`، `excess_tonnage_amount`، `commission_amount`، `detention_amount`، `driver_receivable_amount`، `freight_at_origin`، `is_fixed`.
- ردیف‌های کالا همچنین `product_owner_id`، `title`، `description` و `is_traffic` می‌گیرند.
- همهٔ مبالغ اعداد صحیح نامنفی‌اند؛ API واحد پول را مشخص نمی‌کند.
- شناسه‌ها باید متعلق به همان شرکت باشند:
  - `sender_id` باید طرف حملی با `is_sender` باشد و `receiver_id` طرفی با `is_receiver`.
  - هر شناسهٔ آدرس باید متعلق به طرف حمل خودش باشد.
  - `cargo_id` و `packaging_id` **کد** کاتالوگ هستند، نه شناسه.
  - `transport_contract_id` باید قراردادی باشد که کاربر به آن دسترسی دارد (عمومی، یا تخصیص‌یافته به او).

مشکل شناخته‌شده: `base_freight_amount` قاعدهٔ `nullable` ندارد، بنابراین ارسال `null` در بارنامهٔ ناتمام خطای اعتبارسنجی می‌دهد. به‌جای آن کلید را ارسال نکنید.

#### تصویرهای ثابت (Snapshots)

هنگام ذخیره، سرور اطلاعات طرف حمل، آدرس و راننده را در خود بارنامه کپی می‌کند: `sender_full_name`، `sender_address_postal_code`، `driver1_national_code` و مانند آن. بنابراین بارنامهٔ چاپ‌شده با ویرایش بعدیِ رکورد اصلی تغییر نمی‌کند. برای نمایش و چاپ همین ستون‌های snapshot را بخوانید؛ اشیای تودرتوی `sender`، `first_driver`، `fleet` و بقیه رکوردهای زنده را نشان می‌دهند.

#### پاسخ بارنامه

- همهٔ ستون‌های جدول بارنامه: snapshotها، شماره‌ها، همهٔ فیلدهای `*_amount`، `status`، `freight_at_origin` و `is_fixed`.
- `issued_at` در قالب `YYYY-MM-DD HH:mm:ss`.
- اشیای تودرتوی `sender`، `sender_address`، `receiver`، `receiver_address`، `first_driver`، `second_driver`، `referral_driver`، `fleet`، `insurance` و `transport_contract`.
- `cargos[]`: هر ردیف شامل `id, cargo_id, packaging_id, product_owner_id, title, description, origin_weight, value, quantity, is_traffic, is_returned, cottage_number, cottage_number_2, driver_account_number, container_number, container_number_2` و اشیای تودرتوی `cargo`، `packaging` و `product_owner` است.
- `transport_contract.items` فقط تا زمانی که بارنامه `incomplete` یا `referral` است برگردانده می‌شود.

فیلترهای لیست از دستور مشترک روی هر ستون بارنامه استفاده می‌کنند، به‌علاوهٔ:

- `fleet__plate_*` و `fleet__smart_card_number`
- `firstDriver__full_name`
- `cargos__cargo__code`
- `origin__city__code` و `destination__city__code`

لیست ادمین `GET /admin/waybills` پارامترهای `company_id`، `company_name`، `status`، `created_at_from`، `created_at_to`، `serial_number`، `referral_number` و `bijak_tracking_code` را می‌پذیرد.

#### گزینه‌های فرم — `GET /user/waybills/options`

```json
{
  "statuses": [{ "value": "incomplete", "label": "ناتمام" }],
  "transport_contracts": [
    { "id": 1, "title": "...", "contract_number": "1", "items": [{ "name": "commission", "primary_value": 10 }] }
  ]
}
```

قراردادها همان‌هایی هستند که کاربر مجاز به استفاده از آن‌هاست؛ قراردادهای دارای پرچم پیش‌فرض در ابتدا می‌آیند.
