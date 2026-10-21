# گروه‌های کالا

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۳

گروه‌های کالا برای تعرفهٔ بیمه.

### ۱۴.۱ روت‌ها و مجوزها

| #    | متد | مسیر                                         | نوع ورودی    | مجوز لازم                         | کاربرد                      |
|------|-----|----------------------------------------------|--------------|-----------------------------------|-----------------------------|
| ۱۴.۱ | GET | `/api/user/cargo-groups`                     | query string | `user.cargo-groups.index`         | گروه‌های کالا (کلاس‌های بیمه) |
| ۱۴.۲ | GET | `/api/user/cargo-groups/{cargoGroup}`        | query string | `user.cargo-groups.show`          | جزئیات گروه                 |
| ۱۴.۳ | PUT | `/api/user/cargo-groups/{cargoGroup}/cargos` | JSON         | `user.cargo-groups-cargos.update` | جایگزینی کالاهای گروه       |

### ۱۴.۲ ورودی و خروجی

گروه‌های فقط‌خواندنی (`id, name, group_number, status, cargos[]`) که در تعرفه‌های بیمه استفاده می‌شوند.
`PUT /{cargoGroup}/cargos` با `{ cargo_ids: number[] }` کالاهای گروه را جایگزین می‌کند. این مقادیر **کد** کالا از
`/user/general/cargos` هستند، نه شناسه، و نباید تکراری باشند.
