# کاربران (ادمین)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل ادمین — **تعداد روت:** ۷

مدیریت همهٔ کاربران؛ فقط توکن سیستم.

### ۲۱.۱ روت‌ها و مجوزها

| #    | متد        | مسیر                                  | نوع ورودی    | مجوز لازم                     | کاربرد                 |
|------|------------|---------------------------------------|--------------|-------------------------------|------------------------|
| ۲۱.۱ | GET        | `/api/admin/users`                    | query string | `admin.users.index`           | لیست کاربران           |
| ۲۱.۲ | POST       | `/api/admin/users`                    | JSON         | `admin.users.store`           | ایجاد کاربر            |
| ۲۱.۳ | GET        | `/api/admin/users/{user}`             | query string | `admin.users.show`            | جزئیات کاربر           |
| ۲۱.۴ | PUT\|PATCH | `/api/admin/users/{user}`             | JSON         | `admin.users.update`          | ویرایش کاربر           |
| ۲۱.۵ | DELETE     | `/api/admin/users/{user}`             | ندارد        | `admin.users.destroy`         | حذف کاربر              |
| ۲۱.۶ | GET        | `/api/admin/users/{user}/permissions` | query string | `admin.users.permissions`     | مجوزهای یک کاربر       |
| ۲۱.۷ | POST       | `/api/admin/users/permissions/{user}` | JSON         | `admin.users.syncPermissions` | جایگزینی مجوزهای کاربر |

### ۲۱.۲ ورودی و خروجی

همان فیلدهای کاربران شرکت، به‌علاوهٔ `role_id`! (یک نقش موجود)، `company_id` (برای نقش‌های شرکتی الزامی) و `insurance_agency_id` (فقط برای نقش `insurance`، الزامی؛ [بخش ۲۱.۳](#۲۱۳-نقش-insurance-پنل-بیمه--جدید)). `status`!
الزامی است و پرچم‌های حذف تصویر `remove_profile_image`/`is_profile_delete` و `remove_signature_image`/
`is_signature_delete` هستند. `POST /admin/users/permissions/{user}` بدنهٔ `{ permissions: number[] }` (الزامی، شناسه‌های
موجود) می‌گیرد.

### ۲۱.۳ نقش `insurance` (پنل بیمه) — **جدید**

نقش جدیدی به نام `insurance` («نمایندگی بیمه») داریم. در لیست نقش‌ها (`GET /api/admin/roles`) دیده می‌شود و در dropdown «نقش» فرم کاربر انتخاب می‌شود.
فرم کاربر باید **بر اساس نقش انتخاب‌شده** فیلدهایش را عوض کند (تشخیص با `role.name === "insurance"`، نه `id`):

| وضعیت فرم                  | `company_id`                | `insurance_agency_id` |
|----------------------------|-----------------------------|-----------------------|
| نقش `insurance`             | **ممنوع** (نفرستید / پنهان) | **الزامی**            |
| نقش شرکتی (`companyManager`، `user`) | الزامی              | ممنوع (نفرستید)       |
| `admin` / `superAdmin`      | ندارد                       | ممنوع                 |

کاربر بیمه **فقط به یک نماینده بیمه وصل می‌شود** که می‌تواند **کشوری، استانی یا نمایندگی** باشد. شرکت بیمه، استان، شهر و سطح (`level`) اطلاعات خودِ نماینده‌اند ([بخش ۳۴](34-insurance-agencies.md))
و دیگر روی کاربر ارسال یا ذخیره نمی‌شوند (`city_code`، `insurance_company_id` و `insurance_level` در فرم کاربر نادیده گرفته می‌شوند).

| فیلد                  | قاعده                                                                                                              |
|-----------------------|--------------------------------------------------------------------------------------------------------------------|
| `insurance_agency_id`! | شناسهٔ نماینده بیمهٔ موجود و حذف‌نشده از **هر سطح**؛ از لیست درختی `GET /api/admin/insurance-agencies?tree=1` ([بخش ۳۴.۵](34-insurance-agencies.md)) — مقدار = `id2` گره |

رفتار فرم:
1. وقتی نقش `insurance` انتخاب شد، فیلد شرکت را پنهان کنید و فقط انتخاب «نماینده» را (الزامی) نشان دهید: یک **tree-select** دقیقاً مثل انتخاب کاربر/شرکت والد، با داده‌ی `GET /api/admin/insurance-agencies?tree=1` (ریشه = نماینده کشوری هر شرکت بیمه، فرزندان = استان‌ها، نوه‌ها = نمایندگی‌ها). متن گره `label` است و **هر گره** (هر سه سطح) قابل انتخاب است؛ مقدار ارسالی `id2` گره است، نه `id` (که مسیر درخت است). برای محدود کردن به یک شرکت بیمه `&eq-insurance_company_id=<id>` بفرستید.
2. در **ویرایش** با عوض‌کردن نقش از `insurance` به نقش دیگر، سرور `insurance_agency_id` را خالی می‌کند؛ با عوض‌کردن نقش به `insurance`، نمایندگی را بخواهید و `company_id` را `null`/نفرستید.

نمونهٔ ایجاد کاربر پنل بیمه:

```json
{
  "role_id": 6,
  "first_name": "رضا", "last_name": "کریمی", "national_code": "1234567890",
  "username": "parsian.tehran", "password": "secret-pass", "status": "active",
  "min_commission_percentage": 0, "max_commission_percentage": 0,
  "insurance_agency_id": 5
}
```

**خروجی کاربر** (`UserResource`، در همهٔ روت‌های این بخش و در پاسخ login/checkToken) فیلد `insurance_agency_id` را دارد؛ برای کاربران غیر بیمه `null` است:

```json
{ "id": 31, "company_id": null, "insurance_agency_id": 5, "role": { "id": 6, "name": "insurance" } }
```

خطاها: `422` با خطا روی خود فیلد، مثلاً `insurance_agency_id` (الزامی، وجود ندارد یا حذف شده؛ یا ارسال آن برای نقش غیر بیمه)،
`company_id` (برای نقش بیمه ممنوع). جزئیات رفتار پنل: [بخش ۳۵](35-insurance-panel.md).

مجوزهای کاربر بیمه از همان لیست مجوزها (`GET /api/admin/users/{user}/permissions`) دیده می‌شود؛ گروه جدید «پنل بیمه: شرکت‌ها و بارنامه‌ها» (`insurance.*`) فقط برای نقش `insurance` معنا دارد.
