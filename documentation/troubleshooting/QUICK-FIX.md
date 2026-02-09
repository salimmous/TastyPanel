# إصلاح سريع لمشكلة 404

## المشكلة
Nginx يعيد 404 على `/login` و `/favicon.ico` لأن الطلبات لا تُوجه إلى Laravel.

## الحل السريع

### 1. تطبيق إعدادات Nginx
```bash
sudo ./infrastructure/setup-nginx.sh
```

أو يدوياً:
```bash
# نسخ الإعدادات
sudo cp nginx.conf /etc/nginx/sites-available/tastypanel.site

# تفعيل الموقع
sudo ln -sf /etc/nginx/sites-available/tastypanel.site /etc/nginx/sites-enabled/

# تحديث PHP-FPM socket (افتح الملف وحدّث السطر)
sudo nano /etc/nginx/sites-available/tastypanel.site
# ابحث عن: fastcgi_pass unix:/run/php/php8.3-fpm.sock;
# حدّثه إلى: fastcgi_pass unix:/run/php/php8.3-fpm.sock; (أو الإصدار الصحيح)

# اختبار
sudo nginx -t

# إعادة تحميل
sudo systemctl reload nginx
```

### 2. التحقق من أن Laravel يعمل
```bash
cd /var/www/tastypanel
php artisan route:list | grep "{any}"
```

يجب أن ترى:
```
GET|HEAD  {any} .......................... generated::...
```

### 3. التحقق من الصلاحيات
```bash
chmod -R 755 storage bootstrap/cache
chmod -R 755 public
```

### 4. مسح الكاش
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

## النقاط المهمة في إعدادات Nginx

1. **Root يجب أن يشير إلى `public/`**:
   ```nginx
   root /var/www/tastypanel/public;
   ```

2. **try_files يجب أن يوجه إلى index.php**:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

3. **PHP-FPM socket يجب أن يكون صحيحاً**

## اختبار بعد الإصلاح

```bash
curl -I https://tastypanel.site/login
# يجب أن يعيد 200 OK

curl -I https://tastypanel.site/api/categories
# يجب أن يعيد 200 OK
```

## إذا استمرت المشكلة

1. تحقق من logs:
   ```bash
   tail -f /var/log/nginx/tastypanel.site-error.log
   tail -f storage/logs/laravel.log
   ```

2. تحقق من أن PHP-FPM يعمل:
   ```bash
   sudo systemctl status php-fpm
   # أو
   sudo systemctl status php8.4-fpm
   ```

3. تحقق من أن Nginx يقرأ الإعدادات:
   ```bash
   sudo nginx -T | grep tastypanel
   ```
