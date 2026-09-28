<?php

use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('landing links documentacion to the knowledge base url', function () {
    $response = $this->get('/');

    $response->assertOk();
    $html = $response->getContent();
    $documentationUrl = config('services.documentation.url');

    expect($documentationUrl)->toBe('https://documentation.gestock.site')
        ->and(substr_count($html, 'href="'.$documentationUrl.'"'))->toBe(3)
        ->and(substr_count($html, '>Documentación<'))->toBe(3);

    $response->assertSee('Términos y condiciones', false)
        ->assertSee('Política de privacidad', false);
});

test('landing documentation link follows DOCUMENTATION_URL', function () {
    config(['services.documentation.url' => 'https://docs.example.test']);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="https://docs.example.test"', false)
        ->assertDontSee('href="https://documentation.gestock.site"', false);
});

test('knowledge base preview is a gestock shell under construction', function () {
    $response = $this->get('/documentacion');

    $response->assertOk()
        ->assertSee('<title>Gestock Knowledge Base</title>', false)
        ->assertSeeInOrder([
            'En desarrollo / Under construction',
            'Gestock Knowledge Base',
            'Buscar en la base de conocimiento',
            'Primeros pasos',
            'Próximamente',
            'Preguntas frecuentes',
            'Los artículos se publicarán aquí cuando la base de conocimiento esté lista.',
        ], false)
        ->assertDontSee('Facturación electrónica y control de ventas, todo en un solo lugar.', false);
});

test('knowledge base search is a placeholder and escapes the query', function () {
    $this->get('/documentacion?q='.urlencode('<script>alert(1)</script>'))
        ->assertOk()
        ->assertSee('Sin resultados para', false)
        ->assertSee('La búsqueda todavía no está disponible.', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('documentation host serves the knowledge base at the root', function () {
    $this->get('http://documentation.gestock.site/')
        ->assertOk()
        ->assertSee('<title>Gestock Knowledge Base</title>', false)
        ->assertSee('En desarrollo / Under construction', false)
        ->assertDontSee('Facturación electrónica y control de ventas, todo en un solo lugar.', false);
});

test('main host still serves the landing page', function () {
    $this->get('http://gestock.site/')
        ->assertOk()
        ->assertSee('Facturación electrónica y control de ventas, todo en un solo lugar.', false)
        ->assertDontSee('<title>Gestock Knowledge Base</title>', false);
});

test('terms page stays unchanged', function () {
    $this->get('/terminos')
        ->assertOk()
        ->assertSee('Términos y condiciones', false)
        ->assertSee('Uso de Gestock', false)
        ->assertDontSee('https://documentation.gestock.site', false)
        ->assertDontSee('En desarrollo / Under construction', false);
});
