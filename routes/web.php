<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\TransactionsController;
use Google\Service\CloudSearch\TransactionContext;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Middleware\AdminMiddleWare;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WalletTransactionsController;
use App\Http\Controllers\DataSettingsController;






Route::get('/', function (Request $request) {
    $ref = $request->reference;
    Log::info('get trans reference here: '. $ref);
    return view('welcome');
});


//Dashboard
Route::get('dashboard', [AdminDashboardController::class, 'myAdmin'] );

//Users
Route::get('users', [UsersController::class, 'index']);
Route::get('edit-user/{id}', [UsersController::class, 'edit']);


//Data Settings
Route::get('data_settings', [DataSettingsController::class, 'index']);

//Wallet Transaction
Route::get('wallet_transac', [WalletTransactionsController::class, 'index']);


//TRANSACTIONS

Route::get('transaction', [TransactionsController::class,'index'] );
//Route::post('/transactions/refund/{id}', 'TransactionController@refund')->name('transactions.refund');
Route::post('/transaction/refund/{id}', [TransactionsController::class,'refund'] );




  // Notification
  Route::get('notifications', [NotificationController::class, 'index']);
  Route::get('notification-form', [NotificationController::class, 'create']);
  Route::post('add-notification', [NotificationController::class, 'store']);
  Route::get('edit-notification/{id}', [NotificationController::class, 'edit']);
  Route::post('update-notification/{id}', [NotificationController::class, 'update']);
  Route::get('delete-notification/{id}', [NotificationController::class, 'destroy']);

  // Slider
  Route::get('sliders', [SliderController::class, 'index']);
  Route::get('slider-form', [SliderController::class, 'create']);
  Route::post('add-slider', [SliderController::class, 'store']);
  Route::get('edit-slider/{id}', [SliderController::class, 'edit']);
  Route::post('update-slider/{id}', [SliderController::class, 'update']);
  Route::get('delete-slider/{id}', [SliderController::class, 'destroy']);









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


