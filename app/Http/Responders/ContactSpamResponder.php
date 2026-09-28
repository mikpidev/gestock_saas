<?php

namespace App\Http\Responders;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Honeypot\SpamResponder\SpamResponder;

class ContactSpamResponder implements SpamResponder
{
    public function respond(Request $request, Closure $next)
    {
        Log::warning('Contacto bloqueado por honeypot', [
            'ip' => $request->ip(),
        ]);

        return redirect()
            ->route('landing')
            ->with('contact_sent', true)
            ->withFragment('contacto');
    }
}
