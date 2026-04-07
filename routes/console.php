<?php

use App\Http\Controllers\ReporteVentas;
use App\Services\OCIService;
//use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cronjob to upload sales files to OCI Bucket
//Artisan::command('upload:sales', function () {
//    $this->comment('Uploading sales files to OCI Bucket...');
    // Logic to upload sales files to OCI Bucket
//    app(\App\Http\Controllers\ReporteVentas::class)->dteReporte(app(OCIService::class));10
//    $this->comment('Sales files uploaded successfully!');
//})->purpose('Upload sales files to OCI Bucket');

//Schedule::command('upload:sales')->everyMinute();
