# صاحبان کالا

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۵

مدیریت صاحبان کالا.

### ۱۳.۱ روت‌ها و مجوزها

| #    | متد        | مسیر                                      | نوع ورودی    | مجوز لازم                     | کاربرد          |
|------|------------|-------------------------------------------|--------------|-------------------------------|-----------------|
| ۱۳.۱ | GET        | `/api/user/product-owners`                | query string | `user.product-owners.index`   | صاحبان کالا     |
| ۱۳.۲ | POST       | `/api/user/product-owners`                | JSON         | `user.product-owners.store`   | ایجاد صاحب کالا |
| ۱۳.۳ | GET        | `/api/user/product-owners/{productOwner}` | query string | `user.product-owners.show`    | جزئیات          |
| ۱۳.۴ | PUT\|PATCH | `/api/user/product-owners/{productOwner}` | JSON         | `user.product-owners.update`  | ویرایش          |
| ۱۳.۵ | DELETE     | `/api/user/product-owners/{productOwner}` | ندارد        | `user.product-owners.destroy` | حذف             |

### ۱۳.۲ ورودی و خروجی

`name`!، `phone`! (حداکثر ۲۰)، `transportation_code`!.
