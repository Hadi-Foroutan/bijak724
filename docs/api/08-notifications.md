# اعلان‌ها (کاربر شرکت)

[← فهرست بخش‌ها](README.md)

**پنل:** پنل شرکت — **تعداد روت:** ۲

تاریخچهٔ اعلان‌ها و دریافت اعلان لحظه‌ای.

### ۸.۱ روت‌ها و مجوزها

| #   | متد | مسیر                                     | نوع ورودی    | مجوز لازم                  | کاربرد       |
|-----|-----|------------------------------------------|--------------|----------------------------|--------------|
| ۸.۱ | GET | `/api/user/notifications`                | query string | `user.notifications.index` | اعلان‌های من  |
| ۸.۲ | GET | `/api/user/notifications/{notification}` | query string | `user.notifications.show`  | جزئیات اعلان |

### ۸.۲ ورودی و خروجی

ادمین‌ها اعلان‌ها را از طریق Laravel Reverb (پروتکل Pusher) روی یک کانال خصوصی به نام `notifications` پخش می‌کنند که
همهٔ کاربران احراز هویت‌شده می‌توانند به آن بپیوندند. نام رویداد `notification.sent` است.

| مورد             | مقدار                                                                                       |
|------------------|---------------------------------------------------------------------------------------------|
| سرور             | Reverb: `VITE_REVERB_APP_KEY`، `VITE_REVERB_HOST`، `VITE_REVERB_PORT`، `VITE_REVERB_SCHEME` |
| احراز هویت کانال | `POST /api/broadcasting/auth` با bearer token                                               |
| کانال            | `private-notifications` (در Echo: `Echo.private("notifications")`)                          |
| رویداد           | `.notification.sent` (نقطهٔ ابتدایی در Echo لازم است، چون از `broadcastAs` استفاده شده)      |
| ارسال‌کننده       | `POST /api/admin/notifications` با `{ type, message, should_remove_previous? }`             |

محتوای رویداد:

```json
{
  "notification": {
    "id": 42,
    "type": "warning",
    "should_remove_previous": true,
    "message": "...",
    "created_at": "2026-10-17T08:30:00.000000Z"
  }
}
```

پیکربندی کلاینت:

```ts
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;
export const echo = new Echo({
  broadcaster: "reverb",
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT),
  forceTLS: import.meta.env.VITE_REVERB_SCHEME === "https",
  enabledTransports: ["ws", "wss"],
  authEndpoint: `${API_BASE}/broadcasting/auth`,
  auth: { headers: { Authorization: `Bearer ${token}` } },
});

echo.private("notifications").listen(".notification.sent", ({ notification }) => {
  if (notification.should_remove_previous) clearNotifications();
  pushNotification(notification);
});
```

اگر `should_remove_previous` برابر true بود، پیش از نمایش اعلان جدید اعلان‌های قبلی را حذف کنید. سبک نمایش از `type`
تبعیت می‌کند (`success`، `error`، `warning`، `info`). endpointهای تاریخچه همین داده را همراه با `type_label` و
`sender{id, full_name}` برمی‌گردانند: `GET /user/notifications` برای کاربران و `GET /admin/notifications` برای ادمین‌ها
که `username` و `email` فرستنده را هم دارد. هنگام شروع، تاریخچه را بارگذاری کنید و سپس رویدادهای زنده را به آن اضافه
کنید.
