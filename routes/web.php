<?php

use Illuminate\Support\Facades\Route;
use App\Models\SupplierPurchase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

// Public storefront
Route::livewire('/', 'pages::storefront.home')->name('home');
Route::livewire('/cart', 'pages::storefront.cart')->name('storefront.cart');
Route::livewire('/checkout', 'pages::storefront.checkout')->name('storefront.checkout');
Route::livewire('/orders/{order}/confirmation', 'pages::storefront.order-success')->name('storefront.order.success');

// Bulk export inquiry
Route::livewire('/bulk-orders', 'pages::bulk-inquiry')->name('bulk.inquiry');

// Authenticated dashboard (Fortify-protected) — redirects based on role
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('home');
    })->name('dashboard');
});

// Admin panel
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('products', 'pages::admin.products')->name('products');
    Route::livewire('suppliers', 'pages::admin.suppliers')->name('suppliers');
    Route::livewire('supplier-purchases', 'pages::admin.supplier-purchases')->name('supplier-purchases');
    Route::livewire('employees', 'pages::admin.employees')->name('employees');
    Route::livewire('employee-payments', 'pages::admin.employee-payments')->name('employee-payments');
    Route::get('supplier-purchases/{supplierPurchase}/receipt', function (SupplierPurchase $supplierPurchase) {
        $supplierPurchase->load('supplier');

        $filename = Str::slug($supplierPurchase->supplier->name.'-receipt-'.$supplierPurchase->id).'.pdf';

        return Pdf::loadView('pdf.supplier-purchase-receipt', [
            'purchase' => $supplierPurchase,
        ])->setPaper('a4')->download($filename);
    })->name('supplier-purchases.receipt');
    Route::livewire('orders', 'pages::admin.orders')->name('orders');
    Route::livewire('inquiries', 'pages::admin.inquiries')->name('inquiries');
});

require __DIR__.'/settings.php';
