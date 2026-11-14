# دیپلوی روی cPanel (هاست اشتراکی)

## ۱. آپلود کد

۱. مخزن را روی هاست کلون کن یا فایل پروژه را زیپ و آپلود کن؛ **`vendor` لازم نیست** اگر روی هاست Composer داری، وگرنه پوشه `vendor` را هم آپلود کن.
۲. بهتر است پروژه **بیرون از `public_html`** باشد (مثلاً `/home/USER/app`) و دامنه به پوشه `public` اشاره کند.
۳. اگر نمی‌توانی Document Root را عوض کنی: محتویات `public` را داخل `public_html` بریز و در `public_html/index.php` دو مسیر `__DIR__.'/../vendor/autoload.php'` و `__DIR__.'/../bootstrap/app.php'` را به مسیر پروژه تغییر بده.
۴. فایل `public-build.zip` (بیلد فرانت) را در ریشه پروژه باز کن تا پوشه `public/build` ساخته شود.
۵. در MultiPHP Manager نسخه PHP را روی **8.3 یا 8.4** بگذار.

## ۲. دیتابیس

در cPanel → MySQL Databases یک دیتابیس و کاربر بساز و کاربر را با دسترسی All به دیتابیس وصل کن.

## ۳. فایل `.env`

از `.env.example` کپی بساز و این مقادیر را تنظیم کن:

```dotenv
APP_NAME="..."
APP_ENV=production
APP_KEY=            # با php artisan key:generate روی سیستم خودت بساز و مقدارش را اینجا بگذار
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanel_db_name
DB_USERNAME=cpanel_db_user
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_LEVEL=error

# ربات بله (خالی = ربات خاموش)
BALE_BOT_TOKEN=
BALE_BOT_USERNAME=
BALE_WEBHOOK_SECRET=یک-رشته-تصادفی
BALE_API_URL=https://tapi.bale.ai

# اجرای دستورات آرتیزان از مرورگر (خالی = غیرفعال)
DEPLOY_RUNNER_TOKEN=یک-توکن-بلند-تصادفی
```

> اگر Worker/Queue نداری `QUEUE_CONNECTION=sync` بگذار.

## ۴. اجرای دستورات بدون ترمینال

روت محافظت‌شده با توکن:

```
https://your-domain.com/deploy/artisan/{command}?token=DEPLOY_RUNNER_TOKEN
```

ترتیب اولین دیپلوی:

1. `.../deploy/artisan/migrate?token=...` ← ساخت جدول‌ها (`--force` خودکار)
2. `.../deploy/artisan/storage-link?token=...`
3. `.../deploy/artisan/optimize-clear?token=...`
4. `.../deploy/artisan/optimize?token=...`

دستورهای مجاز: `migrate`، `migrate-status`، `optimize`، `optimize-clear`، `config-cache`، `config-clear`، `route-cache`، `route-clear`، `view-cache`، `view-clear`، `cache-clear`، `storage-link`، `schedule-list`، `version`.

نکته‌ها:
- اگر `DEPLOY_RUNNER_TOKEN` را عوض کردی و قبلاً `config-cache` زده بودی، با توکن **قدیمی** یک‌بار `config-clear` بزن (یا فایل `bootstrap/cache/config.php` را از File Manager حذف کن).
- توکن را جای امن نگه دار و بعد از دیپلوی می‌توانی خالی‌اش کنی.

## ۵. کرون برای یادآورها

در cPanel → Cron Jobs این خط را هر دقیقه اجرا کن:

```
* * * * * /usr/local/bin/php /home/USER/app/artisan schedule:run >> /dev/null 2>&1
```

مسیر `php` را با `which php` یا پشتیبانی هاست چک کن؛ مسیر پروژه هم باید مسیر واقعی `artisan` باشد.

## ۶. وبهوک ربات بله

روی هاست اشتراکی `bale:poll` مناسب نیست؛ وبهوک را یک‌بار با مرورگر ست کن:

```
https://tapi.bale.ai/bot<BALE_BOT_TOKEN>/setWebhook?url=https://your-domain.com/bale/webhook/<BALE_WEBHOOK_SECRET>
```

بررسی وضعیت: `https://tapi.bale.ai/bot<BALE_BOT_TOKEN>/getWebhookInfo`

## ۷. دسترسی‌ها

پوشه‌های `storage` و `bootstrap/cache` باید قابل نوشتن باشند (`755` یا `775`).

## ۸. هر دیپلوی بعدی

۱. فایل‌های تغییرکرده + `public-build.zip` جدید را آپلود/باز کن.
۲. `migrate` → `optimize-clear` → `optimize` را از روت بالا اجرا کن.

## ۹. عیب‌یابی

- خطای 500 → `storage/logs/laravel.log`
- خطای `No application encryption key` → `APP_KEY` خالی است
- خطای 419 روی روت دیپلوی → `bootstrap/app.php` جدید آپلود نشده (این روت از CSRF مستثنی است)
- تغییرات فرانت دیده نمی‌شود → مطمئن شو `public/build` جدید آپلود شده و `view-clear`/`optimize-clear` زده‌ای
- خطای `The Process class relies on proc_open` → فقط دستور `about` به آن نیاز دارد؛ از `version` استفاده کن
- خطای `Table ... already exists` در مایگریشن → یک اجرای قبلی نیمه‌کاره مانده؛ با `migrate-status` جدول‌های Pending را ببین، جدول‌های باقی‌مانده را از phpMyAdmin حذف کن و دوباره `migrate` بزن
- ربات بله جواب نمی‌دهد → آدرس وبهوک باید دقیقاً شامل سکرت باشد: `https://your-domain.com/bale/webhook/BALE_WEBHOOK_SECRET`
