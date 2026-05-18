<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestock - Login')</title>
    <style>
        :root {
            --color-primario: #111111;
            --color-secundario: #1c1c1c;
            --color-acento: #ffffff;
            --color-border: #d9d9d9;
            --color-text: #1a1a1a;
            --color-muted: #777777;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg,
                    #f8f8f8 0%,
                    #efefef 45%,
                    #dcdcdc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .auth-card {
            width: 100%;
            max-width: 400px;
            border-radius: 18px;
            overflow: hidden;
            animation: fadeIn 0.6s ease;

            background: #ffffff;

            box-shadow:
                0 12px 35px rgba(0, 0, 0, 0.10),
                0 2px 10px rgba(0, 0, 0, 0.04);

            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .auth-header {
            padding: 0;
            overflow: hidden;
            background: #000;
        }

        .auth-header img {
            width: 100%;
            display: block;
            object-fit: cover;
            margin: 0;
        }

        .auth-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
            color: #fff;
        }

        .auth-header p {
            font-size: 0.9rem;
            margin-top: 0.5rem;
            color: #d1d1d1;
        }

        .auth-body {
            padding: 2rem;
            color: var(--color-text);
        }

        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #2a2a2a;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 0.8rem 0.9rem;
            margin-bottom: 1rem;
            background: #fafafa;
            border: 1px solid #dcdcdc;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #222;
            transition: all 0.25s ease;
        }

        input::placeholder {
            color: #999;
        }

        input:focus {
            border-color: #888;
            outline: none;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.04);
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            color: var(--color-muted);
            font-size: 0.88rem;
        }

        input[type="checkbox"] {
            margin-right: 0.4rem;
        }

        .btn {
            width: 50%;
            background: linear-gradient(180deg,
                    #1a1a1a 0%,
                    #000000 100%);
            color: #fff;
            border: none;
            padding: 0.85rem 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.96rem;
            font-weight: 600;
            transition: all 0.25s ease;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
        }

        .btn:hover {
            background: linear-gradient(180deg,
                    #2a2a2a 0%,
                    #111111 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.15);
        }

        .link {
            font-size: 0.85rem;
            color: #555;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .link:hover {
            color: #000;
        }

        .register,
        .extra {
            text-align: center;
            margin-top: 1.3rem;
            font-size: 0.9rem;
            color: #666;
        }

        .register a,
        .extra a {
            color: #000;
            font-weight: 600;
            text-decoration: none;
        }

        .register a:hover,
        .extra a:hover {
            text-decoration: underline;
        }

        .alert {
            background: #fafafa;
            color: #222;
            padding: 0.9rem 1rem;
            border-left: 4px solid #000;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            border: 1px solid #ececec;
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <div class="auth-header">
            <img src="{{ asset('Logo.png') }}" alt="Logo Gestok">

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