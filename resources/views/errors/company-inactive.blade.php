<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta no disponible — Gestock</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        :root {
            --color-black: #1a1a1a;
            --color-muted: #5c5c5c;
            --color-bordes: #d9d9d9;
            --color-cta: #af2828;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--color-black);
            background: #f3f3f3;
            padding: 1.5rem;
        }

        .card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.10), 0 2px 10px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            text-align: center;
        }

        .card img {
            width: 70%;
            max-width: 220px;
            margin: 1.2rem auto 0;
            display: block;
        }

        .body {
            padding: 1.25rem 2rem 2rem;
        }

        .badge {
            display: inline-block;
            margin-bottom: 0.75rem;
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            background: #fff5f5;
            color: var(--color-cta);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 0.7rem;
            font-size: 1.45rem;
            line-height: 1.25;
        }

        p {
            margin: 0 0 0.85rem;
            color: var(--color-muted);
            line-height: 1.55;
            font-size: 0.95rem;
        }

        .company {
            margin: 0 0 1.25rem;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-bordes);
            border-radius: 10px;
            background: #fafafa;
            font-size: 0.9rem;
        }

        .company strong {
            display: block;
            color: var(--color-black);
            margin-bottom: 0.15rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0.7rem 1.15rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff0000 0%, #af2828 100%);
            color: #fff;
            box-shadow: 0 6px 15px rgba(175, 40, 40, 0.22);
        }

        .btn-ghost {
            background: #fff;
            color: var(--color-black);
            border-color: #8f8e8e;
        }

        form { margin: 0; }
    </style>
</head>
<body>
    @php
        $status = $company->status ?? null;
        $isSuspended = $status === 'suspendida';
        $statusLabel = match ($status) {
            'suspendida' => 'Suspendida',
            'inactiva' => 'Inactiva',
            default => $status ? ucfirst($status) : 'No disponible',
        };
    @endphp

    <main class="card">
        <img src="{{ asset('Logo.png') }}" alt="Gestock">
        <div class="body">
            <span class="badge">Acceso restringido</span>
            <h1>
                @if ($isSuspended)
                    Tu cuenta está suspendida
                @else
                    Tu cuenta no está activa
                @endif
            </h1>
            <p>
                @if ($isSuspended)
                    Gestock no está disponible por ahora para esta compañía. Si crees que se trata de un error, contacta a soporte.
                @else
                    Esta compañía no tiene acceso activo a Gestock. Para reactivar el servicio, contacta a soporte.
                @endif
            </p>

            @if ($company)
                <div class="company">
                    <strong>{{ $company->company_name }}</strong>
                    Estado: {{ $statusLabel }}
                </div>
            @endif

            <div class="actions">
                <a class="btn btn-ghost" href="{{ route('landing') }}#contacto">Contactar soporte</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
