<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Not Configured - TastyPanel</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #0f172a;
            color: #e2e8f0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .box {
            width: min(640px, 92vw);
            background: rgba(15, 23, 42, .65);
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 24px;
        }
        h1 { margin: 0 0 8px; font-size: 30px; color: #fff; }
        p { margin: 6px 0; color: #cbd5e1; }
        a {
            display: inline-block;
            margin-top: 14px;
            color: #fff;
            text-decoration: none;
            background: #1d4ed8;
            padding: 10px 14px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Site Not Configured Yet</h1>
        <p><strong>Host:</strong> {{ $host ?? request()->getHost() }}</p>
        <p>{{ $reason ?? 'No active site configuration found for this host.' }}</p>
        <a href="{{ route('platform.login') }}">Open Platform Admin</a>
    </div>
</body>
</html>
