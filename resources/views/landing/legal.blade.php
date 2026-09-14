@extends('layouts.landing')

@section('title', $title . ' — Gestock')
@section('meta_description', $heading)

@php
    $logo = 'https://i.ibb.co/605r0jry/Gestock.png';
@endphp

@section('content')
<header class="lp-nav">
    <div class="lp-wrap lp-nav__inner">
        <a href="{{ route('landing') }}" class="lp-logo">
            <img src="{{ $logo }}" alt="Gestock">
        </a>
        <div class="lp-nav__actions" style="display:flex;">
            <a class="lp-btn lp-btn--ghost" href="{{ route('landing') }}">Volver al inicio</a>
        </div>
    </div>
</header>
<main class="lp-legal">
    <div class="lp-wrap">
        <article>
            <h1>{{ $heading }}</h1>
            <p class="lp-lead">Última actualización: {{ $updated }}</p>
            @foreach ($sections as $section)
                <h2>{{ $section['title'] }}</h2>
                <p>{{ $section['body'] }}</p>
            @endforeach
        </article>
    </div>
</main>
<footer class="lp-footer">
    <div class="lp-wrap">
        <small>© 2026 Gestock. Todos los derechos reservados.</small>
    </div>
</footer>
@endsection
