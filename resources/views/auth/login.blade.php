<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &bull; Snaprint ERP</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --o-primary-color: #1E3A8A;
            --o-accent-color: #2563EB;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 50%, #1E40AF 100%) !important;
            min-height: 100vh;
            color: #ffffff;
        }
        .btn-odoo-primary {
            background-color: #2563EB;
            color: #ffffff;
            border: 1px solid #1D4ED8;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 16px;
            transition: all 0.15s ease;
        }
        .btn-odoo-primary:hover {
            background-color: #1D4ED8;
            border-color: #1D4ED8;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="container" style="max-width: 420px;">
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-lg p-1 mb-3" style="width: 84px; height: 84px;">
                <img src="{{ asset('images/logosnaprint.jpeg') }}" alt="Snaprint Logo" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <h3 class="fw-bold text-white mb-1" style="letter-spacing: -0.5px;">Snaprint <span style="color: #93c5fd;">ERP</span></h3>
            <p class="mb-0 text-white" style="font-size: 13px; font-weight: 500; opacity: 0.9;">"great spot to print"</p>
        </div>

        <!-- Login Form Card -->
        <div class="card shadow-2xl border-0 rounded-4 bg-white p-4" style="color: #1e293b;">
            @if($errors->any())
                <div class="alert alert-danger py-2 px-3 text-xs mb-3 rounded-3" role="alert" style="font-size: 12px;">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-3">
                @csrf
                
                <div class="mb-3">
                    <label for="username" class="form-label fw-bold text-slate-700 text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #475569;">Username / ID Pengguna</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-user text-xs"></i></span>
                        <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                            placeholder="Masukkan username" class="form-control form-control-sm border-start-0 ps-1" style="font-size: 13px; padding: 9px 12px; background-color: #f8fafc;">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-bold text-slate-700 text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #475569;">Kata Sandi (Password)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa-solid fa-lock text-xs"></i></span>
                        <input type="password" id="password" name="password" required
                            placeholder="••••••••" class="form-control form-control-sm border-start-0 ps-1" style="font-size: 13px; padding: 9px 12px; background-color: #f8fafc;">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-odoo-primary w-100 fs-6 shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Masuk ke Sistem</span>
                    </button>
                </div>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <small class="text-muted" style="font-size: 11px;">
                    <i class="fa-solid fa-shield-halved me-1 text-primary"></i> Multi-Role Access Control (RBAC)
                </small>
            </div>
        </div>

        <div class="text-center mt-4 text-white" style="font-size: 12px; opacity: 0.85;">
            Powered by <strong class="text-white">Snaprint Enterprise Engine</strong> &bull; &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
