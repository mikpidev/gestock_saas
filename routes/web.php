<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\MHAccessController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreTaxInfoController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\SaleController;
use App\Http\Middleware\PreventBackHistory4;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\ActividadEconomicaController;
use App\Http\Controllers\CashClosureController;
use App\Http\Controllers\ContingenciaController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\DTEController;
use App\Models\Sale;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\OCIController;
use App\Http\Controllers\ReporteVentas;

//Cache prevent back history
Route::middleware([PreventBackHistory4::class])->group(function () {

    //Breeze rutas
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/home', [HomeController::class, 'index'])
        ->middleware(['auth'])
        ->name('home');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__ . '/auth.php';

    // CRUDS

    //rutas para compañias
    Route::resource('companies', CompanyController::class);

    //rutas para tax info
    //Route::resource('stores', StoreController::class);

    //rutas para tiendas

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('stores/{store}', [StoreController::class, 'show'])->name('stores.show');
    Route::delete('stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
    Route::get('stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');

    Route::get('companies/{company}/stores/create', [StoreController::class, 'create'])->name('stores.create');
    Route::post('companies/{company}/stores', [StoreController::class, 'store'])->name('store.store');
    //Route::resource('stores', StoreController::class);








    //rutas para tax info
    Route::get('/stores/{store}/tax-info/create', [StoreTaxInfoController::class, 'create'])
        ->name('store_tax_info.create');
    // EDIT primero
    Route::get('/stores/{store}/tax-info/{storeTaxInfo}/edit', [StoreTaxInfoController::class, 'edit'])
        ->name('store_tax_info.edit');

    // SHOW después
    Route::get('/stores/{store}/tax-info', [StoreTaxInfoController::class, 'show'])
        ->name('store_tax_info.show');


    Route::post('stores_tax_info/{store}', [StoreTaxInfoController::class, 'store'])
        ->name('stores_tax_info.store');

    Route::put('stores_tax_info/{storeTaxInfo}', [StoreTaxInfoController::class, 'update'])
        ->name('stores_tax_info.update');


    //Rutas Hacienda Access
    Route::get('/stores/{store}/mh_access/create', [MHAccessController::class, 'create'])
        ->name('mh_access.create');
    Route::post('/stores/{store}/mh_access', [MHAccessController::class, 'store'])
        ->name('mh_access.store');
    Route::get('/stores/{store}/mh_access/{mhAccess}/edit', [MHAccessController::class, 'edit'])
        ->name('mh_access.edit');
    Route::put('/stores/{store}/mh_access', [MHAccessController::class, 'update'])
        ->name('mh_access.update');
    Route::delete('/stores/{store}/mh-access/{mhAccess}', [MHAccessController::class, 'destroy'])
        ->name('mh_access.destroy');

    //rutas para usuarios
    Route::resource('stores.users', \App\Http\Controllers\UserController::class);

    // Listar productos de una tienda
    Route::get('stores/{store}/product_types', [ProductTypeController::class, 'index'])
        ->name('stores.product_types.index');

    // Formulario de creación de producto en una tienda
    Route::get('stores/{store}/product_types/create', [ProductTypeController::class, 'create'])
        ->name('stores.product_types.create');

    // Guardar producto nuevo
    Route::post('stores/{store}/product_types', [ProductTypeController::class, 'store'])
        ->name('stores.product_types.store');

    // Formulario de edición de producto en una tienda
    Route::get('stores/{store}/product_types/{productType}/edit', [ProductTypeController::class, 'edit'])
        ->name('stores.product_types.edit');

    // Actualizar producto
    Route::put('stores/{store}/product_types/{productType}', [ProductTypeController::class, 'update'])
        ->name('stores.product_types.update');

    // Mostrar detalles de un producto
    Route::get('stores/{store}/product_types/{productType}', [ProductTypeController::class, 'show'])
        ->name('stores.product_types.show');

    //eliminar producto
    Route::delete('stores/{store}/product_types/{productType}', [ProductTypeController::class, 'destroy'])
        ->name('stores.product_types.destroy');

    // Listar customers de una tienda
    Route::get('stores/{store}/customers', [CustomersController::class, 'index'])
        ->name('stores.customers.index');

    // Formulario de creación de customer
    Route::get('stores/{store}/customers/create', [CustomersController::class, 'create'])
        ->name('stores.customers.create');

    // Guardar customer nuevo
    Route::post('stores/{store}/customers', [CustomersController::class, 'store'])
        ->name('stores.customers.store');

    // Formulario de edición de customer
    Route::get('stores/{store}/customers/{customer}/edit', [CustomersController::class, 'edit'])
        ->name('stores.customers.edit');

    // Actualizar customer
    Route::put('stores/{store}/customers/{customer}', [CustomersController::class, 'update'])
        ->name('stores.customers.update');

    // Mostrar detalles de un customer
    Route::get('stores/{store}/customers/{customer}', [CustomersController::class, 'show'])
        ->name('stores.customers.show');

    // Eliminar customer
    Route::delete('stores/{store}/customers/{customer}', [CustomersController::class, 'destroy'])
        ->name('stores.customers.destroy');


    // Listar ventas de una tienda
    Route::get('stores/{store}/sales', [SaleController::class, 'index'])
        ->name('stores.sales.index');

    // Formulario de creación de venta
    Route::get('stores/{store}/sales/create', [SaleController::class, 'create'])
        ->name('stores.sales.create');

    // Guardar venta nueva
    Route::post('stores/{store}/sales', [SaleController::class, 'store'])
        ->name('stores.sales.store');

    // Formulario de edición de venta
    Route::get('stores/{store}/sales/{sale}/edit', [SaleController::class, 'edit'])
        ->name('stores.sales.edit');

    // Actualizar venta
    Route::put('stores/{store}/sales/{sale}', [SaleController::class, 'update'])
        ->name('stores.sales.update');

    Route::get('/dte/{codigo}', [SaleController::class, 'show'])
        ->name('dte.public');


    // Eliminar venta
    Route::delete('stores/{store}/sales/{sale}', [SaleController::class, 'destroy'])
        ->name('stores.sales.destroy');

    Route::post('stores/{store}/sales/{sale}/refresh-dte', [SaleController::class, 'refreshDTE'])
        ->name('stores.sales.refreshDTE');



    Route::get('/stores/{store}/sales/{sale}/print', [ReciboController::class, 'print'])
        ->name('ticket.print');
    Route::get('/stores/{store}/sales/{sale}/preorder', [ReciboController::class, 'preorder'])
        ->name('ticket.preorder');

    Route::get('/stores/{store}/sales/{sale}/reprint', [ReciboController::class, 'reprint'])
        ->name('ticket.reprint');



    Route::get('/catalogos/tipo-documento', [TipoDocumentoController::class, 'index'])->name('tipo_documento.index');
    Route::get('/catalogos/actividades', [ActividadEconomicaController::class, 'index'])->name('actividades.index');
    Route::get('/catalogos/departamentos', [DepartamentoController::class, 'index'])->name('departamentos.index');
    Route::get('/catalogos/municipios', [MunicipioController::class, 'index'])->name('municipios.index');
    Route::get('/catalogos/municipios/{codigo}', [MunicipioController::class, 'byDepartamento'])->name('municipios.byDepartamento');

    //ruta notas de credito
    Route::get('stores/{store}/creditnotes', [\App\Http\Controllers\CreditNoteController::class, 'index'])
        ->name('stores.creditnotes.index');
    Route::get('stores/{store}/creditnotes/create', [\App\Http\Controllers\CreditNoteController::class, 'create'])
        ->name('stores.creditnotes.create');
    Route::post('stores/{store}/creditnotes', [\App\Http\Controllers\CreditNoteController::class, 'store'])
        ->name('stores.creditnotes.store');
    Route::get('stores/{store}/creditnotes/{creditNote}', [\App\Http\Controllers\CreditNoteController::class, 'show'])
        ->name('stores.creditnotes.show');
    Route::delete('stores/{store}/creditnotes/{creditNote}', [\App\Http\Controllers\CreditNoteController::class, 'destroy'])
        ->name('stores.creditnotes.destroy');


    Route::post('stores/{store}/creditnotes/{creditNote}/refresh-dte', [CreditNoteController::class, 'refreshDTE'])
        ->name('stores.creditnotes.refreshDTE');



    Route::get('/sales/{sale}/details', function (Sale $sale) {
        $details = $sale->details()->with('productType')->get();

        return response()->json([
            'details' => $details
        ]);
    });

    Route::get('/stores/{store}/sales/{sale}/details', [CreditNoteController::class, 'getSaleDetails'])
        ->name('stores.sales.details');

    //ruta notas de debito
    Route::get('stores/{store}/debitnotes', [\App\Http\Controllers\DebitNoteController::class, 'index'])
        ->name('stores.debitnotes.index');
    Route::get('stores/{store}/debitnotes/create', [\App\Http\Controllers\DebitNoteController::class, 'create'])
        ->name('stores.debitnotes.create');
    Route::post('stores/{store}/debitnotes', [\App\Http\Controllers\DebitNoteController::class, 'store'])
        ->name('stores.debitnotes.store');
    Route::get('stores/{store}/debitnotes/{debitNote}', [\App\Http\Controllers\DebitNoteController::class, 'show'])
        ->name('stores.debitnotes.show');

    Route::get('/stores/{store}/sales/{sale}/details', [DebitNoteController::class, 'getSaleDetails'])
        ->name('stores.sales.details');
    Route::delete('stores/{store}/debitnotes/{debitNote}', [\App\Http\Controllers\DebitNoteController::class, 'destroy'])
        ->name('stores.debitnotes.destroy');

    Route::post('stores/{store}/debitnotes/{debitNote}/refresh-dte', [DebitNoteController::class, 'refreshDTE'])
        ->name('stores.debitnotes.refreshDTE');


    //Rutas reporte de ventas
    Route::get('/reportes/ventas/pdf', [ReporteVentas::class, 'index'])
        ->name('reportes.ventas.pdf');
    Route::get('/reportes/ventas', [ReporteVentas::class, 'dteReporte'])
        ->name('reportes.ventas');
    Route::get('/reportes/notascredito', [ReporteVentas::class, 'dteReporteNC'])
        ->name('reportes.notascredito');
    Route::get('/reportes/notasdebito', [ReporteVentas::class, 'dteReporteND'])
        ->name('reportes.notasdebito');

    Route::prefix('stores/{store}')->group(function () {
        Route::get('/cash-closures',  [CashClosureController::class, 'index'])->name('stores.cash.closures.index');
        Route::post('/cash-closures/close', [CashClosureController::class, 'closeCash'])->name('cash.closures.close');
        Route::get('/cash-closures/{id}/print', [CashClosureController::class, 'print'])->name('cash.closures.print');
    });
    Route::prefix('stores/{store}/contingencias')->group(function () {

        Route::get('/', [ContingenciaController::class, 'index'])
            ->name('contingencias.index');

        Route::get('/create', [ContingenciaController::class, 'create'])
            ->name('contingencias.create');

        Route::post('/', [ContingenciaController::class, 'store'])
            ->name('contingencias.store');

        Route::post('/{contingencia}/cerrar', [ContingenciaController::class, 'close'])
            ->name('contingencias.cerrar');
    });



    //Bucket Test OCI
    Route::get('/oci-test', [\App\Http\Controllers\OCIController::class, 'uploadTest']);

    //Email Test OCI
    Route::post('/stores/{store}/sales/{sale}/send-email', [\App\Http\Controllers\OCIController::class, 'emailSend'])
        ->name('stores.email.send');
});
