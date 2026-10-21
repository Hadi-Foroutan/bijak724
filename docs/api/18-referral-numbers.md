# بازه‌های شمارهٔ حواله و حواله‌های ابطال‌شده

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۷

بازه‌های شمارهٔ حواله؛ فیلدها مثل بازهٔ بیجک است (بخش قبل).

### ۱۸.۱ روت‌ها و مجوزها

| #    | متد        | مسیر                                          | نوع ورودی    | مجوز لازم                       | کاربرد                  |
|------|------------|-----------------------------------------------|--------------|---------------------------------|-------------------------|
| ۱۸.۱ | GET        | `/api/user/referral-numbers`                  | query string | `user.referral-numbers.index`   | بازه‌های شمارهٔ حواله     |
| ۱۸.۲ | POST       | `/api/user/referral-numbers`                  | JSON         | `user.referral-numbers.store`   | افزودن بازه             |
| ۱۸.۳ | POST       | `/api/user/referral-numbers/inquiry`          | JSON         | `user.referral-numbers.inquiry` | شمارهٔ حوالهٔ آزاد بعدی   |
| ۱۸.۴ | GET        | `/api/user/referral-numbers/{referralNumber}` | query string | `user.referral-numbers.show`    | جزئیات بازه             |
| ۱۸.۵ | PUT\|PATCH | `/api/user/referral-numbers/{referralNumber}` | JSON         | `user.referral-numbers.update`  | ویرایش بازه             |
| ۱۸.۶ | DELETE     | `/api/user/referral-numbers/{referralNumber}` | ندارد        | `user.referral-numbers.destroy` | حذف بازه                |
| ۱۸.۷ | GET        | `/api/user/canceled-referrals`                | query string | `user.canceled-referrals.index` | شماره‌های حوالهٔ ابطال‌شده |

### ۱۸.۲ ورودی و خروجی

فیلدها و استعلام این بخش مثل «بازه‌های شمارهٔ بیجک» است. استعلام حواله `{ waybill_id }` می‌گیرد.
`GET /user/canceled-referrals` شماره‌های حوالهٔ ابطال‌شده را با `waybill_snapshot` و `cargos_snapshot` برمی‌گرداند.
