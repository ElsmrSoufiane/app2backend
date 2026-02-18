<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f4f7fb; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 28px; padding: 30px; border: 1px solid #eef2f6; }
        .button { background: #0066ff; color: white; padding: 14px 28px; border-radius: 999px; text-decoration: none; display: inline-block; font-weight: 900; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="margin-top:0">مرحباً {{ $user->name }}</h1>
        <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في تطبيق مختصر الأرباح.</p>
        <p>لإعادة تعيين كلمة المرور، الرجاء النقر على الرابط التالي:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $link }}" class="button">إعادة تعيين كلمة المرور</a>
        </p>
        <p>إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد الإلكتروني.</p>
        <p>هذا الرابط صالح لمدة 30 دقيقة فقط.</p>
        <hr style="border: none; border-top: 1px solid #eef2f6; margin: 20px 0;">
        <p style="color: #64748b; font-size: 13px;">تطبيق مختصر الأرباح - تتبع أرباحك ببساطة</p>
    </div>
</body>
</html>