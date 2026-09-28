<?php

use App\Models\ContactLead;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('contact_leads');
    Schema::create('contact_leads', function (Blueprint $table) {
        $table->id();
        $table->string('name', 120);
        $table->string('business_name', 160);
        $table->string('email', 160);
        $table->string('phone', 40)->nullable();
        $table->text('message')->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->timestamps();
    });

    config(['honeypot.enabled' => false]);
    RateLimiter::clear('contacto-ip:127.0.0.1');
    RateLimiter::clear('contacto-hour:127.0.0.1');
});

afterEach(function () {
    Schema::dropIfExists('contact_leads');
});

function contactPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ana Pérez',
        'business' => 'Tienda El Sol',
        'email' => 'ana@negocio.sv',
        'phone' => '7777-7777',
        'message' => 'Quiero información de Gestock.',
    ], $overrides);
}

test('el formulario de contacto guarda un lead válido', function () {
    $this->post(route('landing.contact'), contactPayload())
        ->assertRedirect(route('landing').'#contacto')
        ->assertSessionHas('contact_sent');

    expect(ContactLead::query()->count())->toBe(1);
});

test('la blacklist bloquea correos de dominios temporales', function () {
    $this->post(route('landing.contact'), contactPayload([
        'email' => 'bot@mailinator.com',
    ]))->assertRedirect(route('landing').'#contacto');

    expect(ContactLead::query()->count())->toBe(0);
});

test('el honeypot de Spatie descarta envíos de bots', function () {
    config([
        'honeypot.enabled' => true,
        'honeypot.randomize_name_field_name' => false,
        'honeypot.name_field_name' => 'my_name',
        'honeypot.valid_from_timestamp' => false,
    ]);

    $this->post(route('landing.contact'), contactPayload([
        'my_name' => 'filled-by-bot',
    ]))->assertRedirect(route('landing').'#contacto');

    expect(ContactLead::query()->count())->toBe(0);
});

test('el contacto tiene rate limit por IP', function () {
    foreach (range(1, 3) as $i) {
        $this->post(route('landing.contact'), contactPayload([
            'email' => "ana{$i}@negocio.sv",
        ]))->assertRedirect();
    }

    $this->post(route('landing.contact'), contactPayload([
        'email' => 'cuarta@negocio.sv',
    ]))->assertStatus(429);

    expect(ContactLead::query()->count())->toBe(3);
});
