<style>
    :root {
        --color-primario: #ffb548;
        --color-secundario: #ff6c37;
        --color-acento: #8e5928;
        --color-fondo-panel: #fff;
        --color-texto: #000;
        --color-borde: #8e5928;
    }

    /* ===== Contenedor general ===== */
    .gestok-container {
        display: flex;
        min-height: 100vh;
    }

    /* ===== Sidebar siempre expandido ===== */
    .gestok-panel {
        width: 220px;
        background: var(--color-fondo-panel);
        display: flex;
        flex-direction: column;
        align-items: stretch;
        padding: 0;
        /* eliminamos padding global para alinear borde */
        min-height: 100vh;
    }

    .gestok-panel-inner {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        height: 100%;
        box-sizing: border-box;
    }

    /* ===== Botones del menú optimizados para touch ===== */
    .gestok-panel a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: bold;
        text-decoration: none;
        color: var(--color-texto);
        background: var(--color-fondo-panel);
        border-bottom: 2px solid var(--color-borde);
        transition: all 0.2s ease;
        box-sizing: border-box;
    }

    .gestok-panel a:hover {
        color: var(--color-secundario);
        border-bottom-color: var(--color-primario);
        transform: translateX(3px);
        box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.2);
    }

    /* Iconos dentro de los botones */
    .gestok-panel a i {
        font-size: 1.4rem;
        min-width: 24px;
        text-align: center;
        color: inherit;
    }

    /* Contenido principal */
    .gestok-main {
        flex: 1;
        padding: 2rem;
        background: #f2f2f2;
    }

    /* ===== Media Queries ===== */
    @media (max-width: 992px) {
        .gestok-container {
            flex-direction: column;
        }

        .gestok-panel {
            width: 100%;
            border-right: none;
            border-bottom: 2px solid var(--color-borde);
        }
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