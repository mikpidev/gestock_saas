<?php

use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('landing links documentacion to the public knowledge base', function () {
    $response = $this->get('/');

    $response->assertOk();
    $html = $response->getContent();

    expect(substr_count($html, 'href="https://documentation.gestock.site"'))->toBe(3)
        ->and(substr_count($html, '>Documentación<'))->toBe(3);

    $response->assertSee('Términos y condiciones', false)
        ->assertSee('Política de privacidad', false)
        ->assertDontSee('En desarrollo / Under construction', false);
});

test('this app does not serve a knowledge base shell', function () {
    $this->get('/documentacion')->assertNotFound();

    $this->get('http://documentation.gestock.site/')
        ->assertOk()
        ->assertSee('Facturación electrónica y control de ventas, todo en un solo lugar.', false)
        ->assertDontSee('<title>Gestock Knowledge Base</title>', false);
});

test('terms page stays unchanged', function () {
    $this->get('/terminos')
        ->assertOk()
        ->assertSee('Términos y condiciones', false)
        ->assertSee('Uso de Gestock', false)
        ->assertDontSee('https://documentation.gestock.site', false);
});
