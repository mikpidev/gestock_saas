<style>
    .gestok-container {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar siempre expandido */
    .gestok-panel {
        width: 220px;
        background: #fff;
        border-right: 2px solid #000;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        padding: 1.5rem;
        gap: 1rem;
        min-height: 100vh;
    }

    /* Botones del menú optimizados para touch */
    .gestok-panel a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: bold;
        text-decoration: none;
        color: #000;
        background: #fff;
        border-bottom: 2px solid #8888888f;
        transition: all 0.2s ease;
    }

    .gestok-panel a:hover {
        background: #f0f0f0;
        transform: translateX(3px);
        box-shadow: 2px 2px 6px rgba(0,0,0,0.2);
    }

    /* Iconos dentro de los botones */
    .gestok-panel a i {
        font-size: 1.4rem;
        min-width: 24px;
        text-align: center;
    }

    /* Contenido principal */
    .gestok-main {
        flex: 1;
        padding: 2rem;
        background: #f2f2f2;
    }
</style>

<div class="gestok-container">
    <!-- Sidebar -->
    <div class="gestok-panel">
        @hasrole('superadmin|admin')
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
