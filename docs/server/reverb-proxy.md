# راه‌اندازی پروکسی معکوس وب‌سوکت (Reverb) — راهنمای ادمین سرور

> این فایل را به ادمین (root) سرور بدهید. همهٔ دستورها با `root` اجرا می‌شود.
> نام دامنه: `api.bijak724.ir` — ریورب روی `127.0.0.1:9004` اجرا است (HTTP/WebSocket ساده، بدون TLS).
> هدف: مرورگر و Postman به `wss://api.bijak724.ir/app/...` (پورت ۴۴۳، بدون پورت اضافه) وصل شوند.

```
مرورگر ──wss:443──► nginx (TLS با گواهی دامنه) ──► http://127.0.0.1:9004 (Reverb)
```

هیچ پورت جدیدی باز نمی‌شود و فایروال تغییری نمی‌کند.

## ۰. پیش‌نیازها (قبل از هر تغییر، فقط بخوانید)

```bash
# ریورب باید روی 9004 گوش بدهد (php باشد، نه Apache و نه supervisord):
ss -ltnp | grep 9004

# ریورب را مستقیم تست کنید؛ باید «101 Switching Protocols» بدهد:
curl -i -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  "http://127.0.0.1:9004/app/08f2d479befe83e6be6fe43ccca0f6da?protocol=7&client=js&version=8.6.0"

# وب‌سرور جلوی سایت چیست؟ (پاسخ سایت «Server: nginx» است و پشتش Apache روی 8080 دارد)
nginx -v
nginx -T 2>/dev/null | grep -n "server_name .*api.bijak724.ir"
```

اگر `ss` چیزی نشان نداد، ریورب بالا نیست؛ اول آن را بالا بیاورید (در پوشهٔ پروژه: `php artisan reverb:start`).

## ۱. تنظیم nginx

دو `location` باید داخل **server بلوک ۴۴۳ (ssl)** دامنهٔ `api.bijak724.ir` و **قبل از** `location /` (که به Apache/PHP می‌دهد) باشند:

```nginx
# وب‌سوکت مرورگرها
location ^~ /app/ {
    proxy_pass http://127.0.0.1:9004;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 300s;
    proxy_send_timeout 300s;
    proxy_buffering off;
}

# API ارسال رویداد (Laravel → Reverb)
location ^~ /apps/ {
    proxy_pass http://127.0.0.1:9004;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

نکته‌های مهم (علت‌های رایج خطا):

- `proxy_pass http://127.0.0.1:9004;` را **بدون `/` و بدون مسیر** در انتها بنویسید؛ وگرنه مسیر `/app/` تغییر می‌کند و ۴۰۴ می‌گیرید.
- `proxy_read_timeout` حداقل `120s` باشد (پیش‌فرض nginx ۶۰ ثانیه است و اتصال‌های بیکار را می‌بُرد؛ ریورب هر ۶۰ ثانیه ping می‌فرستد).
- `^~` باعث می‌شود این دو مسیر قبل از location های regex دیگر (مثل `.php`) انتخاب شوند.
- مسیرهای `/app/` و `/apps/` با مسیرهای API لاراول (`/api/...`) تداخل ندارند.

### جای قرار دادن کانفیگ (DirectAdmin)

فایل‌های nginx که DirectAdmin تولید می‌کند با هر تغییر دامنه **بازنویسی می‌شوند**؛ مستقیم در آن‌ها ننویسید. از مکانیزم کانفیگ سفارشی DirectAdmin استفاده کنید:

- پنل: کاربر دامنه → **Domain Setup → `api.bijak724.ir` → Custom HTTPD Configurations** (بخش nginx / `|CUSTOM|`) و دو بلوک بالا را بچسبانید و ذخیره کنید؛ یا
- فایل سفارشی دامنه در `/usr/local/directadmin/data/users/<کاربر>/domains/api.bijak724.ir.cust_nginx` (نام دقیق به نسخهٔ custombuild بستگی دارد؛ اگر نبود، ادمین از مستندات DirectAdmin «custom nginx config per domain» استفاده کند).

سپس کانفیگ را بازسازی کنید:

```bash
echo "action=rewrite&value=nginx&user=<کاربر>" >> /usr/local/directadmin/data/task.queue
```

اگر سرور DirectAdmin نیست و nginx مستقیم مدیریت می‌شود، همین دو بلوک را در فایل server دامنه (مثلاً `/etc/nginx/conf.d/api.bijak724.ir.conf` یا `sites-enabled`) بگذارید.

## ۲. اعمال

```bash
nginx -t                  # باید «syntax is ok» و «test is successful» بدهد
systemctl reload nginx    # reload (نه restart) تا اتصال‌های فعلی قطع نشود
```

اگر `nginx -t` خطا داد، **reload نزنید**؛ خطا را بررسی کنید (معمولاً `;` جا افتاده یا `location` بیرون از `server`).

## ۳. تست

از **کامپیوتر دیگر** (بیرون از سرور):

```bash
curl -i -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  "https://api.bijak724.ir/app/08f2d479befe83e6be6fe43ccca0f6da?protocol=7&client=js&version=8.6.0"
```

باید `HTTP/1.1 101 Switching Protocols` بیاید و `X-Powered-By: Laravel Reverb`.

| پاسخ | معنی |
|------|------|
| `101` | درست است |
| `404` با JSON فارسی لاراول | درخواست هنوز به لاراول می‌رسد؛ location ها اعمال نشده یا بعد از `location /` است |
| `502 Bad Gateway` | ریورب روی 9004 بالا نیست، یا `proxy_pass` آدرس/پورت اشتباه دارد |
| `504 Gateway Timeout` | ریورب جواب نمی‌دهد یا فایروال داخلی جلوی 127.0.0.1:9004 را گرفته |
| قطع شدن بعد از ~۶۰ ثانیه | `proxy_read_timeout` کم است |
| `400`/`426` | هدرهای `Upgrade`/`Connection` رد نمی‌شوند |

## ۴. برگرداندن تغییر (rollback)

دو بلوک را از کانفیگ سفارشی حذف کنید، سپس `nginx -t && systemctl reload nginx`. هیچ چیز دیگری تغییر نکرده است.

## ۵. اگر جلوی سایت nginx نیست و فقط Apache است

ماژول‌های `proxy`، `proxy_http` و `proxy_wstunnel` باید فعال باشند و در VirtualHost ۴۴۳ دامنه:

```apache
ProxyPreserveHost On
RequestHeader set X-Forwarded-Proto "https"
ProxyPass        "/app"  "ws://127.0.0.1:9004/app"
ProxyPassReverse "/app"  "ws://127.0.0.1:9004/app"
ProxyPass        "/apps" "http://127.0.0.1:9004/apps"
ProxyPassReverse "/apps" "http://127.0.0.1:9004/apps"
```

سپس `apachectl configtest && systemctl reload apache2` (یا `httpd`).

## ۶. اگر دامنه پشت CDN (مثل Cloudflare) است

وب‌سوکت باید در تنظیمات CDN فعال باشد و دامنه روی پورت ۴۴۳ پروکسی شود (پورت‌های غیر استاندارد مثل 9004 معمولاً پروکسی نمی‌شوند). با پروکسی ۴۴۳ مشکلی نیست.

## بعد از انجام (سمت برنامه)

- `.env` سرور از قبل آماده است (`REVERB_HOST=api.bijak724.ir`، `REVERB_PORT=443`، `REVERB_SCHEME=https`، `REVERB_SERVER_PORT=9004`).
- فرانت: `wsHost = api.bijak724.ir`، `wssPort = 443`، `forceTLS = true` (توضیح کامل در [docs/api/29-socket.md](../api/29-socket.md)).
- بعد از وصل شدن، `REVERB_ALLOWED_ORIGINS` را از `*` به دامنهٔ فرانت محدود کنید.
