<?php

namespace App\Http\Controllers;

use App\Models\ContactLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        return view('landing.index', [
            'screenshots' => $this->screenshots(),
        ]);
    }

    public function terms(): View
    {
        return view('landing.legal', [
            'title' => 'Términos y condiciones',
            'heading' => 'Términos y condiciones',
            'updated' => 'Septiembre 2026',
            'sections' => [
                [
                    'title' => 'Uso de Gestock',
                    'body' => 'Gestock es una plataforma en la nube para facturación electrónica, ventas, clientes, productos y reportes. Al solicitar información o utilizar el servicio, te comprometes a proporcionar datos veraces y a utilizar la plataforma de acuerdo con las condiciones que se definan al momento de contratar.',
                ],
                [
                    'title' => 'Cuentas y acceso',
                    'body' => 'El acceso a Gestock se realiza mediante usuario y contraseña, desde un navegador con conexión a Internet. Cada negocio es responsable de administrar sus usuarios y de proteger sus credenciales.',
                ],
                [
                    'title' => 'Planes y facturación',
                    'body' => 'El plan de Gestock se ofrece por sucursal. Las condiciones comerciales específicas, incluyendo vigencia, forma de pago y alcance del servicio, se confirman al momento de la contratación.',
                ],
                [
                    'title' => 'Contacto',
                    'body' => 'Si tienes dudas sobre estos términos, puedes escribirnos desde la sección de contacto de esta página.',
                ],
            ],
        ]);
    }

    public function privacy(): View
    {
        return view('landing.legal', [
            'title' => 'Política de privacidad',
            'heading' => 'Política de privacidad',
            'updated' => 'Septiembre 2026',
            'sections' => [
                [
                    'title' => 'Datos que recopilamos',
                    'body' => 'Cuando solicitas información, podemos recibir tu nombre, negocio, correo electrónico, teléfono y el mensaje que nos envíes, con el fin de responder tu consulta y dar seguimiento comercial.',
                ],
                [
                    'title' => 'Uso de la información',
                    'body' => 'Utilizamos estos datos para atender solicitudes, explicar el funcionamiento de Gestock y coordinar la contratación del servicio. No vendemos esta información a terceros.',
                ],
                [
                    'title' => 'Información del negocio en la plataforma',
                    'body' => 'Los datos operativos que un cliente carga en Gestock (ventas, productos, clientes y documentos) se gestionan dentro de la plataforma para prestar el servicio contratado.',
                ],
                [
                    'title' => 'Contacto',
                    'body' => 'Para consultas relacionadas con privacidad, puedes utilizar el formulario de contacto de esta página.',
                ],
            ],
        ]);
    }

    public function contact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'business' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['prohibited'],
        ]);

        $lead = ContactLead::create([
            'name' => $validated['name'],
            'business_name' => $validated['business'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        $this->notifyLead($lead);

        return redirect()
            ->route('landing')
            ->with('contact_sent', true)
            ->withFragment('contacto');
    }

    /**
     * @return array<string, array{key: string, title: string, description: string, src: string|null}>
     */
    private function screenshots(): array
    {
        $items = [
            'dashboard' => [
                'title' => 'Dashboard',
                'description' => 'Visualiza rápidamente el comportamiento de tu negocio y las métricas más importantes.',
            ],
            'ventas' => [
                'title' => 'Ventas',
                'description' => 'Registra y consulta tus ventas en un listado claro, listo para el día a día.',
            ],
            'dte' => [
                'title' => 'Facturación electrónica',
                'description' => 'Genera y gestiona tus documentos tributarios electrónicos desde la misma plataforma.',
            ],
            'productos' => [
                'title' => 'Productos',
                'description' => 'Organiza tus productos, precios y la información necesaria para realizar tus ventas.',
            ],
            'clientes' => [
                'title' => 'Clientes',
                'description' => 'Mantén la información de tus clientes ordenada para agilizar cada venta.',
            ],
            'reportes' => [
                'title' => 'Reportes',
                'description' => 'Analiza tus ventas y obtén información útil para tomar decisiones.',
            ],
            'usuarios' => [
                'title' => 'Usuarios',
                'description' => 'Trabaja con distintos usuarios y administra el acceso al sistema.',
            ],
        ];

        foreach ($items as $key => &$item) {
            $item['key'] = $key;
            $item['src'] = $this->screenshotSrc($key);
        }

        return $items;
    }

    private function notifyLead(ContactLead $lead): void
    {
        $to = config('services.gestock_leads.notify_email');
        $from = config('services.gestock_leads.from_email');

        if (! is_string($to) || $to === '' || ! is_string($from) || $from === '') {
            return;
        }

        try {
            Mail::send('emails.contact-lead', ['lead' => $lead], function ($message) use ($lead, $to, $from) {
                $message
                    ->from($from, 'Gestock')
                    ->to($to)
                    ->subject('Nueva solicitud de información — ' . $lead->business_name)
                    ->replyTo($lead->email, $lead->name);
            });
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de lead de la landing', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function screenshotSrc(string $key): ?string
    {
        foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
            $relative = "images/landing/{$key}.{$ext}";
            if (file_exists(public_path($relative))) {
                return asset($relative);
            }
        }

        $remote = [
            'dashboard' => 'https://i.ibb.co/Vptrq9gL/image.png',
            'ventas' => 'https://i.ibb.co/s9sX3kQk/image.png',
            'productos' => 'https://i.ibb.co/RTZTwpGq/image.png',
            'clientes' => 'https://i.ibb.co/TDYht2qs/image.png',
            'usuarios' => 'https://i.ibb.co/jkvBBWmP/image.png',
            'reportes' => 'https://i.ibb.co/Vptrq9gL/image.png',
            'dte' => 'https://i.ibb.co/s9sX3kQk/image.png',
        ];

        return $remote[$key] ?? null;
    }
}
