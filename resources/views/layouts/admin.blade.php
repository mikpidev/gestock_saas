<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestok App - Admin</title>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">


    <link rel="icon" type="image/png"  href="{{ asset('favicon.png') }}">


    <!-- Custom CSS -->
    @vite('resources/css/custom.css')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body>
    <!-- Header -->
    <header class="d-flex align-items-center justify-content-between mb-4">
        <a class="d-flex align-items-center" href="{{ route('home') }}">
            <img src="{{ asset('Logo.png') }}" alt="Logo Gestok" style="height: 150px; margin-bottom: -35px; margin-top: -35px;">
        </a>

        <!-- Dropdown usuario -->
        <ul class="navbar-nav ms-auto align-items-center list-unstyled d-flex mb-0">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle fw-semibold text-black" href="#" id="userDropdown"
                    role="button" data-bs-toggle="dropdown">
                    {{ Auth::user()->name }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow" style="border-radius:6px;">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit', Auth::user()->id) }}"
                            style="color: var(--color-black); font-weight:500;">Perfil</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item gradient-text">Cerrar sesión</button>
                            </button>
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
            <div class="flex-grow-1" class="grandient-text">
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


<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $('#gestokModal').on('shown.bs.modal', function() {

        $('.select2').select2({
            dropdownParent: $('#gestokModal'),
            placeholder: "Seleccione una opción",
            allowClear: true,
            width: '100%'
        });

    });
</script>

</html>