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

        .header h1 {
            margin: 0;
            font-size: 24px;
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

        .alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>

        <div class="content">
            <p>Hi {{ $user->name }},</p>

            <p>We received a request to reset your password for your {{ $tenant->brand_name ?? $tenant->name }} account.
            </p>

            <p>Click the button below to reset your password:</p>

            <a href="{{ $resetUrl }}" class="button">Reset Password</a>

            <div class="alert">
                <strong>⚠️ Security Notice:</strong> This link will expire in 60 minutes for security reasons.
            </div>

            <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain
                unchanged.</p>

            <p>For security reasons, if you continue to receive password reset emails that you didn't request, please
                contact our support team immediately.</p>

            <p>Best regards,<br>
                The {{ $tenant->brand_name ?? $tenant->name }} Team</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $tenant->brand_name ?? $tenant->name }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>