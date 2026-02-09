<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Login - TastyPanel</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .card {
            width: min(440px, 92vw);
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .35);
        }
        h1 {
            margin: 0 0 4px;
            font-size: 30px;
        }
        p { margin: 0 0 18px; color: #64748b; }
        label { display: block; margin-bottom: 12px; font-size: 13px; color: #334155; }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 6px;
        }
        button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            color: #fff;
            background: #1d4ed8;
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
    </style>
</head>
<body>
    <div class="card">
        <h1>TastyPanel</h1>
        <p>Platform Admin Login</p>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        @if(session('success'))
            <div style="margin-bottom:12px;padding:10px;border-radius:8px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;font-size:14px;">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('platform.login') }}" method="POST">
            @csrf
            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
