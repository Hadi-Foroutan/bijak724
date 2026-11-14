# مستند API بیجک برای فرانت‌اند

هر بخش در یک فایل جداست و شامل روت‌ها، نوع ورودی، مجوز (permission) هر روت، فیلدهای ورودی و خروجی است. نام مجوز همان نام
مسیر (route name) است؛ روت‌های «بدون مجوز» فقط توکن معتبر می‌خواهند. علامت `!` کنار فیلد یعنی در ایجاد الزامی است.

| #  | بخش                                     | فایل                                                           |
|----|-----------------------------------------|----------------------------------------------------------------|
| ۱  | قراردادهای کلی                          | [01-general-conventions.md](01-general-conventions.md)         |
| ۲  | مجوزها و نقش‌ها                          | [02-permissions.md](02-permissions.md)                         |
| ۳  | احراز هویت                              | [03-auth.md](03-auth.md)                                       |
| ۴  | پروفایل من                              | [04-profile.md](04-profile.md)                                 |
| ۵  | داشبورد                                 | [05-dashboard.md](05-dashboard.md)                             |
| ۶  | تنظیمات شرکت                            | [06-settings.md](06-settings.md)                               |
| ۷  | کاربران شرکت و مجوزهای آن‌ها             | [07-company-users.md](07-company-users.md)                     |
| ۸  | اعلان‌ها (کاربر شرکت)                    | [08-notifications.md](08-notifications.md)                     |
| ۹  | اطلاعات پایه (فقط‌خواندنی)               | [09-general-lookups.md](09-general-lookups.md)                 |
| ۱۰ | رانندگان و حساب‌های بانکی                | [10-drivers.md](10-drivers.md)                                 |
| ۱۱ | ناوگان                                  | [11-fleets.md](11-fleets.md)                                   |
| ۱۲ | طرفین حمل و آدرس‌ها                      | [12-shipment-parties.md](12-shipment-parties.md)               |
| ۱۳ | صاحبان کالا                             | [13-product-owners.md](13-product-owners.md)                   |
| ۱۴ | گروه‌های کالا                            | [14-cargo-groups.md](14-cargo-groups.md)                       |
| ۱۵ | بیمه‌ها و تعرفه‌ها                        | [15-insurances.md](15-insurances.md)                           |
| ۱۶ | قراردادهای حمل                          | [16-transport-contracts.md](16-transport-contracts.md)         |
| ۱۷ | بازه‌های شمارهٔ بیجک                      | [17-bijak-numbers.md](17-bijak-numbers.md)                     |
| ۱۸ | بازه‌های شمارهٔ حواله و حواله‌های ابطال‌شده | [18-referral-numbers.md](18-referral-numbers.md)               |
| ۱۹ | بارنامه‌ها (بیجک‌ها)                      | [19-waybills.md](19-waybills.md)                               |
| ۲۰ | شرکت‌ها (ادمین)                          | [20-admin-companies.md](20-admin-companies.md)                 |
| ۲۱ | کاربران (ادمین)                         | [21-admin-users.md](21-admin-users.md)                         |
| ۲۲ | نقش‌ها و مجوزها (ادمین)                  | [22-admin-roles-permissions.md](22-admin-roles-permissions.md) |
| ۲۳ | شهرها (ادمین)                           | [23-admin-cities.md](23-admin-cities.md)                       |
| ۲۴ | اعلان‌ها (ادمین)                         | [24-admin-notifications.md](24-admin-notifications.md)         |
| ۲۵ | بارنامه‌های همهٔ شرکت‌ها (ادمین)           | [25-admin-waybills.md](25-admin-waybills.md)                   |
| ۲۶ | Enumها                                  | [26-enums.md](26-enums.md)                                     |
| ۲۷ |                              مدیران فنی | [27-technical-managers.md](27-technical-managers.md)           |
| ۲۸ | گزارش‌ساز | [28-report-builder.md](28-report-builder.md) |
| ۲۹ | وب‌سوکت (اتصال، کانال‌ها، رویدادها، React) | [29-socket.md](29-socket.md) |
| ۳۰ | گزارش‌های پرکاربرد (قالب‌های آمادهٔ گزارش: بیمه و ...) | [30-frequent-reports.md](30-frequent-reports.md) |
| ۳۱ | داشبورد ادمین | [31-admin-dashboard.md](31-admin-dashboard.md) |
| ۳۲ | فیلدهای اضافی | [32-additional-fields.md](32-additional-fields.md) |
| ۳۳ | اطلاعات شرکت (پنل شرکت) | [33-company-profile.md](33-company-profile.md) |
| ۳۴ | نمایندگی‌های بیمه (ادمین؛ شامل ورود به پنل نمایندگی) | [34-insurance-agencies.md](34-insurance-agencies.md) |
| ۳۵ | پنل بیمه | [35-insurance-panel.md](35-insurance-panel.md) |
| ۳۶ | **راهنمای تغییرات نمایندگی بیمه برای فرانت (از اینجا شروع کنید)** | [36-insurance-frontend-handoff.md](36-insurance-frontend-handoff.md) |
| ۳۷ | اتصال نمایندهٔ بیمه به قرارداد بیمهٔ شرکت (تغییرات برای فرانت) | [37-insurance-agency-contract-connection.md](37-insurance-agency-contract-connection.md) |
| ۳۸ | سامانه پیامک، قالب‌ها، پیامک بارنامه و ارسال گروهی به رانندگان | [38-sms-panel.md](38-sms-panel.md) |
| ۳۹ | **راهنمای بخش پیامک برای فرانت (از اینجا شروع کنید)** | [39-sms-frontend-handoff.md](39-sms-frontend-handoff.md) |
