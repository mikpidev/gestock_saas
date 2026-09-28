@extends('layouts.landing')

@section('title', 'Gestock Knowledge Base')
@section('meta_description', 'Base de conocimiento de Gestock en desarrollo. Todavía no hay artículos publicados.')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@php
    $logo = asset('Logo.png');
@endphp

@section('content')
<aside class="kb-banner" role="status" aria-label="Estado de la base de conocimiento">
    <p class="kb-banner__lead"><strong>En desarrollo / Under construction</strong></p>
    <p class="kb-banner__detail">Esta base de conocimiento todavía no está publicada. Las tarjetas son ejemplos y no abren artículos.</p>
</aside>

<header class="kb-top">
    <div class="lp-wrap kb-top__inner">
        <a href="{{ $homeUrl }}" class="lp-logo">
            <img src="{{ $logo }}" alt="Gestock">
        </a>
        <a class="lp-btn lp-btn--ghost kb-home" href="{{ $homeUrl }}">Ir a Gestock</a>
    </div>
</header>

<main>
    <section class="kb-hero">
        <div class="lp-wrap">
            <h1>Gestock Knowledge Base</h1>
            <p>Busca un tema o recorre las categorías de ejemplo. Esta pantalla es un prototipo visual.</p>
            <form class="kb-search" method="get" action="{{ url()->current() }}" role="search">
                <label class="kb-sr" for="kb-q">Buscar en la base de conocimiento</label>
                <span class="kb-search__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <circle cx="11" cy="11" r="6.5" />
                        <path d="M16 16l4 4" />
                    </svg>
                </span>
                <input id="kb-q" type="search" name="q" value="{{ $query }}" maxlength="120" placeholder="Buscar en la base de conocimiento" autocomplete="off" aria-describedby="kb-search-note">
                <button type="submit" class="lp-btn lp-btn--primary">Buscar</button>
            </form>
            <p id="kb-search-note" class="kb-search-status">
                @if ($query !== '')
                    Sin resultados para “{{ $query }}”. La búsqueda todavía no está disponible. En desarrollo / Under construction.
                @else
                    Búsqueda de ejemplo. No consulta artículos reales.
                @endif
            </p>
        </div>
    </section>

    <section class="kb-grid-wrap" aria-label="Categorías de ejemplo">
        <div class="lp-wrap">
            <div class="kb-grid">
                @foreach ($categories as $category)
                    <article class="lp-card kb-card">
                        <div class="lp-icon">
                            @include('kb.partials.icon', ['name' => $category['icon']])
                        </div>
                        <h2>{{ $category['title'] }}</h2>
                        <p>{{ $category['summary'] }}</p>
                        <span class="kb-chip">Próximamente</span>
                    </article>
                @endforeach
            </div>
            <p class="kb-footnote">Los artículos se publicarán aquí cuando la base de conocimiento esté lista.</p>
        </div>
    </section>
</main>

<footer class="lp-footer">
    <div class="lp-wrap">
        <p><a href="{{ $homeUrl }}">Gestock</a></p>
        <small>© 2026 Gestock. Base de conocimiento en desarrollo.</small>
    </div>
</footer>
@endsection
