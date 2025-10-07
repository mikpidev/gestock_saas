<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\HaciendaAuthService;


class DTEController extends Controller
{
    //mandar a llamar token

    protected $authService;

    public function __construct(HaciendaAuthService $authService)
    {
        $this->authService = $authService;
    }

    //


}
