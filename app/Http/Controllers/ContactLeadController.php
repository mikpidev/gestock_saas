<?php

namespace App\Http\Controllers;

use App\Models\ContactLead;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContactLeadController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if (!$user || !$user->canViewLandingLeads()) {
            abort(403, 'Acceso no autorizado.');
        }

        $leads = ContactLead::query()
            ->latest()
            ->paginate(20);

        return view('leads.index', compact('leads'));
    }
}
