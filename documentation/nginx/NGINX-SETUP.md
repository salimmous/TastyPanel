# إعدادات Nginx لـ TastyPanel

## المشكلة
Nginx يعيد 404 لأن جميع الطلبات يجب أن تُوجه إلى `public/index.php` في Laravel.

## الحل

### 1. نسخ ملف الإعدادات
```bash
sudo cp nginx.conf /etc/nginx/sites-available/tastypanel.site
# أو
sudo cp nginx.conf /etc/nginx/conf.d/tastypanel.site.conf
```

### 2. تفعيل الموقع
```bash
sudo ln -s /etc/nginx/sites-available/tastypanel.site /etc/nginx/sites-enabled/
# أو إذا استخدمت conf.d، لا حاجة لتفعيل
```

### 3. تحديث مسار PHP-FPM
افتح الملف وحدّث السطر:
```nginx
fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
```
استبدل `php8.4` بإصدار PHP المثبت لديك. للتحقق:
```bash
ls -la /var/run/php/
```

### 4. تحديث مسارات SSL (إذا كان SSL مثبت)
افتح الملف وحدّث:
```nginx
ssl_certificate /path/to/ssl/certificate.crt;
ssl_certificate_key /path/to/ssl/private.key;
```

### 5. اختبار الإعدادات
```bash
sudo nginx -t
```

### 6. إعادة تحميل Nginx
```bash
sudo systemctl reload nginx
# أو
sudo service nginx reload
```

## النقاط المهمة

1. **Root Directory**: يجب أن يشير إلى `/home/cms/htdocs/tastypanel.site/public`
2. **try_files**: يجب أن يكون `try_files $uri $uri/ /index.php?$query_string;`
3. **PHP-FPM Socket**: تأكد من المسار الصحيح لـ PHP-FPM

## التحقق من العمل

بعد التطبيق، اختبر:
- `https://tastypanel.site/` - الصفحة الرئيسية
- `https://tastypanel.site/login` - صفحة تسجيل الدخول
- `https://tastypanel.site/api/categories` - API endpoint

إذا استمرت المشكلة، تحقق من:
- `tail -f /var/log/nginx/tastypanel.site-error.log`
- `php artisan route:list` - للتأكد من أن الـ routes موجودة

