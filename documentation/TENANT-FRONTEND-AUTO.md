# Tenant Frontend One-Click (Next.js)

هدف: عند إنشاء تينانت جديد من لوحة التحكم، يتولّد موقع Next.js جاهز ويشتغل كخدمة بدون أوامر يدوية.

## المبدأ
- نعيد استخدام قالب Next الموجود في `frontend/`.
- سكريبت جديد `infrastructure/provision-frontend.sh`:
  - ينسخ القالب إلى `/var/www/tastypanel-sites/<tenant-key>/frontend`.
  - يحقن `.env` للقالب: `TENANT_HOST`, `PLATFORM_API_BASE`, `TENANT_ENV`.
  - يختار بورت مميز بناءً على `tenant_id` (مثلاً 32000 + tenant_id).
- يشغّل `npm install && npm run build`.
- ينشئ خدمة systemd `tastypanel-<tenant-key>-frontend.service` لتشغيل `npm start -- -p <port>`.
- Nginx لكل تينانت يوجّه `server_name` إلى `127.0.0.1:<port>`.
- الكشف تلقائي في المنصة: إذا وُجد مجلد `frontend/` داخل `instance_root` للتينانت، Nginx يستعمل تمبليت proxy (`nginx-vhost-frontend.stub`) بدل تمبليت PHP.
- التكامل: استدعِ السكريبت من `orchestrate-tenant.sh` أو من مسار إنشاء التينانت بعد إنشاء الدومين/DB.

## متطلبات السيرفر
- Node 18+ متاح (افتراضياً من `install-ubuntu-24.04.sh` أو نزوده).
- systemd (Ubuntu 24.04).
- مسار جذري للتينات: `/var/www/tastypanel-sites/<tenant-key>/`.

## متغيرات البيئة المطلوبة للسكريبت
- `TENANT_KEY` (إجباري).
- `TENANT_ID` (إجباري لحساب البورت).
- `TENANT_HOST` (إجباري، الدومين الأساسي).
- `PLATFORM_API_BASE` (إجباري، مثل `https://platform.example.com/api`).
- `TENANT_ENV` (اختياري، افتراضي `production`).

## تفعيل one-click من المنصة
- `.env`:
  - `FRONTEND_AUTO=true`
- `FRONTEND_PROVISION_SCRIPT=/var/www/tastypanel/infrastructure/provision-frontend.sh`
- `FRONTEND_DEPROVISION_SCRIPT=/var/www/tastypanel/infrastructure/deprovision-frontend.sh`
- `FRONTEND_PROVISION_USE_SUDO=true`
- `FRONTEND_PLATFORM_API_BASE=https://platform.example.com/api`
- عند `Add new tenant/domain`, المنصة تستدعي provisioning المعتاد ثم تنصيب frontend تلقائيا.

## سريان العمل المقترح
1) أثناء إنشاء التينانت في اللوحة، بعد حفظه:
   - استدعاء: `sudo TENANT_KEY=brand1 TENANT_ID=12 TENANT_HOST=brand1.com PLATFORM_API_BASE=https://platform.example.com/api ./infrastructure/provision-frontend.sh`
2) السكريبت يجهز الخدمة ويعيد تشغيل Nginx (أو فقط يكتب ملف vhost؛ إعادة تحميل Nginx تتم في خطوة لاحقة).
3) عند حذف التينانت: المنصة تنفّذ deprovision للـ frontend service ومسار `frontend/` تلقائيا.

## ملاحظات
- ISR في القالب مضبوط على 5 دقائق؛ يمكن لاحقاً إضافة endpoint revalidate وربطه بالـ webhooks.
- الاستهلاك: كل تينانت يخدم من بورت مستقل؛ Nginx reverse proxy.
- إذا `TENANT_APP_REPO` فارغ، المنصة تستعمل repo الحالي (`base_path`) كـ fallback لبناء instance.
