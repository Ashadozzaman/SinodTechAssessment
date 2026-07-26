<?php

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LostCustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    $totalUser = User::get()->count();
    return Inertia::render('Dashboard', [
        'totalUser' => $totalUser
    ]);
})->middleware(['auth', 'verified', 'prevent_back'])->name('dashboard');

Route::middleware('auth', 'prevent_back')->group(function () {
    //** User Route Start */
    Route::resource('users', UserController::class)
        ->only(['create', 'store'])
        ->middleware("permission:users.create");
    Route::resource('users', UserController::class)
        ->only(['edit', 'update'])
        ->middleware("permission:users.update");
    Route::resource('users', UserController::class)
        ->only(['destroy'])
        ->middleware("permission:users.delete");
    Route::resource('users', UserController::class)
        ->only(['index', 'show'])
        ->middleware("permission:users.view|users.create|users.update|users.delete");
    //** User Route End */

    //** Roles Route start */
    Route::resource('roles', RoleController::class)
        ->only(['create', 'store'])
        ->middleware("permission:roles.create");
    Route::resource('roles', RoleController::class)
        ->only(['edit', 'update'])
        ->middleware("permission:roles.update");
    Route::resource('roles', RoleController::class)
        ->only(['destroy'])
        ->middleware("permission:roles.delete");
    Route::resource('roles', RoleController::class)
        ->only(['index', 'show'])
        ->middleware("permission:roles.view|roles.create|roles.update|roles.delete");
    //** Roles Route End */

    //** Branches Route Start */
    Route::resource('branches', BranchController::class)
        ->only(['create', 'store'])
        ->middleware("permission:branches.create");
    Route::resource('branches', BranchController::class)
        ->only(['edit', 'update'])
        ->middleware("permission:branches.update");
    Route::resource('branches', BranchController::class)
        ->only(['destroy'])
        ->middleware("permission:branches.delete");
    Route::resource('branches', BranchController::class)
        ->only(['index'])
        ->middleware("permission:branches.view|branches.create|branches.update|branches.delete");
    //** Branches Route End */

    //** Products Route Start */
    Route::resource('products', ProductController::class)
        ->only(['create', 'store'])
        ->middleware("permission:products.create");
    Route::resource('products', ProductController::class)
        ->only(['edit', 'update'])
        ->middleware("permission:products.update");
    Route::resource('products', ProductController::class)
        ->only(['destroy'])
        ->middleware("permission:products.delete");
    Route::resource('products', ProductController::class)
        ->only(['index'])
        ->middleware("permission:products.view|products.create|products.update|products.delete");
    //** Products Route End */

    //** Customers Route Start */
    Route::resource('customers', CustomerController::class)
        ->only(['create', 'store'])
        ->middleware("permission:customers.create");
    Route::resource('customers', CustomerController::class)
        ->only(['edit', 'update'])
        ->middleware("permission:customers.update");
    Route::resource('customers', CustomerController::class)
        ->only(['destroy'])
        ->middleware("permission:customers.delete");
    Route::resource('customers', CustomerController::class)
        ->only(['index', 'show'])
        ->middleware("permission:customers.view|customers.create|customers.update|customers.delete");
    //** Customers Route End */

    //** Lost Customers Route Start */
    Route::get('lost-customers', [LostCustomerController::class, 'index'])
        ->name('lost-customers.index')
        ->middleware("permission:customers.lost");
    //** Lost Customers Route End */

    //** Inventory Route Start */
    Route::put('products/{product}/stock', [InventoryController::class, 'update'])
        ->name('inventory.adjust')
        ->middleware("permission:inventory.adjust");
    //** Inventory Route End */

    //** Sales Route Start */
    Route::get('sales/search-customers', [SaleController::class, 'searchCustomers'])
        ->name('sales.search-customers')
        ->middleware("permission:sales.create");
    Route::get('sales/search-products', [SaleController::class, 'searchProducts'])
        ->name('sales.search-products')
        ->middleware("permission:sales.create");
    Route::resource('sales', SaleController::class)
        ->only(['create', 'store'])
        ->middleware("permission:sales.create");
    Route::resource('sales', SaleController::class)
        ->only(['index', 'show'])
        ->middleware("permission:sales.view|sales.create|sales.update|sales.delete");
    //** Sales Route End */
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
