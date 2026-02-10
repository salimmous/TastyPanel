<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>phpMyAdmin not configured - TastyPanel</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0f172a; color: #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .box { width: min(640px, 92vw); background: rgba(15, 23, 42, .65); border: 1px solid #334155; border-radius: 14px; padding: 24px; }
        h1 { margin: 0 0 12px; font-size: 24px; color: #fff; }
        p { margin: 8px 0; color: #cbd5e1; line-height: 1.5; }
        code { background: #1e293b; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
        ul { margin: 12px 0; padding-left: 20px; color: #cbd5e1; }
        a { color: #60a5fa; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .btn { display: inline-block; margin-top: 14px; margin-right: 8px; color: #fff; text-decoration: none; background: #1d4ed8; padding: 10px 14px; border-radius: 8px; }
        .btn:hover { background: #1e40af; }
    </style>
</head>
<body>
    <div class="box">
        <h1>phpMyAdmin not configured</h1>
        <p>This URL is for phpMyAdmin. Right now Nginx is sending the request to the panel, so phpMyAdmin is not being served.</p>
        <p><strong>On the server:</strong></p>
        <ul>
            <li>Install phpMyAdmin: <code>sudo apt update && sudo apt install -y phpmyadmin</code></li>
            <li>Add a <code>location /phpmyadmin/</code> block to the panel’s Nginx config <em>before</em> <code>location /</code> (see <code>documentation/PHPMYADMIN.md</code> or re-run the install script to generate a config that includes it).</li>
            <li>Reload Nginx: <code>sudo nginx -t && sudo systemctl reload nginx</code></li>
        </ul>
        <p>Then open <a href="{{ url('/phpmyadmin/') }}">{{ url('/phpmyadmin/') }}</a> again and log in with your database user (e.g. platform DB from <code>.env</code>: <code>DB_USERNAME</code> / <code>DB_PASSWORD</code>).</p>
        <a href="{{ route('platform.login') }}" class="btn">Open Platform Admin</a>
        <a href="{{ url('/phpmyadmin/') }}" class="btn">Retry phpMyAdmin</a>
    </div>
</body>
</html>
