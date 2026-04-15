<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dominio no encontrado</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji"; background: #0b1220; color: #e5e7eb; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 720px; border-radius: 16px; background: rgba(17,24,39,.92); border: 1px solid rgba(255,255,255,.08); box-shadow: 0 24px 80px rgba(0,0,0,.4); overflow: hidden; }
        .header { padding: 22px 22px 12px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .title { font-size: 18px; font-weight: 700; letter-spacing: .2px; margin: 0; }
        .body { padding: 18px 22px 22px; }
        .domain { display: inline-block; padding: 6px 10px; border-radius: 10px; background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.25); color: #a7f3d0; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; }
        .hint { margin: 12px 0 0; color: rgba(229,231,235,.8); line-height: 1.5; }
        .actions { margin-top: 18px; display: flex; gap: 12px; flex-wrap: wrap; }
        a.btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px; border: 1px solid rgba(255,255,255,.12); color: #e5e7eb; background: rgba(255,255,255,.04); }
        a.btn.primary { border-color: rgba(16,185,129,.35); background: rgba(16,185,129,.14); color: #d1fae5; }
        a.btn:hover { filter: brightness(1.08); }
        .footer { padding: 14px 22px; border-top: 1px solid rgba(255,255,255,.08); color: rgba(229,231,235,.6); font-size: 12px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="header">
                <h1 class="title">No se encuentra el dominio seleccionado</h1>
            </div>
            <div class="body">
                <div class="domain">{{ $domain ?? '—' }}</div>
                <p class="hint">
                    Este dominio no corresponde a ninguna empresa activa en el sistema. Es posible que la empresa haya sido eliminada o que el dominio esté mal escrito.
                </p>
                <div class="actions">
                    <a class="btn primary" href="/central">Ir al panel central</a>
                    <a class="btn" href="/app">Ir al panel de empresa</a>
                </div>
            </div>
            <div class="footer">
                Código: 404
            </div>
        </div>
    </div>
</body>
</html>
