# Supervisor: صف (queue workers) و ریورب

هر دو برنامه باید با Supervisor اجرا شوند (نه کرون): Supervisor آن‌ها را همیشه زنده نگه می‌دارد و بعد از هر deploy
(`queue:restart` / `supervisorctl restart`) فوراً با کد و `.env` جدید بالا می‌آورد.

## ۱. صف‌ها: سبک (همیشه روشن) و سنگین (هر دقیقه)

ساخت فایل گزارش فقط با جاب صف انجام می‌شود، روی دو صف:

| صف | چه گزارش‌هایی | چه کسی اجرا می‌کند |
|----|----------------|---------------------|
| `reports-light` | تا `REPORT_LIGHT_MAX_ROWS` بارنامه (پیش‌فرض ۲۰۰۰) | worker همیشه روشن (Supervisor)؛ جاب را کمتر از یک ثانیه بعد از درج برمی‌دارد |
| `reports-heavy` | بیشتر از آن | کرون هر دقیقه (یک نمونه در هر زمان) |
| `default` | پیام‌های سوکت، اعلان‌ها | همان worker سبک |

چون workerِ صف سبک جداست، گزارش کوچک هیچ‌وقت پشت گزارش بزرگ منتظر نمی‌ماند.

### صف سبک (Supervisor)

`numprocs=5` یعنی ۵ worker جدا؛ هر کدام یک جاب برمی‌دارد، پس تا ۵ گزارش سبک هم‌زمان ساخته می‌شود. `--sleep=1` یعنی صف خالی هر
ثانیه چک می‌شود.

```ini
[program:bijak-queue-light]
process_name=%(program_name)s_%(process_num)02d
directory=/home/pakzad/domains/api.bijak724.ir/public_html
command=/usr/local/php83/bin/php /home/pakzad/domains/api.bijak724.ir/public_html/artisan queue:work database --queue=reports-light,default --sleep=1 --tries=1 --timeout=300 --max-time=3600
user=pakzad
numprocs=5
autostart=true
autorestart=true
startsecs=3
stopasgroup=true
killasgroup=true
stopwaitsecs=300
redirect_stderr=true
stdout_logfile=/home/pakzad/domains/api.bijak724.ir/public_html/storage/logs/queue-light.log
stdout_logfile_maxbytes=5MB
stdout_logfile_backups=2
```

- `--timeout=300` برابر `light_job_timeout` است؛ `--max-time=3600` هر worker را بعد از یک ساعت تازه می‌کند.
- ترتیب `--queue=reports-light,default`: هر دو لازم‌اند (گزارش سبک و پیام‌های سوکت).

### صف سنگین (کرون هر دقیقه)

در DirectAdmin → Cron Jobs، `* * * * *`:

```bash
cd domains/api.bijak724.ir/public_html && bash ops/server/run-logged.sh --no-overlap queue-heavy -- php artisan queue:work --queue=reports-heavy --stop-when-empty --tries=1 --timeout=1800 >> /dev/null 2>&1
```

- هر دقیقه یک worker بالا می‌آید، همهٔ جاب‌های سنگین موجود را یکی‌یکی انجام می‌دهد و وقتی صف خالی شد تمام می‌شود.
- `--no-overlap`: اگر جاب سنگین بیش از یک دقیقه طول کشید، دقیقهٔ بعد worker دومی روی آن نمی‌افتد (آن اجرا رد می‌شود).
- `--timeout=1800` برابر `job_timeout` است و باید کمتر از `DB_QUEUE_RETRY_AFTER` (۱۹۰۰) باشد.

### نکته‌ها

- **کرون قبلی `queue:work` را حذف کنید** (همان که هر دو صف را با `--stop-when-empty` اجرا می‌کرد)، وگرنه صف سبک را هم گاهی آن می‌گیرد.
- حافظهٔ هر worker: ساخت Excel/PDF بزرگ حافظه می‌خواهد؛ `memory_limit` سرور را چک کنید.
- `REPORT_LIGHT_MAX_ROWS` در `.env` سرور مرز سبک و سنگین است؛ بعد از تغییرش `optimize:clear` و `optimize`.

## ۲. ریورب

```ini
[program:bijak-reverb]
process_name=%(program_name)s
directory=/home/pakzad/domains/api.bijak724.ir/public_html
command=/usr/local/php83/bin/php /home/pakzad/domains/api.bijak724.ir/public_html/artisan reverb:start
user=pakzad
autostart=true
autorestart=true
startsecs=3
stopasgroup=true
killasgroup=true
stopwaitsecs=10
redirect_stderr=true
stdout_logfile=/home/pakzad/domains/api.bijak724.ir/public_html/storage/logs/reverb.log
stdout_logfile_maxbytes=1MB
stdout_logfile_backups=1
```

## ۳. اعمال

```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

باید `bijak-queue-light_00` تا `_04` و `bijak-reverb` در وضعیت `RUNNING` باشند.

## ۴. چک

```bash
ps aux | grep "queue:work" | grep -v grep | wc -l     # ۵
tail -n 20 storage/logs/queue-light.log
```

بعد از یک deploy، `php artisan queue:restart` (گیت اکشن می‌زند) هر worker را بعد از جاب فعلی‌اش می‌بندد و Supervisor دوباره بالا می‌آورد.
