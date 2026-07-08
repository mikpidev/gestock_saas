<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Validation\Rule;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;
use Carbon\Carbon;



class StoreController extends Controller
{



    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor, inicia sesión.');
        }
        if ($user->hasRole('superadmin')) {
            $companyId = session('selected_company_id');

            if (!$companyId) {
                return redirect()->route('companies.select'); // o donde seleccione empresa
            }

            $stores = Store::where('company_id', $companyId)->get();
        } elseif ($user->hasRole('admin')) {

            $companyId = session('selected_company_id') ?? $user->company_id;

            $stores = Store::where('company_id', $companyId)->get();
        } else {
            abort(403, 'Acceso no autorizado.');
        }

        return view('store.index', compact('stores'));
    }


    public function create(Company $company)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Lógica para mostrar el formulario de creación de tienda ya incluye la compañia previamente creada
        return view('store.create', compact('company'));
    }

    public function store(Request $request, Company $company)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'store_name' => 'required|max:200',
            'address'    => 'required',
            'phone'      => 'required|size:8',
            'manager'    => 'required|max:100',
            'email'      => 'required|email|unique:stores,email',
            'status'     => 'required|in:activa,suspendida,inactiva',
            'environment' => 'required|in:Production,Development',
            'comments'   => 'nullable',
        ]);

        $store = $company->stores()->create($validated);

        return redirect()->route('store_tax_info.create', ['store' => $store->id])
            ->with('success', 'Tienda creada, ahora crea la información fiscal de la tienda.');
    }

    public function getChartData(Request $request, Store $store)
    {

        //filtros
        $dateFrom = $request->from
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::today()->subDays(6)->startOfDay();

        $dateTo = $request->to
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::today()->endOfDay();
        //Filtrar por tipo de documento (Factura, CF, etc)
        $documentType = $request->tipo_documento_id;

        //Filtrar por Status (por defecto solo ventas aceptadas por Hacienda)
        $dte_status = 'PROCESADO' ??  $request->dte_status;

        // Base query con filtros aplicados

        $baseQuery = Sale::query()
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($documentType, function ($query) use ($documentType) {
                $query->where('tipo_documento_id', $documentType);

            });

        \Log::info("Base Query: " . $baseQuery->toSql(), [
            'bindings' => $baseQuery->getBindings(),
        ]);

        // Chart Data

        $chartData = (clone $baseQuery)
            ->selectRaw("DATE(created_at) as date, SUM(total_amount) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalSales = (clone $baseQuery)->sum('total_amount');
        $totalCount = (clone $baseQuery)->count();
        $salesTodayTotal = (clone $baseQuery)->whereDate('created_at', Carbon::today())->sum('total_amount');
        $salesTodayCount = (clone $baseQuery)->whereDate('created_at', Carbon::today())->count();
        $salesWeekTotal = (clone $baseQuery)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('total_amount');
        $salesWeekCount = (clone $baseQuery)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $salesMonthTotal = (clone $baseQuery)->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('total_amount');
        $salesMonthCount = (clone $baseQuery)->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count();

        $methodPaymentData = (clone $baseQuery)
            ->selectRaw("
                COUNT(CASE WHEN payment_method = 'Efectivo' THEN 1 END ) as efectivo,
                COUNT(CASE WHEN payment_method = 'Tarjeta' THEN 1 END ) as tarjeta,
                COUNT(CASE WHEN payment_method = 'Transferencia' THEN 1 END ) as transferencia,
                SUM(total_amount) as total,
                SUM(CASE WHEN payment_method = 'Efectivo' THEN total_amount ELSE 0 END) as monto_efectivo,
                SUM(CASE WHEN payment_method = 'Tarjeta' THEN total_amount ELSE 0 END) as monto_tarjeta,
                SUM(CASE WHEN payment_method = 'Transferencia' THEN total_amount ELSE 0 END) as monto_transferencia               
            ")->first();

        $dteSummary = (clone $baseQuery) 
            ->selectRaw("
                COUNT(CASE WHEN tipo_documento_id = 1 THEN 1 END) as factura,
                COUNT(CASE WHEN tipo_documento_id = 2 THEN 1 END) as CCF,
                COUNT(CASE WHEN tipo_documento_id = 10 THEN 1 END) as SE,
                SUM(total_amount) as total,
                SUM(CASE WHEN tipo_documento_id = 1 THEN total_amount ELSE 0 END) as monto_factura,
                SUM(CASE WHEN tipo_documento_id = 2 THEN total_amount ELSE 0 END) as monto_CCF,
                SUM(CASE WHEN tipo_documento_id = 10 THEN total_amount ELSE 0 END) as monto_SE 
            ")->first();

        
        $dteAproved = (clone $baseQuery)->where('dte_status', 'PROCESADO')->count();
        $dteDeny = (clone $baseQuery)->where('dte_status', 'RECHAZADO')->count();
        $dtePending = (clone $baseQuery)->where('dte_status', 'PENDIENTE')->count();
        $dteFactura = (clone $baseQuery)->where('tipo_documento_id', '1')->count();
        $dteCF = (clone $baseQuery)->where('tipo_documento_id', '2')->count();
        $dteSE = (clone $baseQuery)->where('tipo_documento_id', '10')->count();

        return response()->json([
            'chartData' => $chartData,
            'totalSales' => $totalSales,
            'totalCount' => $totalCount,
            'salesTodayTotal' => $salesTodayTotal,
            'salesTodayCount' => $salesTodayCount,
            'salesWeekTotal' => $salesWeekTotal,
            'salesWeekCount' => $salesWeekCount,
            'salesMonthTotal' => $salesMonthTotal,
            'salesMonthCount' => $salesMonthCount,
            'methodPaymentData' => $methodPaymentData,
            'dteSummary' => $dteSummary,
            'dteAproved' => $dteAproved,
            'dteDeny' => $dteDeny,
            'dtePending' => $dtePending,
            'dteFactura' => $dteFactura,
            'dteCF' => $dteCF,
            'dteSE' => $dteSE,
        ]);
    }


    public function dashboard(Store $store)
    {
        return view('store.dashboard', compact('store'));
    }

    public function show(Store $store)
    {

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Validar acceso según rol
        if ($user->hasRole('superadmin')) {
            $companyId = session('selected_company_id');
            if (!$companyId) abort(403, 'Se requiere compañía.');
            if ($store->company_id != $companyId) abort(403, 'Acceso no autorizado.');
        } elseif ($user->hasRole('admin') || $user->hasRole('user')) {
            if ($store->company_id != $user->company_id) abort(403, 'Acceso no autorizado.');
        } else {
            abort(403, 'Acceso no autorizado.');
        }

        //llamar Catalogos

        $actividades = \App\Models\CodActividad::all();
        $departamentos = \App\Models\Departamento::all();
        $municipios = \App\Models\Municipio::all();


        // Cargar relaciones necesarias
        $store->load(['taxInfo', 'company']);

        // Base query para esta tienda
        $baseQuery = Sale::where('store_id', $store->id);

        // Últimas 5 ventas
        $sales = $baseQuery->latest('created_at')->take(5)->get();

        // Totales y conteos
        $salesTodayTotal = (clone $baseQuery)->whereDate('created_at', Carbon::today())->sum('total_amount');
        $salesTodayCount = (clone $baseQuery)->whereDate('created_at', Carbon::today())->count();

        $salesWeekTotal = (clone $baseQuery)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('total_amount');
        $salesWeekCount = (clone $baseQuery)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();

        // Ventas por día de la última semana para gráfico
        $weeklySalesLabels = [];
        $weeklySalesData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $weeklySalesLabels[] = $date->locale('es')->isoFormat('ddd'); // Lunes, Mar, etc.
            $weeklySalesData[] = (clone $baseQuery)->whereDate('created_at', $date)->sum('total_amount');
        }


        return view('store.show', compact(
            'store',
            'actividades',
            'departamentos',
            'municipios',
            'sales',
            'salesTodayTotal',
            'salesTodayCount',
            'salesWeekTotal',
            'salesWeekCount',
            'weeklySalesLabels',
            'weeklySalesData'
        ));
    }



    public function edit(Request $request, Store $store)
    {

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        //$store = Store::findOrFail($id);
        return view('store.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Validación de los datos del formulario
        $validated = $request->validate([
            'store_name' => 'required|max:200',
            'address' => 'required',
            'phone' => 'required|size:8',
            'manager' => 'required|max:100',
            'email' => ['required', 'email', 'max:100', Rule::unique('stores')->ignore($store->id)->whereNull('deleted_at'),],  // Solo verifica registros activos
            'status' => 'required|in:activa,suspendida,inactiva',
            'environment' => 'required|in:Production,Development',
            'comments' => 'nullable',
        ]);

        // Actualización de la tienda
        $store->update($validated);

        return redirect()->route('stores.index')->with('success', 'Tienda actualizada exitosamente.');
    }

    public function destroy(Request $request, Store $store)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Lógica para eliminar una tienda (soft delete)
        $store->delete();
        return redirect()->route('store.show')->with('success', 'Tienda eliminada exitosamente.');
    }
}
