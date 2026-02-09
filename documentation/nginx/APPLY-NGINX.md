# تطبيق إعدادات Nginx لـ TastyPanel

## ⚠️ ملاحظة مهمة
أنت تحتاج إلى صلاحيات root (sudo) لتطبيق هذه الإعدادات. إذا لم تكن لديك الصلاحيات، اتصل بمدير النظام.

## الخطوات:

### 1. نسخ ملف الإعدادات
```bash
sudo cp /home/cms/htdocs/tastypanel.site/nginx.conf /etc/nginx/sites-available/tastypanel.site
```

أو إذا كان السيرفر يستخدم `conf.d`:
```bash
sudo cp /home/cms/htdocs/tastypanel.site/nginx.conf /etc/nginx/conf.d/tastypanel.site.conf
```

### 2. تحديث مسار PHP-FPM
افتح الملف وحدّث السطر:
```bash
sudo nano /etc/nginx/sites-available/tastypanel.site
# أو
sudo nano /etc/nginx/conf.d/tastypanel.site.conf
```

ابحث عن:
```nginx
fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
```

وحدّثه إلى المسار الصحيح. للتحقق من المسار الصحيح:
```bash
# جرب هذه الأوامر (قد تحتاج sudo):
ls -la /var/run/php-fpm/
ls -la /run/php/
ls -la /tmp/php-fpm.sock
```

أو اسأل مدير النظام عن مسار PHP-FPM socket.

### 3. تحديث مسارات SSL (إذا كان SSL مثبت)
افتح الملف وحدّث:
```nginx
ssl_certificate /path/to/ssl/certificate.crt;
ssl_certificate_key /path/to/ssl/private.key;
```

### 4. تفعيل الموقع (إذا استخدمت sites-available)
```bash
sudo ln -sf /etc/nginx/sites-available/tastypanel.site /etc/nginx/sites-enabled/
```

### 5. اختبار الإعدادات
```bash
sudo nginx -t
```

إذا نجح الاختبار، ستشاهد:
```
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### 6. إعادة تحميل Nginx
```bash
sudo systemctl reload nginx
# أو
sudo service nginx reload
```

## التحقق من العمل

بعد التطبيق، اختبر:
```bash
curl -I https://tastypanel.site/login
# يجب أن يعيد: HTTP/2 200

curl -I https://tastypanel.site/api/categories
# يجب أن يعيد: HTTP/2 200
```

## إذا واجهت مشاكل

### تحقق من logs:
```bash
sudo tail -f /var/log/nginx/tastypanel.site-error.log
sudo tail -f /var/log/nginx/error.log
```

### تحقق من أن PHP-FPM يعمل:
```bash
sudo systemctl status php-fpm
# أو
sudo systemctl status php8.4-fpm
```

### تحقق من أن Nginx يقرأ الإعدادات:
```bash
sudo nginx -T | grep tastypanel
```

## معلومات مهمة

- **Root Directory**: `/home/cms/htdocs/tastypanel.site/public`
- **PHP Version**: 8.4.12
- **Domain**: tastypanel.site

## إذا لم تكن لديك صلاحيات root

اتصل بمدير النظام وأرسل له:
1. ملف `nginx.conf` من المشروع
2. هذا الملف (`APPLY-NGINX.md`)
3. اطلب منه تطبيق الإعدادات

