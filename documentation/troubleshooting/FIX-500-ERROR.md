# إصلاح خطأ 500 Internal Server Error

## التحقق من المشكلة:

عند اختبار الموقع بـ curl، يعيد **HTTP/2 200 OK**، مما يعني أن Laravel يعمل بشكل صحيح.

## الأسباب المحتملة:

### 1. مشكلة في Assets Loading
إذا كان JavaScript يحاول تحميل resources غير موجودة، قد يسبب 500 error.

### 2. مشكلة في Session/Cache
إذا كان Session driver يستخدم database وكانت هناك مشكلة في الاتصال.

### 3. مشكلة في PHP-FPM
قد تكون هناك مشكلة في PHP-FPM configuration.

## الحلول:

### 1. مسح جميع الكاشات:
```bash
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear
```

### 2. تحديث الصلاحيات:
```bash
chmod -R 775 storage bootstrap/cache
chmod -R 755 public
```

### 3. إعادة بناء Assets (إذا لزم الأمر):
```bash
npm run build
```

### 4. التحقق من Logs:
```bash
tail -f storage/logs/laravel.log
```

### 5. التحقق من Nginx Error Log:
```bash
sudo tail -f /var/log/nginx/tastypanel.site-error.log
sudo tail -f /var/log/nginx/error.log
```

## إذا استمرت المشكلة:

1. **تحقق من PHP Error Log:**
   ```bash
   sudo tail -f /var/log/php-fpm/error.log
   # أو
   sudo tail -f /var/log/php8.4-fpm/error.log
   ```

2. **تحقق من PHP-FPM Status:**
   ```bash
   sudo systemctl status php-fpm
   ```

3. **إعادة تحميل PHP-FPM:**
   ```bash
   sudo systemctl reload php-fpm
   ```

## ملاحظة:

إذا كان curl يعيد 200 OK لكن المتصفح يعطي 500، قد تكون المشكلة في:
- JavaScript errors
- CORS issues
- Browser cache
- Assets loading issues

**جرب:**
- مسح browser cache (Ctrl+Shift+Delete)
- فتح الموقع في incognito mode
- فتح Developer Tools → Console لرؤية JavaScript errors

