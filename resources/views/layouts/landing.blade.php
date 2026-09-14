<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Gestock — Facturación electrónica y control de ventas')</title>
    <meta name="description" content="@yield('meta_description', 'Gestock es una plataforma en la nube para facturación electrónica, ventas, productos, clientes y reportes. Diseñada para negocios en El Salvador.')">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body class="lp" x-data="{ menu: false, contact: {{ $errors->any() ? 'true' : 'false' }} }">
    @yield('content')
</body>
</html>
