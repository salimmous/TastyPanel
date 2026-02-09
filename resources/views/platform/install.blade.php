<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Wizard - TastyPanel</title>
    <style>
        body {
            margin: 0;
            background: #f4f6fb;
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .wrap {
            max-width: 980px;
            margin: 26px auto;
            padding: 0 16px;
            display: grid;
            gap: 14px;
        }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
        }
        h1 { margin: 0 0 6px; font-size: 30px; }
        .muted { color: #64748b; font-size: 14px; }
        .steps { margin: 8px 0 0 20px; color: #334155; }
        .steps li { margin: 8px 0; }
        .ok { color: #065f46; }
        .bad { color: #991b1b; }
        code {
            display: block;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            margin: 8px 0;
            white-space: pre-wrap;
            font-size: 13px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        label { display: block; font-size: 13px; color: #334155; }
        input, select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
            margin-top: 6px;
        }
        .btn {
            border: 0;
            border-radius: 8px;
            background: #1d4ed8;
            color: #fff;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .error {
            margin-bottom: 12px;
            padding: 10px;
            border-radius: 8px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            font-size: 14px;
        }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>TastyPanel Installer</h1>
        <div class="muted">Manual first-run setup. Complete these steps, then create your admin user from this page.</div>
    </div>

    <div class="card">
        <h3>Step 1 - Server Commands (Manual)</h3>
        <ol class="steps">
            <li>Install dependencies and PHP/MySQL/Nginx.</li>
            <li>Set your `.env` values (DB credentials, APP_URL, APP_KEY).</li>
            <li>Run migrations.</li>
        </ol>
        <code>php artisan key:generate --force
php artisan migrate --force
php artisan config:clear
php artisan cache:clear</code>
    </div>

    <div class="card">
        <h3>Step 2 - Environment Checks</h3>
        <div class="grid">
            <div class="{{ $checks['env_file'] ? 'ok' : 'bad' }}">.env file: {{ $checks['env_file'] ? 'OK' : 'Missing' }}</div>
            <div class="{{ $checks['app_key'] ? 'ok' : 'bad' }}">APP_KEY: {{ $checks['app_key'] ? 'OK' : 'Missing' }}</div>
            <div class="{{ $checks['database'] ? 'ok' : 'bad' }}">Database: {{ $checks['database'] ? 'Reachable' : 'Not reachable' }}</div>
            <div class="{{ $checks['users_table'] ? 'ok' : 'bad' }}">Users table: {{ $checks['users_table'] ? 'Ready' : 'Missing (run migrate)' }}</div>
            <div class="{{ $checks['settings_table'] ? 'ok' : 'bad' }}">Platform settings table: {{ $checks['settings_table'] ? 'Ready' : 'Missing (run migrate)' }}</div>
        </div>
    </div>

    <div class="card">
        <h3>Step 3 - Finish Install (Create Superadmin)</h3>
        <div class="muted">This does not install any theme and does not auto-provision any site.</div>
        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('platform.install.complete') }}">
            @csrf
            <div class="grid">
                <label>
                    Admin Name
                    <input type="text" name="admin_name" value="{{ old('admin_name', 'Platform Admin') }}" required>
                </label>
                <label>
                    Admin Email
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" required>
                </label>
                <label>
                    Admin Password
                    <input type="password" name="admin_password" required>
                </label>
                <label>
                    Panel Scheme
                    <select name="panel_scheme" required>
                        <option value="http" {{ old('panel_scheme', $panelScheme) === 'http' ? 'selected' : '' }}>http</option>
                        <option value="https" {{ old('panel_scheme', $panelScheme) === 'https' ? 'selected' : '' }}>https</option>
                    </select>
                </label>
                <label>
                    Panel Host
                    <input type="text" name="panel_host" value="{{ old('panel_host', $panelHost) }}" required>
                </label>
                <label>
                    Panel Port
                    <input type="number" name="panel_port" min="1" max="65535" value="{{ old('panel_port', $panelPort) }}" required>
                </label>
            </div>
            <div style="margin-top:14px;">
                <button class="btn" type="submit">Complete Installation</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
