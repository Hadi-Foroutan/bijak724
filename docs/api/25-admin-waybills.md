# بارنامه‌های همهٔ شرکت‌ها (ادمین)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل ادمین — **تعداد روت:** ۳

لیست بارنامه‌ها در همهٔ شرکت‌ها، سطل بازیافت (بارنامه‌های حذف‌شده) و بازگردانی.

### ۲۵.۱ روت‌ها و مجوزها

| #    | متد | مسیر                  | نوع ورودی    | مجوز لازم              | کاربرد                |
|------|-----|-----------------------|--------------|------------------------|-----------------------|
| ۲۵.۱ | GET | `/api/admin/waybills` | query string | `admin.waybills.index` | بارنامه‌های همهٔ شرکت‌ها |
| ۲۵.۳ | GET | `/api/admin/waybills/trashed` | query string | `admin.waybills.trashed` | بارنامه‌های حذف‌شده (سطل بازیافت) |
| ۲۵.۴ | PATCH | `/api/admin/waybills/{id}/restore` | ندارد | `admin.waybills.restore` | بازگرداندن بارنامهٔ حذف‌شده |

### ۲۵.۲ ورودی و خروجی

فیلترها: `company_id`، `company_name`، `status`، `created_at_from`، `created_at_to`، `serial_number`، `referral_number`،
`bijak_tracking_code` به‌علاوهٔ دستور لیست مشترک (بخش ۱).

### ۲۵.۳ بارنامه‌های حذف‌شده و بازگردانی

کاربر شرکت فقط بارنامهٔ **ناتمام** (`incomplete`) را حذف می‌کند (`DELETE /api/user/waybills/{id}`؛ برای وضعیت‌های دیگر `422`).
حذف **نرم** است: داده و محموله‌ها پاک نمی‌شوند، بارنامه فقط از لیست شرکت و از `GET /api/admin/waybills` کنار می‌رود.

- `GET /api/admin/waybills/trashed` همان فیلترها و خروجی لیست عادی را دارد (`company_id`، `company_name`، `status`، `created_at_from/to`،
  `serial_number`، `paginate` ...) و فقط بارنامه‌های حذف‌شده را برمی‌گرداند. فیلد `deleted_at` زمان حذف را نشان می‌دهد.
- `PATCH /api/admin/waybills/{id}/restore` بارنامه را به شرکت برمی‌گرداند. `{id}` همان `id` ردیف لیست ادمین است (نه `waybill_id`).
  پاسخ، همان ردیف با `deleted_at: null` است. اگر `id` وجود نداشته باشد یا بارنامه حذف‌شده نباشد `404` می‌دهد.
