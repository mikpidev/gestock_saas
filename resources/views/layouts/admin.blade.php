<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestok App - Admin</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- Custom CSS -->
    @vite('resources/css/custom.css')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --color-primario: #ffb548;
            --color-secundario: #ff6c37;
            --color-acento: #8e5928;
            --color-fondo: #ffffff;
            --color-fondo-secundario: #f9f6f2;
            --color-texto: #333333;
            --color-borde: #8e5928;
        }

        header {
            background: linear-gradient(135deg, var(--color-primario)0%, var(--color-secundario)100%);
            /* Fondo principal sólido */
            padding: 1rem 2rem;
            color: #fff;
            position: relative;
        }

        /* Gradiente detrás del logo */
        header::before {
            content: "";
            position: absolute;
            left: 0;
            /* Inicia desde la izquierda */
            top: 0;
            width: 150px;
            /* Ancho del gradiente */
            height: 100%;
            /* Ocupa toda la altura del header */
            z-index: 0;
            /* Debe estar detrás del contenido */
            border-radius: 0 8px 8px 0;
            /* Bordes suavizados opcional */
        }

        /* Logo encima del gradiente */
        header .navbar-brand {
            position: relative;
            z-index: 1;
            /* Logo sobre el gradiente */
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background-color: var(--color-fondo);
            border-right: 2px solid var(--color-borde);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .sidebar a {
            color: var(--color-texto);
            font-weight: 500;
            padding: 0.75rem;
            border-bottom: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .sidebar a:hover {
            color: var(--color-primario);
            background-color: var(--color-fondo-secundario);
            border-color: var(--color-primario);
        }

        /* Botones */
        .btn-primary {
            background-color: var(--color-primario);
            border-color: var(--color-primario);
        }

        .btn-primary:hover {
            background-color: var(--color-secundario);
            border-color: var(--color-acento);
        }

        .btn-secondary {
            background-color: var(--color-fondo);
            color: var(--color-acento);
            border: 2px solid var(--color-acento);
        }

        .btn-secondary:hover {
            background-color: var(--color-acento);
            color: #fff;
        }

        /* Tarjetas */
        .card {
            background-color: var(--color-fondo);
            border: 1px solid var(--color-borde);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        /* Inputs */
        input,
        select,
        textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.95rem;
            color: var(--color-texto);
            background-color: var(--color-fondo-secundario);
            transition: border-color 0.2s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--color-primario);
            outline: none;
            background-color: #fff;
        }

        /* Footer */
        footer {
            background-color: var(--color-fondo-secundario);
            color: var(--color-texto);
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="d-flex align-items-center justify-content-between mb-4">
        <a class="d-flex align-items-center" href="{{ route('home') }}">
            <img src="{{ asset('Logo.png') }}" alt="Logo Gestok"
                style="width: 100px; height: auto; filter: drop-shadow(1px 1px 3px rgba(0,0,0,0.3));">
        </a>

        <!-- Dropdown usuario -->
        <ul class="navbar-nav ms-auto align-items-center list-unstyled d-flex mb-0">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle fw-semibold text-white" href="#" id="userDropdown"
                    role="button" data-bs-toggle="dropdown">
                    {{ Auth::user()->name }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow" style="border-radius:6px;">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit', Auth::user()->id) }}"
                            style="color: var(--color-acento); font-weight:500;">Perfil</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item"
                                style="color: var(--color-secundario); font-weight:600;">Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </header>

    <!-- Main -->
    <main class="container-fluid mt-4">
        @if(isset($store) && request()->routeIs('stores.*') && !request()->routeIs('stores.index'))
        <div class="d-flex">
            <!-- Sidebar -->
            <div class="flex-shrink-0 me-4 sidebar">
                <x-store-panel :store="$store" />
            </div>

            <!-- Contenido principal -->
            <div class="flex-grow-1">
                @yield('content')
            </div>
        </div>
        @else
        <div>
            @yield('content')
        </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="text-center py-3 mt-auto">
        <div class="container">
            <small>&copy; {{ date('Y') }} Gestok App. Todos los derechos reservados.</small>
        </div>
    </footer>

    <!-- Modal -->
    <div class="modal fade" id="gestokModal" tabindex="-1" aria-labelledby="gestokModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gestokModalLabel">Gestok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center">Aquí se cargará el contenido...</p>
                </div>
            </div>
        </div>
    </div>

    @yield('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>
</body>

</html>