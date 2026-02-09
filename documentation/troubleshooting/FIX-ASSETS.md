# إصلاح مشكلة تحميل Assets

## المشكلة
الملفات في `/build/assets/` لا تُحمّل (404 error)

## الحل

### 1. تحديث إعدادات Nginx

تم تحديث `nginx.conf` لخدمة ملفات `/build` مباشرة. يجب تطبيق الإعدادات:

```bash
sudo cp nginx.conf /etc/nginx/sites-available/tastypanel.site
# أو
sudo cp nginx.conf /etc/nginx/conf.d/tastypanel.site.conf

# اختبار
sudo nginx -t

# إعادة تحميل
sudo systemctl reload nginx
```

### 2. التحقق من الملفات

```bash
# يجب أن تكون الملفات موجودة:
ls -la public/build/assets/
# يجب أن ترى:
# - main-C85bxWDl.js
# - main-B7kGfL-t.css
```

### 3. التحقق من الصلاحيات

```bash
chmod -R 755 public/build
```

### 4. مسح الكاش

```bash
php artisan view:clear
php artisan config:clear
php artisan optimize:clear
```

## النقاط المهمة في nginx.conf

```nginx
# Serve build assets directly (يجب أن يكون قبل location /)
location /build {
    try_files $uri =404;
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

هذا يضمن أن Nginx يخدم ملفات `/build/assets/` مباشرة بدون توجيهها إلى Laravel.

## اختبار بعد الإصلاح

```bash
# يجب أن يعيد 200 OK:
curl -I https://tastypanel.site/build/assets/main-C85bxWDl.js
curl -I https://tastypanel.site/build/assets/main-B7kGfL-t.css
```

