# Enumها

[← فهرست بخش‌ها](README.md)

ستون `value` را ارسال کنید و برچسب را نمایش دهید. Enumهایی که با «از API» مشخص شده‌اند، برچسبشان در پاسخ‌ها هم می‌آید
(`status_label`، `name_label`، `type_label` یا endpointهای گزینه‌ها)؛ آن‌ها را به متن ثابت در کد ترجیح دهید.

| Enum                                         | مقادیر (برچسب)                                                                                                    | محل استفاده                                                                |
|----------------------------------------------|-------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------|
| `Status`                                     | `active` (فعال)، `inactive` (غیرفعال)                                                                             | شرکت‌ها، نقش‌ها، مجوزها، رانندگان، ناوگان، طرفین حمل، بیمه‌ها، قراردادهای حمل |
| `UserStatus`                                 | `active`، `inactive`، `blocked`                                                                                   | کاربران                                                                    |
| `CompanyParent`                              | `original`، `branch`                                                                                              | شرکت‌ها                                                                     |
| `Role`                                       | `superAdmin`، `admin`، `companyManager`، `user`                                                                   | `role.name`                                                                |
| `WaybillStatus`                              | `incomplete` (ناتمام)، `referral` (صدور حواله)، `completed` (تمام‌شده)، `canceled` (باطل‌شده) — از API              | بارنامه‌ها                                                                  |
| `BijakNumberStatus` / `ReferralNumberStatus` | `active` (فعال)، `inactive` (غیرفعال)، `completed` (اتمام‌شده)                                                     | بازه‌های شماره                                                              |
| `FleetOwnershipType`                         | `owned` (ملکی)، `leased` (استیجاری)، `free` (آزاد)، `unknown` (نامشخص)                                            | `fleets.ownership_type`                                                    |
| `TransportContractItemType`                  | `owned` (ملکی)، `rental` (استیجاری)، `free` (آزاد)، `unknown` (نامشخص) — فیلدهای `is_<value>` و `default_<value>` | آیتم‌های قرارداد و پرچم‌های پیش‌فرض                                           |
| `NotificationType`                           | `success` (موفقیت‌آمیز)، `error` (خطا)، `warning` (هشدار)، `info` (اطلاعات) — از API                               | اعلان‌ها                                                                    |
| `SettingValueType`                           | `boolean`، `integer`، `decimal`، `string`                                                                         | تنظیمات                                                                    |

به این ناهمخوانی توجه کنید: ناوگان از `leased` استفاده می‌کند، در حالی که آیتم‌های قرارداد و پرچم‌های پیش‌فرض از
`rental`. آن‌ها را صریحاً نگاشت کنید و از یک ثابت برای هر دو استفاده نکنید.

### نام آیتم‌های قرارداد حمل

ترتیب با endpoint گزینه‌ها یکی است (`id` از ۱ تا ۱۱).

| `id` | `value`             | برچسب            | فیلد بارنامه (`field_name`) |
|------|---------------------|------------------|-----------------------------|
| 1    | `base_freight`      | کرایه پایه       | `base_freight_amount`       |
| 2    | `loading_cost`      | هزینه بارگیری    | `loading_amount`            |
| 3    | `weighbridge_cost`  | هزینه باسکول     | `weighbridge_amount`        |
| 4    | `warehousing`       | انبارداری        | `warehousing_amount`        |
| 5    | `unloading_cost`    | تخلیه بار        | `unloading_amount`          |
| 6    | `commission`        | کمیسیون          | `commission_amount`         |
| 7    | `excess_tonnage`    | اضافه تناژ       | `excess_tonnage_amount`     |
| 8    | `insurance_premium` | حق بیمه          | `insurance_amount`          |
| 9    | `insurance_vat`     | ارزش افزوده بیمه | `insurance_tax_amount`      |
| 10   | `advance_freight`   | پیش‌کرایه         | `advance_freight_amount`    |
| 11   | `detention`         | حق توقف          | `detention_amount`          |

برچسب `primary_value`: برای کرایهٔ پایه «کرایه از هر تن»، برای حق بیمه «درصد از تخفیف» و برای بقیه «درصد از کرایه».
برچسب `secondary_value`: برای کرایهٔ پایه «کرایه ثابت»، برای حق بیمه «مقدار ثابت از تخفیف» و برای بقیه «مقدار ثابت».
