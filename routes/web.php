<?php

use App\Http\Controllers\Admin\ShowCustomerDocument;
use App\Http\Controllers\ShowDeliveryReport;
use App\Http\Controllers\ShowInspectionReport;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware(SetLocale::class)->group(function () {
    Route::view('/', 'portal.home')->name('home');
});

// Acta de recepción para el cliente: enlace firmado con vencimiento (Ajustes > Check-in).
Route::get('actas/{inspection}', ShowInspectionReport::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('inspections.report');

Route::get('entregas/{delivery}', ShowDeliveryReport::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('deliveries.report');

// Archivos privados del panel: URL firmada + sesión + Policy.
Route::middleware(['auth', 'signed'])->prefix('admin/private')->group(function () {
    Route::get('customer-documents/{document}', ShowCustomerDocument::class)->name('admin.customer-documents.show');
    Route::get('inspections/{inspection}/report', ShowInspectionReport::class)->name('admin.inspections.report');
    Route::get('deliveries/{delivery}/report', ShowDeliveryReport::class)->name('admin.deliveries.report');
});
