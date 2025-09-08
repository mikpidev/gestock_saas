<style>
    /* Contenedor del panel */
    .gestok-panel {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
        padding: 2rem 0;
        background: #fdfdfd;
        min-height: 80vh;
    }

    /* Botones del menú */
    .gestok-panel .menu-btn {
        width: 140px;
        height: 140px;
        background: #fff;
        color: #000;
        border: 2px solid #000;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: bold;
        font-size: 1rem;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .gestok-panel .menu-btn:hover {
        background: #f0f0f0;
        transform: translateY(-3px);
        box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.15);
    }

    /* Icono dentro del botón */
    .gestok-panel .menu-btn svg {
        width: 48px;
        height: 48px;
        margin-bottom: 10px;
    }
</style>
<header>
    <div class="gestok-panel">
        <!-- Usuarios -->
        @hasrole('superadmin|admin')
            <a href="{{ route('stores.users.index', $store->id) }}" class="menu-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                    <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
                </svg>
                Usuarios
            </a>
        @endhasrole

        @hasrole('superadmin|admin')
        <!-- Productos -->
            <a href="{{ route('stores.product_types.index', $store->id) }}" class="menu-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-box" viewBox="0 0 16 16">
                    <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z" />
                </svg>
                Productos
            </a>
        @endhasrole

        @hasrole('superadmin|admin|user')
            <a href="{{ route('stores.customers.index', $store->id) }}" class="menu-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person-vcard" viewBox="0 0 16 16">
                    <path d="M5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4m4-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5M9 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3A.5.5 0 0 1 9 8m1 2.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5"/>
                    <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM1 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1z"/>
                </svg>
                Clientes
            </a>
        @endhasrole

    </div>
</header>