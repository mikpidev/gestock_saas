


<div class="gestok-container">
    <!-- Sidebar -->
    <div class="gestok-panel">
        @hasrole('superadmin|admin')
        <a href="{{ route('stores.dashboard', $store->id) }}">
            <i class="fas fa-box-open"></i> Dashboard
        </a>
        <a href="{{ route('stores.users.index', $store->id) }}">
            <i class="fas fa-users"></i> Usuarios
        </a>
        <a href="{{ route('stores.product_types.index', $store->id) }}">
            <i class="fas fa-box-open"></i> Productos
        </a>

        @endhasrole

        @hasrole('superadmin|admin|user')
        <a href="{{ route('stores.customers.index', $store->id) }}">
            <i class="fas fa-user-friends"></i> Clientes
        </a>
        <a href="{{ route('stores.sales.index', $store->id) }}">
            <i class="fas fa-cash-register"></i> Ventas
        </a>
        <a href="{{ route('stores.cash.closures.index', $store->id) }}">
            <i class="fas fa-box-open"></i> Corte de Caja
        </a>
        <a href="{{ route('stores.creditnotes.index', $store->id) }}">
            <i class="fas fa-file-invoice"></i> Notas de Crédito
        </a>
        <a href="{{ route('stores.debitnotes.index', $store->id) }}">
            <i class="fas fa-file-invoice-dollar"></i> Notas de Débito
        </a>
        @endhasrole
    </div>

</div>

<!-- Agregar FontAwesome para los iconos -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>