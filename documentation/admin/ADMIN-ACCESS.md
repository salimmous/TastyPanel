# معلومات الوصول إلى Admin Dashboard

## ✅ حالة قاعدة البيانات:

### قاعدة البيانات:
- **Status**: ✅ يعمل بشكل صحيح
- **Database**: `/home/cms/htdocs/tastypanel.site/database/recipesarticles.sqlite`
- **Connection**: OK

### البيانات:
- **Users**: 1 (Admin)
- **Recipes**: 9 وصفات
- **Categories**: 8 تصنيفات
- **Articles**: 3 مقالات

## 🔐 معلومات تسجيل الدخول:

### Admin User:
- **Email**: `admin@tastypanel.com`
- **Password**: `password`
- **Status**: ✅ موجود ويعمل

## 🌐 روابط الوصول:

### صفحة تسجيل الدخول:
```
https://tastypanel.site/login
```

### لوحة التحكم (بعد تسجيل الدخول):
```
https://tastypanel.site/admin/dashboard
```

## ✅ التحقق من العمل:

تم اختبار API Login وهو يعمل بشكل صحيح:
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@tastypanel.com"
  },
  "message": "تم تسجيل الدخول بنجاح"
}
```

## 📋 خطوات تسجيل الدخول:

1. افتح `https://tastypanel.site/login`
2. أدخل:
   - **Email**: `admin@tastypanel.com`
   - **Password**: `password`
3. اضغط "تسجيل الدخول"
4. سيتم توجيهك تلقائياً إلى `/admin/dashboard`

## 🔧 إذا نسيت كلمة المرور:

يمكنك إنشاء مستخدم admin جديد:
```bash
php artisan tinker
>>> $user = App\Models\User::where('email', 'admin@tastypanel.com')->first();
>>> $user->password = Hash::make('new_password');
>>> $user->save();
```

أو استخدام seeder:
```bash
php artisan db:seed --class=AdminUserSeeder --force
```

## 📊 إحصائيات قاعدة البيانات:

- **Total Users**: 1
- **Total Recipes**: 9
- **Total Categories**: 8
- **Total Articles**: 3

## ✅ كل شيء جاهز ويعمل!

