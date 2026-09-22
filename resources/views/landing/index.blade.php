@extends('layouts.landing')

@php
$logo = file_exists(public_path('Logo.png')) ? asset('Logo.png') : asset('Logo.png');
$tourKeys = ['dashboard', 'ventas', 'dte', 'productos', 'clientes', 'reportes', 'usuarios'];
@endphp

@section('content')
<header class="lp-nav">
    <div class="lp-wrap lp-nav__inner">
        <a href="{{ route('landing') }}" class="lp-logo">
            <img src="{{ $logo }}" alt="Gestock">
        </a>
        <nav class="lp-nav__links" aria-label="Principal">
            <a href="#inicio">Inicio</a>
            <a href="#caracteristicas">Características</a>
            <a href="#reportes">Reportes</a>
            <a href="#planes">Planes</a>
            <a href="#api">API</a>
            <a href="#faq">Preguntas frecuentes</a>
            <a href="#contacto">Contacto</a>
        </nav>
        <div class="lp-nav__actions">
            @auth
            <a class="lp-btn lp-btn--ghost" href="{{ route('home') }}">Ir al sistema</a>
            @else
            <a class="lp-btn lp-btn--ghost" href="{{ route('login') }}">Ingresar</a>
            @endauth
            <button type="button" class="lp-btn lp-btn--primary" @click="contact = true">Solicitar información</button>
        </div>
        <button type="button" class="lp-menu-btn" @click="menu = !menu" aria-label="Abrir menú">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
        </button>
    </div>
    <div class="lp-wrap lp-drawer" :class="{ 'is-open': menu }">
        <a href="#inicio" @click="menu = false">Inicio</a>
        <a href="#caracteristicas" @click="menu = false">Características</a>
        <a href="#reportes" @click="menu = false">Reportes</a>
        <a href="#planes" @click="menu = false">Planes</a>
        <a href="#api" @click="menu = false">API</a>
        <a href="#faq" @click="menu = false">Preguntas frecuentes</a>
        <a href="#contacto" @click="menu = false">Contacto</a>
        @auth
        <a href="{{ route('home') }}">Ir al sistema</a>
        @else
        <a href="{{ route('login') }}">Ingresar</a>
        @endauth
        <button type="button" class="lp-btn lp-btn--primary" @click="menu = false; contact = true">Solicitar información</button>
    </div>
</header>

<main>
    <section id="inicio" class="lp-hero">
        <div class="lp-wrap lp-hero__grid">
            <div class="lp-reveal">
                <span class="lp-kicker">Plataforma en la nube · El Salvador</span>
                <h1>Facturación electrónica y control de ventas, todo en un solo lugar.</h1>
                <p class="lp-lead">Gestock simplifica la facturación electrónica, las ventas, el control de productos y los reportes de tu negocio desde una plataforma segura en la nube.</p>
                <div class="lp-cta-row">
                    <button type="button" class="lp-btn lp-btn--primary" @click="contact = true">Solicitar información</button>
                    <a class="lp-btn lp-btn--ghost" href="#producto">Conocer Gestock</a>
                </div>
            </div>
            <div class="lp-reveal">
                <x-landing.screenshot
                    :src="$screenshots['dashboard']['src']"
                    :title="$screenshots['dashboard']['title']"
                    :description="$screenshots['dashboard']['description']"
                    :caption="false"
                    alt="Dashboard de Gestock" />
            </div>
        </div>
    </section>

    <section id="caracteristicas" class="lp-section lp-section--alt">
        <div class="lp-wrap">
            <div class="lp-section__head lp-reveal">
                <h2>Todo lo que necesitas para administrar tus ventas, sin complicarte.</h2>
            </div>
            <div class="lp-grid-3">
                @foreach ([
                ['Facturación electrónica', 'Genera y gestiona tus documentos tributarios electrónicos desde una sola plataforma.'],
                ['En la nube', 'Accede a Gestock desde cualquier lugar con conexión a Internet. No necesitas instalar el sistema en cada computadora.'],
                ['Control de ventas', 'Registra y consulta tus ventas fácilmente y mantén toda la información organizada.'],
                ['Reportes', 'Obtén información clara sobre el comportamiento de tus ventas para tomar mejores decisiones.'],
                ['Control de productos', 'Mantén organizados tus productos, precios y disponibilidad dentro del sistema.'],
                ['Multiusuario', 'Permite trabajar con diferentes usuarios y administrar el acceso al sistema.'],
                ] as $item)
                <article class="lp-card lp-reveal">
                    <div class="lp-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="12" r="8" />
                            <path d="M8 12l2.5 2.5L16 9" />
                        </svg>
                    </div>
                    <h3>{{ $item[0] }}</h3>
                    <p>{{ $item[1] }}</p>
                </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="producto" class="lp-section" x-data="{ tab: 'dashboard' }">
        <div class="lp-wrap">
            <div class="lp-section__head lp-reveal">
                <h2>Conoce Gestock por dentro</h2>
                <p>Una plataforma diseñada para que la operación diaria de tu negocio sea más sencilla.</p>
            </div>
            <div class="lp-tabs lp-reveal" role="tablist">
                @foreach ($tourKeys as $key)
                <button type="button" class="lp-tab" :class="{ 'is-active': tab === '{{ $key }}' }" @click="tab = '{{ $key }}'">
                    {{ $screenshots[$key]['title'] }}
                </button>
                @endforeach
            </div>
            @foreach ($tourKeys as $key)
            <div class="lp-reveal" x-show="tab === '{{ $key }}'" x-cloak>
                <x-landing.screenshot
                    :src="$screenshots[$key]['src']"
                    :title="$screenshots[$key]['title']"
                    :description="$screenshots[$key]['description']" />
            </div>
            @endforeach
        </div>
    </section>

    <section id="reportes" class="lp-section lp-section--alt">
        <div class="lp-wrap lp-split">
            <div class="lp-reveal">
                <h2>Convierte tus ventas en información útil</h2>
                <p class="lp-lead">Los reportes de Gestock te ayudan a conocer el comportamiento de tu negocio: cuánto vendes, con qué frecuencia y cómo van tus documentos tributarios.</p>
                <div class="lp-metrics">
                    <div class="lp-card lp-metric"><span>Ventas totales</span><strong>Periodo</strong></div>
                    <div class="lp-card lp-metric"><span>Cantidad de ventas</span><strong>Conteo</strong></div>
                    <div class="lp-card lp-metric"><span>Ventas por día</span><strong>Tendencia</strong></div>
                    <div class="lp-card lp-metric"><span>Estado de DTE</span><strong>Procesados</strong></div>
                </div>
            </div>
            <div class="lp-reveal">
                <x-landing.screenshot
                    :src="$screenshots['reportes']['src']"
                    title="Reportes"
                    description="Analiza tus ventas y obtén información útil para tomar decisiones."
                    :caption="false" />
                @unless ($screenshots['reportes']['src'])
                <div class="lp-card" style="margin-top: 1rem;">
                    <span style="font-size:.8rem;font-weight:600;color:var(--lp-muted);">Vista ilustrativa de ventas por día</span>
                    <div class="lp-bars" aria-hidden="true">
                        <i style="height:42%"></i><i style="height:68%"></i><i style="height:55%"></i>
                        <i style="height:86%"></i><i style="height:47%"></i><i style="height:73%"></i>
                        <i style="height:61%"></i>
                    </div>
                </div>
                @endunless
            </div>
        </div>
    </section>

    <section id="productos" class="lp-section">
        <div class="lp-wrap lp-split">
            <div class="lp-reveal">
                <x-landing.screenshot
                    :src="$screenshots['productos']['src']"
                    title="Productos"
                    description="Organiza tus productos, precios y la información necesaria para realizar tus ventas."
                    :caption="false" />
            </div>
            <div class="lp-reveal">
                <h2>Mantén tus productos organizados</h2>
                <p class="lp-lead">Gestiona fácilmente los productos que utilizas en tus ventas, sus precios y la información necesaria para mantener tu operación organizada.</p>
                <p class="lp-lead">Es control y organización de productos para tus ventas, pensado para apoyar el día a día del negocio.</p>
            </div>
        </div>
    </section>

    <section id="facturacion" class="lp-section lp-section--alt">
        <div class="lp-wrap lp-split">
            <div class="lp-reveal">
                <h2>Facturación electrónica más sencilla</h2>
                <p class="lp-lead">Gestock permite generar y gestionar documentos tributarios electrónicos desde la plataforma, con la información de tus ventas centralizada.</p>
                <ul class="lp-list">
                    <li>Menos procesos manuales al trabajar ventas y documentos en un mismo lugar.</li>
                    <li>Documentos organizados y disponibles desde la nube.</li>
                    <li>Una operación más simple, accesible desde el navegador.</li>
                </ul>
            </div>
            <div class="lp-reveal">
                <x-landing.screenshot
                    :src="$screenshots['dte']['src']"
                    title="Facturación electrónica"
                    description="Genera y gestiona tus documentos tributarios electrónicos desde la misma plataforma."
                    :caption="false" />
            </div>
        </div>
    </section>

    <section id="nube" class="lp-section">
        <div class="lp-wrap lp-split">
            <div class="lp-cloud lp-reveal" aria-hidden="true">
                <svg viewBox="0 0 420 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="70" y="108" width="280" height="92" rx="10" fill="#fff" stroke="#8f8e8e" />
                    <rect x="84" y="122" width="252" height="64" rx="6" fill="#f3f3f3" />
                    <rect x="150" y="196" width="120" height="10" rx="3" fill="#d9d9d9" />
                    <rect x="175" y="208" width="70" height="6" rx="3" fill="#d9d9d9" />
                    <path d="M168 78c0-22 18-40 40-40 16 0 30 9 37 23 6-4 13-6 21-6 22 0 40 18 40 40 0 3 0 6-1 8H169c-1-8-1-16-1-25z" fill="#fff" stroke="#af2828" stroke-width="2" />
                    <text x="210" y="158" text-anchor="middle" font-size="18" font-family="Figtree, sans-serif" fill="#1a1a1a" font-weight="700">Gestock</text>
                    <text x="210" y="178" text-anchor="middle" font-size="11" font-family="Figtree, sans-serif" fill="#5c5c5c">Acceso desde el navegador</text>
                </svg>
            </div>
            <div class="lp-reveal">
                <h2>Tu negocio, disponible desde cualquier lugar</h2>
                <p class="lp-lead">Gestock funciona en la nube para que puedas acceder a tu información desde una computadora con conexión a Internet, sin depender de una instalación local.</p>
                <ul class="lp-list">
                    <li>Acceso desde cualquier lugar con Internet.</li>
                    <li>Sin instalaciones complicadas en cada equipo.</li>
                    <li>Información centralizada.</li>
                    <li>Actualizaciones y mantenimiento centralizados.</li>
                    <li>Acceso mediante navegador.</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="planes" class="lp-section lp-section--alt">
        <div class="lp-wrap">

            <div class="lp-section__head lp-reveal" style="text-align:center;margin-inline:auto;">
                <h2>Un plan simple para tu negocio</h2>
                <p>
                    Elige el plan que mejor se adapte al volumen de facturación de tu negocio.
                </p>
            </div>

            <div class="lp-grid-3 lp-plans">
            <!-- Plan Básico -->
            <article class="lp-card lp-price lp-reveal">
                <span class="lp-kicker">Para comenzar</span>

                <h3 style="margin-top:1rem;">Gestock Básico</h3>

                <div class="lp-amount">
                    <strong>$25 + IVA</strong>
                    <span>/ mes por sucursal</span>
                </div>

                <p>
                    Ideal para pequeños negocios con un volumen moderado de facturación.
                </p>

                <ul>
                    <li>Hasta <strong>200 DTEs</strong> al mes</li>
                    <li>Facturación electrónica</li>
                    <li>Ventas</li>
                    <li>Control de productos</li>
                    <li>Clientes</li>
                    <li>Usuarios</li>
                    <li>Plataforma en la nube</li>
                    <li>Soporte Limitado (Solo para consultas)</li>
                </ul>

                <button type="button"
                    class="lp-btn lp-btn--primary"
                    style="width:100%;"
                    @click="contact = true">
                    Contratar Gestock
                </button>
            </article>


            <!-- Plan Recomendado -->
            <article class="lp-card lp-price lp-reveal">

                <span class="lp-kicker">Más elegido</span>

                <h3 style="margin-top:1rem;">Gestock Profesional</h3>

                <div class="lp-amount">
                    <strong>$35 + IVA</strong>
                    <span>/ mes por sucursal</span>
                </div>

                <p>
                    La opción ideal para negocios que necesitan mayor capacidad de facturación.
                </p>

                <ul>
                    <li>Hasta <strong>1,000 DTEs</strong> al mes</li>
                    <li>Facturación electrónica</li>
                    <li>Ventas</li>
                    <li>Control de productos</li>
                    <li>Clientes</li>
                    <li>Usuarios</li>
                    <li>Reportes</li>
                    <li>Plataforma en la nube</li>
                    <li>Soporte 24/7</li>
                </ul>

                <button type="button"
                    class="lp-btn lp-btn--primary"
                    style="width:100%;"
                    @click="contact = true">
                    Contratar Gestock
                </button>
            </article>


            <!-- Plan Empresarial -->
            <article class="lp-card lp-price lp-reveal">

                <span class="lp-kicker">Para alto volumen</span>

                <h3 style="margin-top:1rem;">Gestock Empresarial</h3>

                <div class="lp-amount">
                    <strong>$60 + IVA</strong>
                    <span>/ mes por sucursal</span>
                </div>

                <p>
                    Infraestructura dedicada para negocios que requieren facturación sin límites.
                </p>

                <ul>
                    <li><strong>DTEs ilimitados</strong></li>
                    <li>Hosting propio</li>
                    <li>Dominio propio</li>
                    <li>Infraestructura dedicada</li>
                    <li>Facturación electrónica</li>
                    <li>Ventas</li>
                    <li>Control de productos</li>
                    <li>Clientes</li>
                    <li>Usuarios</li>
                    <li>Reportes</li>
                    <li>Soporte 24/7</li>
                </ul>

                <div style="margin-bottom:1rem;">
                    <strong>Instalación: $300 + IVA</strong>
                </div>

                <button type="button"
                    class="lp-btn lp-btn--primary"
                    style="width:100%;"
                    @click="contact = true">
                    Contratar Gestock
                </button>
            </article>
            </div>
        </div>
    </section>

    <section id="api" class="lp-api">
        <div class="lp-wrap lp-api__grid">
            <div class="lp-reveal">
                <span class="lp-api__badge">
                    <i></i>
                    En desarrollo · Muy pronto
                </span>
                <p class="lp-api__brand">Gestock API</p>
                <h2>La puerta para que tu negocio facture desde cualquier sistema.</h2>
                <p class="lp-lead">Estamos construyendo una API para conectar tu POS, e-commerce o ERP con Gestock: ventas, clientes y DTE sin salir de tu flujo. El futuro de la facturación electrónica en El Salvador, abierto a integraciones.</p>
                <ul class="lp-api__chips" aria-label="Capacidades en desarrollo">
                    <li>REST</li>
                    <li>DTE</li>
                    <li>Webhooks</li>
                    <li>Claves de acceso</li>
                    <li>Sandbox</li>
                </ul>
                <div class="lp-cta-row">
                    <button type="button" class="lp-btn lp-btn--primary" @click="contact = true">Quiero acceso anticipado</button>
                    <a class="lp-btn lp-btn--light" href="#contacto">Avísame cuando esté lista</a>
                </div>
            </div>
            <div class="lp-api__terminal lp-reveal" aria-hidden="true">
                <div class="lp-api__terminal-bar">
                    <span></span><span></span><span></span>
                    <code>gestock-api · coming soon</code>
                </div>
                <pre><span class="lp-api__cmt"># Integración en construcción</span>
<span class="lp-api__cmd">POST</span> /v1/dte
{
  <span class="lp-api__key">"tipoDte"</span>: <span class="lp-api__str">"01"</span>,
  <span class="lp-api__key">"ambiente"</span>: <span class="lp-api__str">"00"</span>,
  <span class="lp-api__key">"receptor"</span>: { <span class="lp-api__key">"nombre"</span>: <span class="lp-api__str">"…"</span> },
  <span class="lp-api__key">"cuerpoDocumento"</span>: [ … ]
}

<span class="lp-api__cmt"># Status</span>
<span class="lp-api__ok">200</span>  almost there<span class="lp-api__cursor">█</span></pre>
            </div>
        </div>
    </section>

    <section id="para-quien" class="lp-section lp-audience">
        <div class="lp-wrap">
            <div class="lp-section__head lp-reveal">
                <h2>Diseñado para negocios que quieren trabajar mejor</h2>
            </div>
            <div class="lp-grid-3">
                @foreach ([
                ['Tiendas', 'Ideal para puntos de venta que necesitan registrar operaciones y emitir documentos con orden.'],
                ['Comercios', 'Una forma clara de centralizar ventas, clientes y productos en un solo lugar.'],
                ['Pequeñas y medianas empresas', 'Pensado para equipos que buscan simplicidad, sin procesos innecesarios.'],
                ['Negocios con varias sucursales', 'El plan se maneja por sucursal, para crecer de forma ordenada.'],
                ['Profesionales y empresas', 'Para quienes necesitan facturación electrónica y control de su operación de ventas.'],
                ] as $item)
                <article class="lp-card lp-reveal">
                    <h3>{{ $item[0] }}</h3>
                    <p>{{ $item[1] }}</p>
                </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="confianza" class="lp-section lp-section--alt">
        <div class="lp-wrap">
            <div class="lp-section__head lp-reveal">
                <h2>La información de tu negocio, centralizada y disponible</h2>
            </div>
            <div class="lp-grid-3">
                @foreach ([
                ['Plataforma en la nube', 'Trabaja desde el navegador, con la información de tu negocio en un solo lugar.'],
                ['Acceso mediante usuarios', 'Cada persona ingresa con sus credenciales y el rol que le corresponda.'],
                ['Gestión de permisos', 'Administra quién puede utilizar el sistema según las necesidades de tu equipo.'],
                ['Información centralizada', 'Ventas, documentos, productos y clientes quedan organizados en la plataforma.'],
                ['Respaldo y mantenimiento', 'El mantenimiento de la plataforma se gestiona de forma centralizada, sin instalaciones locales.'],
                ] as $item)
                <article class="lp-card lp-reveal">
                    <h3>{{ $item[0] }}</h3>
                    <p>{{ $item[1] }}</p>
                </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="testimonios" class="lp-section">
        <div class="lp-wrap">
            <div class="lp-section__head lp-reveal">
                <h2>Negocios que ya trabajan con Gestock</h2>
            </div>
            <div class="lp-card lp-empty lp-reveal">
                <p>Esta sección está lista para testimonios reales. Cuando estén disponibles, aparecerán aquí. No publicamos comentarios inventados.</p>
            </div>
        </div>
    </section>

    <section id="faq" class="lp-section lp-section--alt lp-faq">
        <div class="lp-wrap" style="max-width:760px;">
            <div class="lp-section__head lp-reveal">
                <h2>Preguntas frecuentes</h2>
            </div>
            @foreach ([
            ['¿Qué es Gestock?', 'Gestock es una plataforma SaaS para facturación electrónica, ventas, clientes, productos y reportes.'],
            ['¿Necesito instalar Gestock?', 'No. Gestock funciona en la nube y se accede mediante navegador.'],
            ['¿Puedo utilizar Gestock desde diferentes computadoras?', 'Sí, siempre que tengas acceso a Internet y las credenciales correspondientes.'],
            ['¿Gestock es un sistema de inventario?', 'No. Gestock ofrece control y organización de productos para apoyar la operación de ventas, pero no pretende ser un sistema especializado de inventario.'],
            ['¿Gestock funciona con facturación electrónica?', 'Sí. La plataforma está orientada a la gestión de documentos tributarios electrónicos y la operación de ventas.'],
            ['¿El precio es por sucursal?', 'Sí. El plan se maneja por sucursal.'],
            ['¿Gestock tendrá API?', 'Sí. Gestock API está en desarrollo. Muy pronto podrás integrar facturación electrónica y ventas con tus propios sistemas. Si quieres acceso anticipado, escríbenos.'],
            ] as $faq)
            <details class="lp-reveal">
                <summary>{{ $faq[0] }}</summary>
                <p>{{ $faq[1] }}</p>
            </details>
            @endforeach
        </div>
    </section>

    <section class="lp-final">
        <div class="lp-wrap lp-reveal">
            <h2>Empieza a trabajar de una forma más sencilla.</h2>
            <p class="lp-lead">Centraliza tus ventas, facturación electrónica, productos y reportes en una sola plataforma.</p>
            <div class="lp-cta-row">
                <button type="button" class="lp-btn lp-btn--primary" @click="contact = true">Solicitar información</button>
                <a class="lp-btn lp-btn--light" href="#producto">Conocer Gestock</a>
            </div>
        </div>
    </section>

    <section id="contacto" class="lp-section">
        <div class="lp-wrap lp-split">
            <div class="lp-reveal">
                <h2>Conversemos sobre tu negocio</h2>
                <p class="lp-lead">Cuéntanos quién eres y te explicamos cómo Gestock puede ayudarte. Sin compromiso.</p>
            </div>
            <div class="lp-card lp-reveal">
                @if (session('contact_sent'))
                <div class="lp-alert lp-alert--ok">Recibimos tu solicitud. Te contactaremos pronto.</div>
                @endif
                @include('landing.partials.contact-form')
            </div>
        </div>
    </section>
</main>

<footer class="lp-footer">
    <div class="lp-wrap lp-footer__grid">
        <div>
            <a href="{{ route('landing') }}" class="lp-logo" style="margin-bottom:0.8rem;">
                <img src="{{ $logo }}" alt="Gestock">
            </a>
            <p>Plataforma en la nube para facturación electrónica y gestión de ventas.</p>
        </div>
        <div>
            <p style="font-weight:700;color:#fff;margin:0 0 .7rem;">Navegación</p>
            <p><a href="#inicio">Inicio</a></p>
            <p><a href="#caracteristicas">Características</a></p>
            <p><a href="#reportes">Reportes</a></p>
            <p><a href="#planes">Planes</a></p>
            <p><a href="#api">Gestock API</a></p>
            <p><a href="#faq">Preguntas frecuentes</a></p>
            <p><a href="#contacto">Contacto</a></p>
        </div>
        <div>
            <p style="font-weight:700;color:#fff;margin:0 0 .7rem;">Legal</p>
            <p><a href="{{ route('landing.terms') }}">Términos y condiciones</a></p>
            <p><a href="{{ route('landing.privacy') }}">Política de privacidad</a></p>
            @auth
            <p><a href="{{ route('home') }}">Ir al sistema</a></p>
            @else
            <p><a href="{{ route('login') }}">Ingresar</a></p>
            @endauth
        </div>
    </div>
    <div class="lp-wrap">
        <small>© 2026 Gestock. Todos los derechos reservados.</small>
    </div>
</footer>

<div class="lp-modal" x-show="contact" x-cloak @keydown.escape.window="contact = false">
    <div class="lp-modal__backdrop" @click="contact = false"></div>
    <div class="lp-modal__panel" @click.stop>
        <div class="lp-modal__head">
            <h3>Solicitar información</h3>
            <button type="button" class="lp-menu-btn" @click="contact = false" aria-label="Cerrar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>
        <p class="lp-lead">Completa el formulario y te contactamos.</p>
        @include('landing.partials.contact-form')
    </div>
</div>
@endsection