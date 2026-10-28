# ۲۹ — وب‌سوکت (Reverb): اتصال، کانال‌ها و رویدادها

[← فهرست بخش‌ها](README.md)

همهٔ پیام‌های لحظه‌ای پروژه (اعلان‌ها و وضعیت ساخت گزارش‌ها) با **Laravel Reverb** ارسال می‌شوند. Reverb از پروتکل **Pusher** استفاده
می‌کند، پس در فرانت از `laravel-echo` + `pusher-js` استفاده کنید. این فایل همه‌چیز را یک‌جا دارد: آدرس‌ها، احراز هویت،
کانال‌ها، رویدادها، کد React و روش تست.

## ۱. آدرس‌ها و پورت‌ها

| مورد | محیط توسعه | محیط واقعی (پیشنهادی) |
|------|-----------|------------------------|
| سرور API | `http://localhost:8000` | `https://api.example.com` |
| پایهٔ API (`API_BASE`) | `http://localhost:8000/api` | `https://api.example.com/api` |
| سرور وب‌سوکت (Reverb) | `ws://localhost:8080` | `ws://api.example.com:8080` (همان دامنه، پورت جدا) |
| احراز هویت کانال خصوصی | `POST http://localhost:8000/api/broadcasting/auth` | `POST https://api.example.com/api/broadcasting/auth` |
| آدرس خام اتصال | `ws://localhost:8080/app/{APP_KEY}` | `ws://api.example.com:8080/app/{APP_KEY}` |

مقدارهای `REVERB_APP_KEY`، `REVERB_HOST`، `REVERB_PORT` و `REVERB_SCHEME` را بک‌اند می‌دهد (همان‌هایی که در `.env` سرور هست).
`REVERB_APP_SECRET` **سری است و هرگز در فرانت قرار نمی‌گیرد**؛ فقط `APP_KEY` عمومی است.

متغیرهای محیطی فرانت (Vite):

```env
VITE_API_BASE=http://localhost:8000/api
VITE_REVERB_APP_KEY=<REVERB_APP_KEY از بک‌اند>
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

> در Create React App به‌جای `VITE_` از پیشوند `REACT_APP_` و `process.env.REACT_APP_...` استفاده کنید.

## ۲. احراز هویت

همهٔ کانال‌های پروژه **خصوصی** هستند (`private-...`). اتصال دو مرحله دارد:

1. کلاینت به Reverb وصل می‌شود و یک `socket_id` می‌گیرد.
2. برای هر کانال خصوصی، Echo خودش `POST /api/broadcasting/auth` را با **همان توکن Sanctum** (هدر `Authorization: Bearer ...`) صدا می‌زند.
   بدنه (Echo خودکار می‌فرستد): `socket_id` و `channel_name`. اگر کاربر اجازه داشته باشد پاسخ `200` با `{"auth": "key:signature"}`
   می‌آید، وگرنه `403`.

توکن همان توکنی است که با لاگین می‌گیرید ([03-auth.md](03-auth.md)). اگر توکن عوض شد (لاگین دوباره، «ورود به‌عنوان شرکت») باید
اتصال Echo را با توکن جدید دوباره بسازید.

## ۳. کانال‌ها

| کانال (در Echo) | نام واقعی | چه کسی می‌تواند عضو شود | کاربرد |
|------------------|-----------|---------------------------|--------|
| `Echo.private("notifications")` | `private-notifications` | هر کاربر لاگین‌شده | اعلان عمومی ادمین برای همه |
| `Echo.private(\`users.${userId}.notifications\`)` | `private-users.{userId}.notifications` | فقط خود همان کاربر | اعلان اختصاصی کاربر |
| `Echo.private(\`companies.${companyId}.notifications\`)` | `private-companies.{companyId}.notifications` | کاربران همان شرکت (`user.company_id` برابر `companyId`) | اعلان اختصاصی شرکت |
| `Echo.private(\`users.${userId}.reports\`)` | `private-users.{userId}.reports` | فقط خود همان کاربر | وضعیت ساخت گزارش‌های خودش (صف، در حال ساخت، تکمیل، خطا) |

نکته‌ها:

- `userId` و `companyId` را از پروفایل کاربر لاگین‌شده بردارید (`GET /api/user/profile`، یا پاسخ لاگین).
- گزارش‌ها عمداً روی کانال **کاربر** می‌روند، نه شرکت، چون پیام لینک دانلود دارد و نباید به بقیهٔ کاربران شرکت برسد.
- با پیشوند `private-` در Echo کار نکنید؛ `Echo.private("x")` خودش `private-x` را می‌سازد.

## ۴. رویدادها و ساختار پیام

نام رویدادها با `broadcastAs` تعیین شده‌اند، پس در Echo با **نقطهٔ ابتدایی** گوش می‌دهیم (`.notification.sent`).

### ۴.۱ اعلان — `notification.sent`

کانال‌ها: `notifications`، `users.{id}.notifications`، `companies.{id}.notifications`

```json
{
  "notification": {
    "id": 42,
    "type": "warning",
    "should_remove_previous": true,
    "message": "سامانه ساعت ۲۴ بروزرسانی می‌شود",
    "is_private": false,
    "created_at": "2026-10-17T08:30:00.000000Z"
  }
}
```

- `type`: `success | error | warning | info` (سبک نمایش).
- `should_remove_previous: true` یعنی قبل از نمایش، اعلان‌های قبلی را پاک کنید.
- `is_private`: `false` عمومی، `true` اختصاصی (کاربر/شرکت).

تاریخچه: `GET /api/user/notifications` ([08-notifications.md](08-notifications.md)). هنگام شروع برنامه تاریخچه را بخوانید و بعد
رویدادهای زنده را به آن اضافه کنید.

### ۴.۲ گزارش — `report-export.{status}`

کانال: `users.{userId}.reports`. چهار رویداد (چهار نام جدا) ارسال می‌شود:

| نام رویداد در Echo | زمان |
|---------------------|------|
| `.report-export.queued` | درخواست ثبت و در صف قرار گرفت |
| `.report-export.processing` | صف شروع به ساخت فایل کرد (در حال آماده‌سازی) |
| `.report-export.completed` | فایل ساخته شد؛ `download_url` موجود است |
| `.report-export.failed` | ساخت ناموفق بود؛ `error` علت را دارد |

بدنهٔ همهٔ آن‌ها یکسان است:

```json
{
  "company_id": 3,
  "export": {
    "id": 7,
    "report_definition_id": 2,
    "name": "گزارش ماهانه",
    "format": "xlsx",
    "status": "completed",
    "status_label": "تکمیل شده",
    "fields": ["bijak_number", "sender_full_name"],
    "filters": { "eq-status": "completed" },
    "rows_count": 120,
    "file_size": 20480,
    "error": null,
    "started_at": "2026-10-26T09:00:01+00:00",
    "completed_at": "2026-10-26T09:00:05+00:00",
    "expires_at": "2026-10-26T17:00:05+00:00",
    "created_at": "2026-10-26T09:00:00+00:00",
    "download_url": "https://api.example.com/report-exports/7/download?expires=...&signature=..."
  }
}
```

- `download_url` فقط در `completed` پر است و تا `expires_at` (پیش‌فرض ۸ ساعت، تنظیم ادمین) معتبر است؛ بدون توکن مستقیم باز می‌شود.
- پس از انقضا فایل پاک می‌شود و وضعیت `expired` می‌شود (برای این وضعیت رویداد سوکتی نمی‌آید؛ فقط در لیست `GET /api/user/report-exports` دیده می‌شود).
- شناسهٔ `export.id` را با لیست گزارش‌های اخیر تطبیق دهید: اگر رکورد در لیست هست آپدیتش کنید، وگرنه به ابتدای لیست اضافه کنید.

جزئیات REST گزارش‌ها: [28-report-builder.md](28-report-builder.md).

## ۵. نصب و پیکربندی کلاینت

```bash
npm i laravel-echo pusher-js
```

### ۵.۱ فایل `echo.js` (یک نمونهٔ مشترک)

```js
// src/realtime/echo.js
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const API_BASE = import.meta.env.VITE_API_BASE; // مثلا http://localhost:8000/api

export function createEcho(token) {
  return new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT),
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint: `${API_BASE}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
    },
  });
}
```

### ۵.۲ هوک اتصال و Provider در React

یک اتصال برای کل برنامه، با توکن فعلی. با خروج کاربر یا تغییر توکن قطع و دوباره ساخته می‌شود.

```jsx
// src/realtime/RealtimeProvider.jsx
import { createContext, useContext, useEffect, useMemo, useState } from "react";
import { createEcho } from "./echo";

const RealtimeContext = createContext(null);
export const useEcho = () => useContext(RealtimeContext);

export function RealtimeProvider({ token, children }) {
  const [echo, setEcho] = useState(null);

  useEffect(() => {
    if (!token) return;

    const instance = createEcho(token);
    setEcho(instance);

    return () => {
      instance.disconnect(); // قطع کامل هنگام خروج/تغییر توکن
      setEcho(null);
    };
  }, [token]);

  return <RealtimeContext.Provider value={echo}>{children}</RealtimeContext.Provider>;
}
```

```jsx
// src/main.jsx
<RealtimeProvider token={auth.token}>
  <App />
</RealtimeProvider>
```

### ۵.۳ هوک عمومی گوش‌دادن به یک کانال

```jsx
// src/realtime/useChannel.js
import { useEffect, useRef } from "react";
import { useEcho } from "./RealtimeProvider";

/**
 * useChannel(`users.${userId}.reports`, { ".report-export.completed": handler, ... })
 * کلیدها همان نام رویداد با نقطهٔ ابتدایی‌اند.
 */
export function useChannel(channelName, handlers) {
  const echo = useEcho();
  const handlersRef = useRef(handlers);
  handlersRef.current = handlers; // همیشه آخرین handler، بدون ساخت دوبارهٔ اشتراک

  useEffect(() => {
    if (!echo || !channelName) return;

    const channel = echo.private(channelName);
    const events = Object.keys(handlersRef.current);

    events.forEach((event) => {
      channel.listen(event, (payload) => handlersRef.current[event]?.(payload));
    });

    return () => {
      events.forEach((event) => channel.stopListening(event));
      echo.leave(channelName);
    };
  }, [echo, channelName]);
}
```

### ۵.۴ اعلان‌ها (سه کانال)

```jsx
// src/realtime/useNotifications.js
import { useState, useCallback } from "react";
import { useChannel } from "./useChannel";

export function useNotifications(user) {
  const [items, setItems] = useState([]); // تاریخچه را از GET /user/notifications اینجا مقداردهی کنید

  const onSent = useCallback(({ notification }) => {
    setItems((prev) => (notification.should_remove_previous ? [notification] : [notification, ...prev]));
  }, []);

  const handlers = { ".notification.sent": onSent };

  useChannel("notifications", handlers);
  useChannel(user ? `users.${user.id}.notifications` : null, handlers);
  useChannel(user?.company_id ? `companies.${user.company_id}.notifications` : null, handlers);

  return items;
}
```

### ۵.۵ گزارش‌های اخیر (وضعیت لحظه‌ای + دانلود)

```jsx
// src/reports/RecentExports.jsx
import { useEffect, useState, useCallback } from "react";
import { useChannel } from "../realtime/useChannel";

const API_BASE = import.meta.env.VITE_API_BASE;

export function RecentExports({ user, token }) {
  const [exports, setExports] = useState([]);

  // ۱) فهرست اولیه
  useEffect(() => {
    fetch(`${API_BASE}/user/report-exports?paginate=1&itemsPerPage=20`, {
      headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
    })
      .then((r) => r.json())
      .then((json) => setExports(json.data.data ?? json.data));
  }, [token]);

  // ۲) هر رویداد: اگر هست آپدیت، وگرنه اضافه به ابتدای لیست
  const upsert = useCallback(({ export: item }) => {
    setExports((prev) =>
      prev.some((e) => e.id === item.id)
        ? prev.map((e) => (e.id === item.id ? item : e))
        : [item, ...prev],
    );
  }, []);

  useChannel(user ? `users.${user.id}.reports` : null, {
    ".report-export.queued": upsert,
    ".report-export.processing": upsert,
    ".report-export.completed": (payload) => {
      upsert(payload);
      // اینجا می‌توانید toast نشان دهید: «گزارش آماده شد»
    },
    ".report-export.failed": upsert,
  });

  return (
    <ul>
      {exports.map((e) => (
        <li key={e.id}>
          {e.name} ({e.format}) — {e.status_label}
          {e.status === "failed" && <em> {e.error}</em>}
          {e.download_url && (
            <a href={e.download_url} download>
              دانلود
            </a>
          )}
        </li>
      ))}
    </ul>
  );
}
```

درخواست خروجی (ثبت در صف):

```js
await fetch(`${API_BASE}/user/reports/${reportId}/exports`, {
  method: "POST",
  headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json", Accept: "application/json" },
  body: JSON.stringify({ format: "xlsx", filters: { "eq-status": "completed" } }),
}); // پاسخ 202؛ ادامه را سوکت خبر می‌دهد
```

## ۶. رفتار اتصال و خطاها

| وضعیت | معنی | کار فرانت |
|--------|------|-----------|
| `403` روی `/broadcasting/auth` | کاربر اجازهٔ این کانال را ندارد (مثلا `userId` دیگری) | شناسهٔ درست را بدهید؛ دوباره تلاش نکنید |
| `401` روی `/broadcasting/auth` | توکن نامعتبر/منقضی | لاگین دوباره، سپس ساخت دوبارهٔ Echo |
| قطع اتصال شبکه | Echo/Pusher خودش دوباره وصل می‌شود | هنگام وصل مجدد، لیست‌ها (اعلان‌ها و گزارش‌های اخیر) را یک‌بار از REST بخوانید، چون پیام‌های زمان قطع بودن از دست می‌روند |
| نوع پیام ندیدن | نام رویداد بدون نقطهٔ ابتدایی | `".report-export.completed"` نه `"report-export.completed"` |

وضعیت اتصال: `echo.connector.pusher.connection.bind("state_change", ({ current }) => ...)` (`connected`، `connecting`، `unavailable`، `disconnected`).

## ۷. تست دستی

### ۷.۱ صفحهٔ تست مستقل (بدون React)

فایل زیر را به‌صورت `socket-test.html` ذخیره و در مرورگر باز کنید. توکن، شناسهٔ کاربر و کلید را وارد کنید و «اتصال» را بزنید؛
هر پیام در لاگ چاپ می‌شود.

```html
<!doctype html>
<meta charset="utf-8" />
<title>Bijak socket test</title>
<style>body{font-family:sans-serif;max-width:720px;margin:2rem auto}input{width:100%;margin:.25rem 0;padding:.4rem}pre{background:#111;color:#0f0;padding:1rem;height:320px;overflow:auto}</style>
<h3>تست سوکت بیجک</h3>
<input id="api" value="http://localhost:8000/api" placeholder="API base" />
<input id="host" value="localhost" placeholder="REVERB_HOST" />
<input id="port" value="8080" placeholder="REVERB_PORT" />
<input id="key" placeholder="REVERB_APP_KEY" />
<input id="token" placeholder="Bearer token" />
<input id="userId" placeholder="user id" />
<input id="companyId" placeholder="company id (اختیاری)" />
<button id="go">اتصال</button>
<pre id="log"></pre>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.4.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
  const $ = (id) => document.getElementById(id);
  const log = (...a) => ($("log").textContent += a.map((x) => (typeof x === "string" ? x : JSON.stringify(x, null, 2))).join(" ") + "\n");

  $("go").onclick = () => {
    window.Pusher = Pusher;
    const echo = new Echo({
      broadcaster: "reverb",
      key: $("key").value,
      wsHost: $("host").value,
      wsPort: Number($("port").value),
      wssPort: Number($("port").value),
      forceTLS: false,
      enabledTransports: ["ws", "wss"],
      authEndpoint: $("api").value + "/broadcasting/auth",
      auth: { headers: { Authorization: "Bearer " + $("token").value, Accept: "application/json" } },
    });

    echo.connector.pusher.connection.bind("state_change", (s) => log("state:", s.current));

    const listen = (name, events) => {
      const ch = echo.private(name);
      ch.subscribed(() => log("subscribed:", "private-" + name));
      ch.error((e) => log("ERROR on", name, e));
      events.forEach((ev) => ch.listen(ev, (p) => log(name, ev, p)));
    };

    const notif = [".notification.sent"];
    const reports = [".report-export.queued", ".report-export.processing", ".report-export.completed", ".report-export.failed"];
    listen("notifications", notif);
    listen(`users.${$("userId").value}.notifications`, notif);
    listen(`users.${$("userId").value}.reports`, reports);
    if ($("companyId").value) listen(`companies.${$("companyId").value}.notifications`, notif);
  };
</script>
```

### ۷.۲ اجرای سرویس‌ها در سرور (توسعه)

```bash
php artisan serve                       # API روی 8000
php artisan reverb:start --debug        # سوکت روی 8080
php artisan queue:work --queue=reports,default   # ساخت گزارش‌ها
```

### ۷.۳ فرستادن پیام آزمایشی

اعلان عمومی (نیاز به توکن ادمین):

```bash
curl -X POST http://localhost:8000/api/admin/notifications \
  -H "Authorization: Bearer ADMIN_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"type":"info","message":"پیام آزمایشی","should_remove_previous":false}'
```

اعلان اختصاصی: به بدنه `"user_id": 5` یا `"company_id": 3` اضافه کنید (فقط یکی).

گزارش: یک گزارش بسازید و خروجی بخواهید، سپس سه پیام `queued` ← `processing` ← `completed` را در صفحهٔ تست ببینید:

```bash
curl -X POST http://localhost:8000/api/user/reports \
  -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"تست","fields":["bijak_number","sender_full_name","status"]}'

curl -X POST http://localhost:8000/api/user/reports/1/exports \
  -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"format":"xlsx"}'
```

اگر `queued` آمد ولی `processing` نیامد، worker صف (`queue:work --queue=reports`) اجرا نیست.

### ۷.۴ عیب‌یابی

| علامت | علت محتمل |
|-------|-----------|
| هیچ اتصالی برقرار نمی‌شود | `reverb:start` اجرا نیست، یا `host/port` اشتباه است |
| `403` در auth | `userId`/`companyId` متعلق به کاربر لاگین‌شده نیست |
| متصل ولی پیام نمی‌آید | نام رویداد بدون نقطه، یا کانال اشتباه، یا `BROADCAST_CONNECTION` در سرور `reverb` نیست |
| روی HTTPS خطای mixed content | `forceTLS: true` و `wss://` را فعال کنید (`VITE_REVERB_SCHEME=https`) |
| در پروداکشن بعد از چند ثانیه قطع می‌شود | پروکسی (Nginx) باید هدرهای `Upgrade`/`Connection` را عبور دهد |

## ۸. نکتهٔ استقرار (برای DevOps)

- Reverb روی همان دامنهٔ برنامه و با یک پورت جدا اجرا می‌شود (بدون پروکسی و بدون ساب‌دامین): مثلاً `api.example.com:8080`.
  در `.env` سرور: `REVERB_HOST=api.example.com`، `REVERB_PORT=8080`، `REVERB_SCHEME=http` (یا `https` اگر خود Reverb TLS دارد).
- پروسه را با `php artisan reverb:start` (ترجیحاً با Supervisor) بالا نگه دارید و پورت انتخابی را در فایروال باز کنید.
- فرانت با همان `host` و `port` وصل می‌شود: `ws://api.example.com:8080/app/{APP_KEY}`. اگر سایت روی `https` است مرورگر اتصال
  `ws://` را به‌عنوان mixed content مسدود می‌کند؛ در آن حالت Reverb باید خودش TLS داشته باشد (`wss`) یا از پروکسی استفاده شود.
- Worker صف گزارش‌ها جداست (`queue:work --queue=reports`) و scheduler برای پاک‌سازی فایل‌ها لازم است ([28-report-builder.md](28-report-builder.md)).
- اگر چند سرور Reverb دارید، `REVERB_SCALING_ENABLED=true` (به Redis نیاز دارد).

## لاگ اتصال‌ها در ترمینال سرور

با `php artisan reverb:start` (و در لاگ `storage/logs/reverb.log` وقتی با cron بالا می‌آید) هر اتصال چاپ می‌شود:

```
[2026-10-28 16:24:50] connected · socket 38124259.75973579 · origin: https://app.example.com · online: 1
[2026-10-28 16:24:51] user subscribed · socket 38124259.75973579 · user #12 (علی احمدی) company #3 · channel: private-users.12.reports · online: 1
[2026-10-28 16:24:52] disconnected · socket 38124259.75973579 · user #12 (علی احمدی) company #3 · online: 0
```

شناسهٔ کاربر از کانال خصوصی او (`private-users.{id}.reports`) خوانده می‌شود، و فقط وقتی چاپ می‌شود که اشتراک واقعاً تأیید شده باشد.
`online` تعداد اتصال‌های باز است. کلاس: `app/Support/Reverb/LoggingPusherServer.php`.
