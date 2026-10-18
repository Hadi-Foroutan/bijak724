# کاربران شرکت و مجوزهای آن‌ها

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۷

مدیریت کاربران شرکت. گروه `user.users.*` مجوز غیرپیش‌فرض است.

### ۷.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۷.۱ | GET | `/api/user/users` | query string | `user.users.index` | کاربران شرکت |
| ۷.۲ | POST | `/api/user/users` | multipart/form-data | `user.users.store` | ایجاد کاربر (multipart، تصاویر) |
| ۷.۳ | GET | `/api/user/users/{user}` | query string | `user.users.show` | جزئیات کاربر |
| ۷.۴ | POST | `/api/user/users/{user}` | multipart/form-data | `user.users.update` | ویرایش کاربر (POST تا multipart کار کند) |
| ۷.۵ | DELETE | `/api/user/users/{user}` | ندارد | `user.users.destroy` | حذف کاربر |
| ۷.۶ | GET | `/api/user/{user}/permissions` | query string | `user.permissions.index` | مجوزهای کاربر شرکت |
| ۷.۷ | PUT | `/api/user/{user}/permissions` | JSON | `user.permissions.update` | جایگزینی مجوزهای کاربر شرکت |

### ۷.۲ ورودی و خروجی

هنگام آپلود تصویر از `multipart/form-data` استفاده کنید؛ ویرایش با `POST /user/users/{user}` انجام می‌شود.

| فیلد | نوع | قواعد |
| --- | --- | --- |
| `first_name`!، `last_name`! | string | حداکثر ۲۵۵ |
| `national_code`! | string | یکتا میان همهٔ کاربران |
| `username`! | string | یکتا |
| `password`! (فقط ایجاد) | string | حداقل ۷؛ در ویرایش برای حفظ رمز، ارسال نکنید |
| `min_commission_percentage`!، `max_commission_percentage`! | number | ۰ تا ۱۰۰ |
| `phone`، `email` | string | یکتا؛ قالب ایمیل |
| `parent_id` | int | یک کاربر موجود (سرپرست) |
| `print_name`، `address`، `description` | string |  |
| `profile_image`، `signature_image` | file | jpg، jpeg، png، webp؛ حداکثر ۵۱۲۰ کیلوبایت |
| `remove_profile_image`، `remove_signature_image` | bool | حذف تصویر ذخیره‌شده |
| `status` | enum | `UserStatus` |

پاسخ (`UserResource`): `id, company_id, parent_id, parent{id, full_name, print_name}, role, full_name, first_name, last_name, print_name, national_code, phone, email, username, min_commission_percentage, max_commission_percentage, address, profile_image_url, signature_image_url, description, status`.

مجوزها: `GET /user/{user}/permissions` مجوزهای کاربر را برمی‌گرداند؛ `PUT` با `{ permissions: number[] }` (شناسه‌ها، کلید `present`، می‌تواند `[]` باشد) آن‌ها را جایگزین می‌کند. فقط شناسه‌هایی پذیرفته می‌شوند که نقش `companyManager` دارد.
