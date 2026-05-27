<?php

use Illuminate\Support\Facades\Route;
use App\Models\SupplierPurchase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

// Public storefront
Route::livewire('/', 'pages::storefront.home')->name('home');
Route::livewire('/cart', 'pages::storefront.cart')->name('storefront.cart');
Route::livewire('/checkout', 'pages::storefront.checkout')->name('storefront.checkout');
Route::livewire('/orders/{order}/confirmation', 'pages::storefront.order-success')->name('storefront.order.success');

// Bulk export inquiry
Route::livewire('/bulk-orders', 'pages::bulk-inquiry')->name('bulk.inquiry');

// About page and contact form
Route::get('/about', function () {
    return view('about');
})->name('about');

Route::post('/about/send', function (Request $request) {
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'message' => 'required|string',
    ]);

    $body = "Name: {$data['name']}\nEmail: {$data['email']}\n\nMessage:\n{$data['message']}";

    Mail::raw($body, function ($message) use ($data) {
        $message->to('smartagro2025@gmail.com')
            ->subject('Website message from '.$data['name'])
            ->replyTo($data['email'], $data['name']);
    });

    return redirect()->route('about')->with('status', 'Your message has been sent. Thank you!');
})->name('about.send');

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
