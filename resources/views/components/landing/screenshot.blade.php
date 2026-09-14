@props([
    'src' => null,
    'title',
    'description' => null,
    'alt' => null,
    'caption' => true,
])

<figure {{ $attributes->class('lp-shot') }}>
    <div class="lp-browser">
        <div class="lp-browser__bar" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
            <div class="lp-browser__url">gestock.site</div>
        </div>
        <div class="lp-browser__body">
            @if ($src)
                <img src="{{ $src }}" alt="{{ $alt ?? $title }}">
            @else
                <div class="lp-placeholder" role="img" aria-label="Captura pendiente: {{ $title }}">
                    <span class="lp-placeholder__badge">Captura pendiente</span>
                    <strong>{{ $title }}</strong>
                    <p>Reemplaza este espacio con la captura real en <code>public/images/landing/</code></p>
                </div>
            @endif
        </div>
    </div>
    @if ($caption)
        <figcaption>
            <h3>{{ $title }}</h3>
            @if ($description)
                <p>{{ $description }}</p>
            @endif
        </figcaption>
    @endif
</figure>
