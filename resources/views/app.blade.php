<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TastyPanel</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #111827;
            color: #f9fafb;
        }
        .box {
            border: 1px solid #374151;
            border-radius: 12px;
            padding: 24px;
            background: #1f2937;
            text-align: center;
        }
        a {
            color: #fff;
            text-decoration: none;
            background: #2563eb;
            border-radius: 8px;
            padding: 8px 12px;
            display: inline-block;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>TastyPanel</h1>
        <p>Frontend SPA is disabled. Use the Laravel admin platform directly.</p>
        <a href="{{ route('platform.login') }}">Open Platform Login</a>
    </div>
</body>
</html>
