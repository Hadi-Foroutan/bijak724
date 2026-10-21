# نقش‌ها و مجوزها (ادمین)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل ادمین — **تعداد روت:** ۹

مدیریت نقش‌ها و مجوزها (هر مجوز = نام یک مسیر).

### ۲۲.۱ روت‌ها و مجوزها

| #    | متد        | مسیر                                  | نوع ورودی    | مجوز لازم                   | کاربرد                    |
|------|------------|---------------------------------------|--------------|-----------------------------|---------------------------|
| ۲۲.۱ | GET        | `/api/admin/permissions`              | query string | `admin.permissions.index`   | لیست مجوزها (گروه‌بندی‌شده) |
| ۲۲.۲ | POST       | `/api/admin/permissions`              | JSON         | `admin.permissions.store`   | ایجاد مجوز                |
| ۲۲.۳ | PUT\|PATCH | `/api/admin/permissions/{permission}` | JSON         | `admin.permissions.update`  | ویرایش مجوز               |
| ۲۲.۴ | DELETE     | `/api/admin/permissions/{permission}` | ندارد        | `admin.permissions.destroy` | حذف مجوز                  |
| ۲۲.۵ | GET        | `/api/admin/roles`                    | query string | `admin.roles.index`         | لیست نقش‌ها                |
| ۲۲.۶ | POST       | `/api/admin/roles`                    | JSON         | `admin.roles.store`         | ایجاد نقش                 |
| ۲۲.۷ | GET        | `/api/admin/roles/{role}`             | query string | `admin.roles.show`          | جزئیات نقش                |
| ۲۲.۸ | PUT\|PATCH | `/api/admin/roles/{role}`             | JSON         | `admin.roles.update`        | ویرایش نقش                |
| ۲۲.۹ | DELETE     | `/api/admin/roles/{role}`             | ندارد        | `admin.roles.destroy`       | حذف نقش                   |

### ۲۲.۲ ورودی و خروجی

نقش: `name`! (یکتا)، `display_name`!، `description`، `status`، `permissions`! (آرایهٔ شناسهٔ مجوزها). مجوز: `name`!
(یکتا، در قالب نام مسیر)، `display_name`!، `description`، `status`، `is_default`.
