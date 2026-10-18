# طرفین حمل و آدرس‌ها

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۱۲

فرستنده/گیرنده و آدرس‌های آن‌ها.

### ۱۲.۱ روت‌ها و مجوزها

| # | متد | مسیر | نوع ورودی | مجوز لازم | کاربرد |
| --- | --- | --- | --- | --- | --- |
| ۱۲.۱ | GET | `/api/user/shipment-parties` | query string | `user.shipment-parties.index` | فرستندگان و گیرندگان |
| ۱۲.۲ | POST | `/api/user/shipment-parties` | JSON | `user.shipment-parties.store` | ایجاد طرف حمل |
| ۱۲.۳ | GET\|POST | `/api/user/shipment-parties/inquiry` | query یا JSON | `user.shipment-parties.inquiry` | یافتن طرف حمل با شناسهٔ ملی |
| ۱۲.۴ | GET\|POST | `/api/user/shipment-parties/addresses/inquiry` | query یا JSON | `user.shipment-parties.addresses-inquiry` | یافتن آدرس |
| ۱۲.۵ | GET | `/api/user/shipment-parties/{shipmentParty}` | query string | `user.shipment-parties.show` | جزئیات طرف حمل |
| ۱۲.۶ | PUT\|PATCH | `/api/user/shipment-parties/{shipmentParty}` | JSON | `user.shipment-parties.update` | ویرایش طرف حمل |
| ۱۲.۷ | DELETE | `/api/user/shipment-parties/{shipmentParty}` | ندارد | `user.shipment-parties.destroy` | حذف طرف حمل |
| ۱۲.۸ | GET | `/api/user/shipment-parties/{shipmentParty}/addresses` | query string | `user.addresses.index` | آدرس‌های یک طرف حمل |
| ۱۲.۹ | POST | `/api/user/shipment-parties/{shipmentParty}/addresses` | JSON | `user.addresses.store` | افزودن آدرس |
| ۱۲.۱۰ | GET | `/api/user/shipment-parties/{shipmentParty}/addresses/{address}` | query string | `user.addresses.show` | جزئیات آدرس |
| ۱۲.۱۱ | PUT\|PATCH | `/api/user/shipment-parties/{shipmentParty}/addresses/{address}` | JSON | `user.addresses.update` | ویرایش آدرس |
| ۱۲.۱۲ | DELETE | `/api/user/shipment-parties/{shipmentParty}/addresses/{address}` | ندارد | `user.addresses.destroy` | حذف آدرس |

### ۱۲.۲ ورودی و خروجی

طرف حمل: `national_identifier`! (یکتا در شرکت، حداکثر ۲۰)، `is_sender`!، `is_receiver`! (حداقل یکی true)، `first_name`!، `last_name`!، `title`، `mobile`، `landline`، `intermediary_code`، `transportation_code`، `email`، `description`، `status`. پاسخ شامل `full_name` و `addresses[]` است.

آدرس — `/user/shipment-parties/{id}/addresses`: `postal_code`! (یکتا برای هر طرف حمل)، `city_code`!، `address`!، `phone` (حداکثر ۱۱)، `description`. پاسخ شامل `city` است.

استعلام‌ها:

- `/inquiry`: `national_code` (۱۰ یا ۱۱ رقم) و `type` (`sender` یا `receiver`).
- `/addresses/inquiry`: `postal_code` و `type`.

هر دو رکورد فعالِ منطبق را برمی‌گردانند، یا خطای فارسی که رکوردهای غیرفعال را هم گزارش می‌کند.
