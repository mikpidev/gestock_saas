<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestok - Login')</title>
    <style>
        :root {
            --color-primario: #ffb548;
            --color-secundario: #ff6c37;
            --color-acento: #8e5928;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, var(--color-primario) 0%, var(--color-secundario) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .auth-card {
            background: #fff;
            color: #000;
            width: 100%;
            max-width: 400px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-header {
            position: relative;
            background: linear-gradient(360deg, var(--color-primario) 75%, var(--color-secundario) 125%);
            color: #fff;
            padding: 2rem 1.5rem;
            text-align: center;
            overflow: hidden;
        }

        /* Oscurece un poco el fondo para que el logo resalte más */
        .auth-header::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.15);
            z-index: 0;
        }

        .auth-header img {
            position: relative;
            z-index: 1;
            filter: brightness(1.2) contrast(1.1) ;
            width: 180px;
            margin-bottom: 0.5rem;
        }

        .auth-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
        }

        .auth-header p {
            font-size: 0.9rem;
            margin-top: 0.5rem;
            color: #ffd9b3;
        }

        .auth-body {
            padding: 2rem;
        }

        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            font-weight: bold;
            color: var(--color-acento);
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 0.7rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.95rem;
            color: #000;
            transition: border-color 0.3s ease;
        }

        input:focus {
            border-color: var(--color-secundario);
            outline: none;
            background: #fffaf5;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        input[type="checkbox"] {
            margin-right: 0.4rem;
        }

        .btn {
            background: var(--color-secundario);
            color: #fff;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: bold;
            transition: background 0.3s ease, transform 0.1s ease;
        }

        .btn:hover {
            background: var(--color-acento);
            transform: scale(1.03);
        }

        .link {
            font-size: 0.85rem;
            color: var(--color-acento);
            text-decoration: underline;
        }

        .link:hover {
            text-decoration: none;
            color: var(--color-secundario);
        }

        .register,
        .extra {
            text-align: center;
            margin-top: 1.2rem;
            font-size: 0.9rem;
        }

        .register a,
        .extra a {
            color: var(--color-secundario);
            font-weight: bold;
        }

        .alert {
            background: #fff3f3;
            color: #b91c1c;
            padding: 0.8rem 1rem;
            border-left: 4px solid var(--color-secundario);
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <div class="auth-header">
            <img src="{{ asset('logo.png') }}" alt="Logo Gestok" width="150">

            <p>@yield('subtitle', 'Inicio de sesión')</p>
        </div>

        <div class="auth-body">
            @if (session('error'))
            <div class="alert">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>


    </div>
</body>

</html>