<?php

use App\Http\Controllers\Admin\ShowCustomerDocument;
use App\Http\Controllers\Admin\ShowQuoteReport;
use App\Http\Controllers\Portal\CatalogController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\ProjectSentController;
use App\Http\Controllers\Portal\ShowProgressPhoto;
use App\Http\Controllers\ShowDeliveryReport;
use App\Http\Controllers\ShowInspectionReport;
use App\Http\Middleware\SetLocale;
use App\Livewire\Portal\ProjectBuilder;
use App\Livewire\Portal\QuotePage;
use App\Livewire\Portal\TrackingPage;
use Illuminate\Support\Facades\Route;

// Portal público (PLAN.md §2.2): catálogo y constructor abiertos; registro solo al enviar.
Route::middleware(SetLocale::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('catalogo', CatalogController::class)->name('portal.catalog');
    Route::get('projeto', ProjectBuilder::class)->name('portal.builder');
    Route::get('projeto/enviado/{project}', ProjectSentController::class)->middleware('signed')->name('portal.project.sent');

    // Área del cliente: el enlace firmado abre su sesión (sin contraseña).
    Route::middleware('customer.link')->group(function () {
        Route::get('orcamento/{quote}', QuotePage::class)->name('portal.quote');
        Route::get('acompanhar/{workOrder}', TrackingPage::class)->name('portal.tracking');
    });
    Route::get('acompanhar/{workOrder}/fotos/{update}/{index}', ShowProgressPhoto::class)
        ->middleware('auth')->whereNumber('index')->name('portal.tracking.photo');
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
    Route::get('quotes/{quote}/report', ShowQuoteReport::class)->name('admin.quotes.report');
});
