<?php

use App\Http\Controllers\Admin\ShowCustomerDocument;
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

// Archivos privados del panel: URL firmada + sesión + Policy.
Route::middleware(['auth', 'signed'])->prefix('admin/private')->group(function () {
    Route::get('customer-documents/{document}', ShowCustomerDocument::class)->name('admin.customer-documents.show');
    Route::get('inspections/{inspection}/report', ShowInspectionReport::class)->name('admin.inspections.report');
});
