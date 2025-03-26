<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\TransactionsController;
use Google\Service\CloudSearch\TransactionContext;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Middleware\AdminMiddleWare;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WalletTransactionsController;
use App\Http\Controllers\DataSettingsController;
use App\Livewire\SliderControllers;
use App\Http\Controllers\ReportedController;
use App\Http\Controllers\UserSearchController;







Route::get('/', function (Request $request) {
    $ref = $request->reference;
    Log::info('get trans reference here: '. $ref);
    return view('welcome');
});


//Dashboard
Route::get('dashboard', [AdminDashboardController::class, 'myAdmin'])->name('dashboard');
Route::get('/filter-data', [AdminDashboardController::class, 'filterData']);

//Users
Route::match(['get', 'post'], 'users', [UsersController::class, 'index'])->name('users.index');
Route::get('edit-user/{id}', [UsersController::class, 'edit'])->name('edit-user');
Route::put('update-user/{id}', [UsersController::class, 'update'])->name('update-user');
Route::get('suspend-user/{id}', [UsersController::class, 'suspend'])->name('suspend-user');
Route::get('block-user/{id}', [UsersController::class, 'block'])->name('block-user');
Route::get('delete-user/{id}', [UsersController::class, 'destroy'])->name('delete-user');


//Data Settings
Route::get('data_settings', [DataSettingsController::class, 'index']);

//Wallet Transaction
Route::get('wallet_transac', [WalletTransactionsController::class, 'index'])->name('wallet_transac.index');
Route::post('wallet_transac/{id}/walletrefund', [WalletTransactionsController::class, 'walletrefund'])->name('wallet_transac.refund');


//TRANSACTIONS

Route::match(['get', 'post'], 'transaction', [TransactionsController::class, 'index'])->name('transaction.index');
Route::post('transaction/{id}/refund', [TransactionsController::class, 'refund'])->name('transaction.refund');


//Reported Transaction
Route::get('reported', [ReportedController::class, 'index'])->name('reported.index');
Route::post('reported/{id}/reportrefund', [ReportedController::class, 'reportedrefund'])->name('report.refund');
Route::get('reported/{transactionId}', [ReportedController::class, 'show'])->name('reported.reports');
//Route::get('reported/api/{requestId}', [ReportedController::class, 'queryApiStatus'])->name('reported.api.query');


  // Notification
Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('notification-form', [NotificationController::class, 'create']);
Route::post('add-notification', [NotificationController::class, 'store'])->name('add-notification');
Route::get('edit-notification/{id}', [NotificationController::class, 'edit']);
Route::post('update-notification/{id}', [NotificationController::class, 'update']);
Route::get('delete-notification/{id}', [NotificationController::class, 'destroy']);


// search user
Route::get('/search-users', [UserSearchController::class, 'search'])->name('search.users');


  // Slider Routes
Route::get('sliders', [SliderController::class, 'index'])->name('sliders');
Route::get('slider-form', [SliderController::class, 'create'])->name('slider-form');
Route::post('add-slider', [SliderController::class, 'store'])->name('add-slider'); 
Route::get('edit-slider/{id}', [SliderController::class, 'edit'])->name('edit-slider');
Route::post('update-slider/{id}', [SliderController::class, 'update'])->name('update-slider');
Route::delete('delete-slider/{id}', [SliderController::class, 'destroy'])->name('delete-slider');








Route::get('/email', function (Request $request) {

    return view('emails.verification_code');
});



Route::get('/db-info', function () {
    $connection = Config::get('database.default');
    $config = Config::get("database.connections.$connection");

    return [
        'Database Type' => $config['driver'],
        'Database Name' => $config['database'],
        'Database Password' => $config['password'],
    ];
});

Route::get('/login', [DashboardController::class, 'showLoginForm'])->name('admin.login');


Route::prefix('admin')->group(function () {
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    // Route::get('/transactions', 'Admin\TransactionController@index')->name('admin.transactions');
    // Route::get('/users', 'Admin\UserController@index')->name('admin.users');
    // Route::get('/reports', 'Admin\ReportController@index')->name('admin.reports');
    // Route::get('/settings', 'Admin\SettingsController@index')->name('admin.settings');
    Route::get('/logout', [DashboardController::class, 'logout'])->name('admin.logout');
    // Route::get('/login', [DashboardController::class, 'showLoginForm'])->name('admin.login');
    // Route::post('/login', [DashboardController::class, 'login'])->name('admin.login');
    // Route::post('/logout', [DashboardController::class, 'logout'])->name('admin.logout');

    Route::middleware(['auth', AdminMiddleWare::class, ])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    });
});


