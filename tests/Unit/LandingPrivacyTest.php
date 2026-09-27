<?php

use Tests\TestCase;

uses(TestCase::class);

it('publishes the privacy policy v1 with sections 1 through 13', function () {
    $this->withoutVite();

    $response = $this->get(route('landing.privacy'));

    $response->assertOk();
    $response->assertSee('Política de Privacidad — Gestock', false);
    $response->assertSee('gestock.site', false);
    $response->assertSee('Versión', false);
    $response->assertSee('v1', false);
    $response->assertSee('27 de septiembre de 2026', false);
    $response->assertDontSee('FINAL DRAFT', false);
    $response->assertDontSee('DRAFT (not live)', false);
    $response->assertDontSee('[FECHA DE PUBLICACIÓN]', false);

    $sections = [
        '1. Responsable del tratamiento',
        '2. Alcance',
        '3. Datos que se tratan',
        '4. Finalidades',
        '5. Con quién se comparten los datos',
        '6. Transferencias fuera de El Salvador',
        '7. Retención',
        '8. Derechos y contacto',
        '9. Seguridad',
        '10. Cookies',
        '11. Responsabilidad del comercio',
        '12. Cambios',
        '13. Contacto',
    ];

    foreach ($sections as $section) {
        $response->assertSee($section, false);
    }

    $response->assertSee('Miguel Armando Navarro Pineda (persona natural)', false);
    $response->assertSee('058641004', false);
    $response->assertSee('gestock.sts@outlook.com', false);
    $response->assertSee('Aplica a Clientes (comercios con suscripción), Usuarios autorizados por el Cliente y al uso de la plataforma Gestock (POS + facturación electrónica DTE).', false);
    $response->assertSee('No se tratan aún:', false);
    $response->assertSee('datos de pasarela de pago (el cobro online de la suscripción no está implementado).', false);
    $response->assertSee('<table>', false);
    $response->assertSee('Oracle', false);
    $response->assertSee('región Phoenix, EE.UU.', false);
    $response->assertSee('Brevo', false);
    $response->assertSee('Ministerio de Hacienda de El Salvador', false);
    $response->assertSee('No hay pasarela de pago activa. El dashboard no envía métricas a analytics externos: se procesan en Gestock (sobre Oracle).', false);
    $response->assertSee('treinta (30) días calendario', false);
    $response->assertSee('Ningún sistema garantiza seguridad absoluta.', false);
    $response->assertDontSee('Cuando solicitas información, podemos recibir tu nombre', false);
});

it('keeps the terms page copy unchanged', function () {
    $this->withoutVite();

    $response = $this->get(route('landing.terms'));

    $response->assertOk();
    $response->assertSee('Términos y condiciones', false);
    $response->assertSee('Última actualización: Septiembre 2026', false);
    $response->assertSee('Uso de Gestock', false);
    $response->assertSee('Gestock es una plataforma en la nube para facturación electrónica, ventas, clientes, productos y reportes. Al solicitar información o utilizar el servicio, te comprometes a proporcionar datos veraces y a utilizar la plataforma de acuerdo con las condiciones que se definan al momento de contratar.', false);
    $response->assertSee('Cuentas y acceso', false);
    $response->assertSee('El acceso a Gestock se realiza mediante usuario y contraseña, desde un navegador con conexión a Internet. Cada negocio es responsable de administrar sus usuarios y de proteger sus credenciales.', false);
    $response->assertSee('Planes y facturación', false);
    $response->assertSee('El plan de Gestock se ofrece por sucursal. Las condiciones comerciales específicas, incluyendo vigencia, forma de pago y alcance del servicio, se confirman al momento de la contratación.', false);
    $response->assertSee('Si tienes dudas sobre estos términos, puedes escribirnos desde la sección de contacto de esta página.', false);
    $response->assertDontSee('Miguel Armando Navarro Pineda', false);
    $response->assertDontSee('27 de septiembre de 2026', false);
    $response->assertDontSee('058641004', false);
});

it('links politicas de privacidad from the landing footer', function () {
    $this->withoutVite();

    $response = $this->get(route('landing'));

    $response->assertOk();
    $response->assertSee('href="'.route('landing.privacy').'"', false);
    $response->assertSee('>Políticas de privacidad</a>', false);
    $response->assertSee('href="'.route('landing.terms').'"', false);
    $response->assertSee('>Términos y condiciones</a>', false);
});
