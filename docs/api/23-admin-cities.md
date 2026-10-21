# شهرها (ادمین)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل ادمین — **تعداد روت:** ۵

مدیریت شهرها.

### ۲۳.۱ روت‌ها و مجوزها

| #    | متد        | مسیر                       | نوع ورودی    | مجوز لازم              | کاربرد     |
|------|------------|----------------------------|--------------|------------------------|------------|
| ۲۳.۱ | GET        | `/api/admin/cities`        | query string | `admin.cities.index`   | لیست شهرها |
| ۲۳.۲ | POST       | `/api/admin/cities`        | JSON         | `admin.cities.store`   | ایجاد شهر  |
| ۲۳.۳ | GET        | `/api/admin/cities/{city}` | query string | `admin.cities.show`    | جزئیات شهر |
| ۲۳.۴ | PUT\|PATCH | `/api/admin/cities/{city}` | JSON         | `admin.cities.update`  | ویرایش شهر |
| ۲۳.۵ | DELETE     | `/api/admin/cities/{city}` | ندارد        | `admin.cities.destroy` | حذف شهر    |

### ۲۳.۲ ورودی و خروجی

شهر: `name`!، `code`! (عدد یکتا)، `state_id`!، `tax_id`، `tax_ostan`، `anbar_code`.
