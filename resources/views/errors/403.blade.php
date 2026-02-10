<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 grid place-items-center p-6">
    <div class="w-full max-w-xl">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-2xl">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-widest text-slate-400">TastyPanel</div>
                    <h1 class="mt-2 text-2xl font-bold">Access denied</h1>
                </div>
                <div class="text-xs font-mono px-3 py-2 rounded-lg bg-white/10 border border-white/10">
                    403
                </div>
            </div>

            <p class="mt-4 text-sm text-slate-300">
                You don't have permission to access this page.
            </p>

            <div class="mt-5 rounded-xl bg-black/20 border border-white/10 p-4">
                <div class="text-xs text-slate-400 uppercase tracking-widest font-semibold">Request</div>
                <div class="mt-2 text-sm">
                    <span class="text-slate-300">IP:</span>
                    <span class="font-mono">{{ request()->ip() }}</span>
                </div>
            </div>

            <div class="mt-5 text-xs text-slate-400">
                If you enabled an IP allowlist, add your IP in platform Settings (panel_allowed_ips) and try again.
            </div>
        </div>
    </div>
</body>
</html>

