<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .header {
            background:
                {{ $tenant->brand_color ?? '#3b82f6' }}
            ;
            color: #ffffff;
            padding: 40px 20px;
            text-align: center;
        }

        .header img {
            max-height: 60px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .content {
            padding: 40px 30px;
            color: #333333;
            line-height: 1.6;
        }

        .content p {
            margin: 0 0 20px;
            font-size: 16px;
        }

        .button {
            display: inline-block;
            padding: 14px 30px;
            background:
                {{ $tenant->brand_color ?? '#3b82f6' }}
            ;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }

        .footer a {
            color:
                {{ $tenant->brand_color ?? '#3b82f6' }}
            ;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @if($tenant->brand_logo)
                <img src="{{ $tenant->brand_logo }}" alt="{{ $tenant->brand_name ?? $tenant->name }}">
            @endif
            <h1>Welcome to {{ $tenant->brand_name ?? $tenant->name }}!</h1>
        </div>

        <div class="content">
            <p>Hi {{ $user->name }},</p>

            <p>Welcome aboard! We're excited to have you join our community.</p>

            <p>Your account has been successfully created. You can now log in and start exploring all the amazing
                recipes we have to offer.</p>

            <a href="{{ $loginUrl }}" class="button">Get Started</a>

            <p>If you have any questions, feel free to reach out to us anytime.</p>

            <p>Happy cooking!<br>
                The {{ $tenant->brand_name ?? $tenant->name }} Team</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $tenant->brand_name ?? $tenant->name }}. All rights reserved.</p>
            @if(!($tenant->white_label_enabled && $tenant->hide_powered_by))
                <p style="margin-top: 10px; font-size: 12px">Powered by <a href="https://tastypanel.site">TastyPanel</a></p>
            @endif
        </div>
    </div>
</body>

</html>