<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestok - Login')</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f2f2f2;
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
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            background: #000;
            color: #fff;
            padding: 2rem 1.5rem;
            text-align: center;
        }

        .auth-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }

        .auth-header p {
            font-size: 0.9rem;
            margin-top: 0.5rem;
            color: #ddd;
        }

        .auth-body {
            padding: 2rem;
        }

        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            font-weight: bold;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 0.7rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.95rem;
            color: #000;
        }

        input:focus {
            border-color: #000;
            outline: none;
            background: #fff;
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
            background: #000;
            color: #fff;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #333;
        }

        .link {
            font-size: 0.85rem;
            color: #000;
            text-decoration: underline;
        }

        .link:hover {
            text-decoration: none;
        }

        .register, .extra {
            text-align: center;
            margin-top: 1.2rem;
            font-size: 0.9rem;
        }

        .register a, .extra a {
            color: #000;
            font-weight: bold;
        }

        .alert {
            background: #fee2e2;
            color: #b91c1c;
            padding: 0.8rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <h1>Gestok</h1>
            <p>@yield('subtitle', 'Inicio de sesión')</p>
        </div>

        <div class="auth-body">
            @if (session('error'))
                <div class="alert">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>

        <div class="register">
            <p>¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
        </div>
    </div>
</body>
</html>
