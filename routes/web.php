<?php

use Illuminate\Support\Facades\Route;

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
    Route::livewire('orders', 'pages::admin.orders')->name('orders');
    Route::livewire('inquiries', 'pages::admin.inquiries')->name('inquiries');
});

require __DIR__.'/settings.php';
