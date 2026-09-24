<div dir="rtl">

# راهنمای راه‌اندازی پروژه روی سیستم شخصی

این پروژه یک اپلیکیشن **Laravel 13** است با این بخش‌ها: مدیریت کارها، کارکرد و حقوق (با افزونه مرورگر برای بیزاجی و ورود از اکسل) و ابزارها (مرتب‌سازی JSON و تبدیل تایم‌استمپ). همه تاریخ‌ها شمسی و صفحه‌ها راست‌چین هستند.

دو روش برای بالا آوردن پروژه هست؛ یکی را انتخاب کنید:

- [روش ۱: داکر](#روش-۱-داکر) — ساده‌ترین؛ فقط Docker Desktop لازم است.
- [روش ۲: لاراگون (ویندوز)](#روش-۲-لاراگون-ویندوز) — برای توسعه و تغییر کد راحت‌تر است.

---

## دریافت کد

مخزن گیت‌هاب **خصوصی** است؛ اول باید صاحب پروژه شما را به‌عنوان Collaborator دعوت کند (Settings ← Collaborators) و دعوت را قبول کنید. بعد:

</div>

```bash
git clone https://github.com/MR-Ghavidel/sandbox.git
cd sandbox
```

<div dir="rtl">

---

## روش ۱: داکر

### پیش‌نیاز

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) نصب و **در حال اجرا** باشد.
- برای اولین ساخت به اینترنت نیاز است (دانلود ایمیج‌ها، پکیج‌ها و فونت وزیرمتن).

### اجرا

در پوشه پروژه:

</div>

```bash
docker compose up -d --build
```

<div dir="rtl">

اولین بار چند دقیقه طول می‌کشد. در این مرحله خودکار انجام می‌شود:

- ساخت فایل‌های CSS/JS با Vite،
- نصب PHP 8.4 با افزونه‌های `intl` (برای تاریخ شمسی) و `zip` (برای خواندن اکسل)،
- راه‌اندازی MySQL 8.4، ساخت `.env` و کلید برنامه و اجرای مایگریشن‌ها.

بعد از آن آدرس زیر را باز کنید:

**http://localhost:8000**

### دستورهای کاربردی

</div>

| کار | دستور |
|---|---|
| دیدن لاگ‌ها | `docker compose logs -f app` |
| اجرای دستور artisan | `docker compose exec app php artisan <command>` |
| اجرای تست‌ها | `docker compose exec app php artisan test` |
| اعمال تغییرات کد | `docker compose up -d --build` |
| خاموش کردن | `docker compose down` |
| خاموش کردن و **پاک کردن همه داده‌ها** | `docker compose down -v` |

<div dir="rtl">

- دیتابیس MySQL از بیرون کانتینر روی `127.0.0.1:3307` در دسترس است (کاربر `sandbox`، رمز `secret`، دیتابیس `sandbox`) — مثلاً برای HeidiSQL یا DBeaver.
- داده‌ها در volumeهای داکر می‌مانند و با `docker compose down` پاک نمی‌شوند؛ فقط `down -v` آن‌ها را پاک می‌کند.
- تنظیمات دیتابیس و منطقه زمانی داخل `compose.yaml` است و روی `.env` داخل کانتینر اولویت دارد.

---

## روش ۲: لاراگون (ویندوز)

### پیش‌نیازها

</div>

| ابزار | نسخه | توضیح |
|---|---|---|
| [Laragon](https://laragon.org/download/) | Full | شامل Apache، MySQL، Composer و Git |
| PHP | **8.4** (حداقل 8.3) | از منوی Laragon ← PHP ← Version انتخاب کنید |
| افزونه‌های PHP | `intl`، `zip`، `pdo_mysql`، `mbstring`، `fileinfo`، `openssl` | منوی Laragon ← PHP ← Extensions |
| Node.js | **20.19 یا جدیدتر** (پیشنهاد: 22 LTS) | نسخه همراه Laragon معمولاً قدیمی است؛ از [nodejs.org](https://nodejs.org/) نصب کنید |

<div dir="rtl">

> **مهم:** بدون افزونه `intl` تاریخ‌های شمسی کار نمی‌کنند و بدون `zip` ورود از اکسل کار نمی‌کند. بعد از فعال کردن افزونه‌ها، Laragon را یک بار Stop/Start کنید.

نسخه‌ها را در ترمینال Laragon (دکمه Terminal) بررسی کنید:

</div>

```bash
php -v
php -m | findstr /i "intl zip pdo_mysql"
node -v
composer -V
```

<div dir="rtl">

### ۱. قرار دادن پروژه

پروژه را داخل `C:\laragon\www` کلون کنید تا Laragon خودش برایش آدرس بسازد:

</div>

```bash
cd C:\laragon\www
git clone https://github.com/MR-Ghavidel/sandbox.git
cd sandbox
```

<div dir="rtl">

### ۲. ساخت دیتابیس

Laragon را Start کنید و یک دیتابیس بسازید (از دکمه **Database** با HeidiSQL، یا در ترمینال):

</div>

```bash
mysql -u root -e "CREATE DATABASE sandbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

<div dir="rtl">

### ۳. تنظیم `.env`

</div>

```bash
copy .env.example .env
```

<div dir="rtl">

فایل `.env` را باز کنید و این مقادیر را تنظیم کنید:

</div>

```dotenv
APP_NAME=Sandbox
APP_URL=http://sandbox.test
APP_TIMEZONE=Asia/Tehran

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sandbox
DB_USERNAME=root
DB_PASSWORD=
```

<div dir="rtl">

> `APP_TIMEZONE=Asia/Tehran` را حتماً نگه دارید؛ «امروز»، هفته شنبه تا جمعه و بازه‌های حقوق بر اساس آن حساب می‌شوند.

### ۴. نصب و آماده‌سازی

این یک دستور همه کارها را انجام می‌دهد (نصب پکیج‌های PHP، ساخت کلید، مایگریشن، نصب پکیج‌های JS و ساخت CSS/JS):

</div>

```bash
composer run setup
```

<div dir="rtl">

یا مرحله به مرحله:

</div>

```bash
composer install
php artisan key:generate
php artisan migrate
npm install
npm run build
```

<div dir="rtl">

### ۵. باز کردن سایت

در Laragon روی **Reload** بزنید (یا Stop/Start). Laragon برای پوشه `sandbox` خودکار آدرس زیر را می‌سازد:

**http://sandbox.test**

- برای HTTPS: منوی Laragon ← Apache ← SSL ← Enabled، بعد `https://sandbox.test` (و `APP_URL` را هم https کنید).
- اگر پسوند آدرس‌ها در Laragon چیز دیگری است (مثلاً `.local`)، از منوی Preferences ← General ← Hostname template تنظیم شده؛ `APP_URL` را مطابق آن بنویسید.
- بدون Apache هم می‌شود: `php artisan serve` و بعد `http://127.0.0.1:8000`.

### حین توسعه

</div>

| کار | دستور |
|---|---|
| ساخت خودکار CSS/JS هنگام تغییر فایل‌ها | `npm run dev` (یا `composer run dev`) |
| اجرای تست‌ها | `php artisan test` |
| مرتب کردن کد PHP | `vendor/bin/pint` |

<div dir="rtl">

> اگر تغییری در ظاهر سایت دیده نمی‌شود، `npm run build` بزنید یا `npm run dev` را در حال اجرا نگه دارید.

---

## بعد از راه‌اندازی

### ورود داده‌های قبلی از اکسل

در صفحه **کارکرد و حقوق** روی **ورود از اکسل** بزنید و یک یا چند فایل ماهانه (xlsx) را انتخاب کنید؛ قبل از ثبت، پیش‌نمایش تغییرات نشان داده می‌شود. در روش لاراگون از ترمینال هم می‌شود:

</div>

```bash
php artisan attendance:import-excel "C:\path\01_Mehr.xlsx" "C:\path\02_Aban.xlsx"
```

<div dir="rtl">

### افزونه مرورگر بیزاجی (کروم و فایرفاکس)

افزونه در پوشه `browser-extension/bizagi-export` است و جدول ورود و خروج بیزاجی را برای صفحه کارکرد می‌فرستد.

- **کروم / Edge:** `chrome://extensions` ← روشن کردن Developer mode ← **Load unpacked** ← انتخاب پوشه `browser-extension/bizagi-export`.
- **فایرفاکس (نسخه ۱۲۸ به بعد):** `about:debugging#/runtime/this-firefox` ← **Load Temporary Add-on** ← انتخاب فایل `manifest.json` همان پوشه. (افزونه موقت با بستن فایرفاکس حذف می‌شود.)

بعد از نصب، روی آیکون افزونه بزنید ← **تنظیمات** ← آدرس سامانه را وارد کنید (مثلاً `http://localhost:8000` در داکر یا `http://sandbox.test` در لاراگون) و **ذخیره آدرس** را بزنید و اجازه دسترسی را تأیید کنید.

---

## رفع اشکال

</div>

| خطا یا مشکل | راه حل |
|---|---|
| `Class "IntlDateFormatter" not found` یا `IntlCalendar` | افزونه `intl` را در PHP فعال کنید و Laragon را ری‌استارت کنید. |
| `Class "ZipArchive" not found` هنگام ورود از اکسل | افزونه `zip` را فعال کنید. |
| `Unable to locate file in Vite manifest` | `npm run build` را اجرا کنید. |
| Vite هنگام build خطای نسخه Node می‌دهد | Node.js نسخه 20.19 یا جدیدتر نصب کنید (`node -v`). |
| «امروز» یا ساعت‌ها ۳:۳۰ جابه‌جا هستند | در `.env` مقدار `APP_TIMEZONE=Asia/Tehran` باشد، بعد `php artisan config:clear`. |
| `SQLSTATE[HY000] [1049] Unknown database` | دیتابیس `sandbox` را بسازید (مرحله ۲). |
| افزونه مرورگر «ارسال ناموفق» می‌دهد | آدرس سامانه را در تنظیمات افزونه بررسی کنید؛ اگر خطای HTTPS است، آدرس `http://` را امتحان کنید. |
| در داکر، `localhost:8000` باز نمی‌شود | `docker compose logs -f app` را ببینید؛ اولین بار صبر کنید تا MySQL آماده و مایگریشن‌ها اجرا شوند. |
