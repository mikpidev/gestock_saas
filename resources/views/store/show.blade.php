@extends('layouts.admin')

@section('content')
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
        box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }

    .gestok-panel .menu-btn:hover {
        background: #f0f0f0;
        transform: translateY(-3px);
        box-shadow: 4px 4px 10px rgba(0,0,0,0.15);
    }

    /* Icono dentro del botón */
    .gestok-panel .menu-btn svg {
        width: 48px;
        height: 48px;
        margin-bottom: 10px;
    }
</style>
<div class="gestok-form-card">
    <div class="gestok-form-header">
        <h1>{{ $store->store_name }}</h1>

    </div>






    </div>
</div>

@endsection
