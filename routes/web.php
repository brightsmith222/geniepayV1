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
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WalletTransactionsController;
use App\Http\Controllers\DataSettingsController;
use App\Livewire\SliderControllers;
use App\Http\Controllers\ReportedController;
use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProductLookupController;
use App\Http\Controllers\TicketController;







Route::get('/', function (Request $request) {
    $ref = $request->reference;
    Log::info('get trans reference here: '. $ref);
    return view('welcome');
});



// Admin Authentication routes
Route::middleware('web')->group(function () {
Route::get('login', [DashboardController::class, 'showLoginForm'])->name('login');
Route::get('admin/login', [DashboardController::class, 'showLoginForm'])->name('admin.login');
Route::post('admin/login', [DashboardController::class, 'login'])->name('admin.login.submit');
Route::match(['get', 'post'], 'logout', [DashboardController::class, 'logout'])->name('admin.logout');
Route::get('/admin/verify-otp', [DashboardController::class, 'showOtpForm'])->name('admin.otp.verify.page');
Route::post('/admin/verify-otp', [DashboardController::class, 'verifyOtp'])->name('admin.otp.verify.submit');

Route::post('/admin/resend-otp', [DashboardController::class, 'resendOtp'])->name('admin.otp.resend');


});


// Protected admin routes
Route::middleware(['auth', AdminMiddleware::class])->group(function () {

//Dashboard
Route::get('admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
Route::get('dashboard', [AdminDashboardController::class, 'myAdmin'])->name('dashboard');
Route::get('/filter-data', [AdminDashboardController::class, 'filterData']);
Route::get('/get-wallet-balance', [AdminDashboardController::class, 'getWalletBalance'])->name('get.wallet.balance');
Route::get('/dash-reported', [AdminDashboardController::class, 'getReportedTransactions']);
Route::get('/get-glad-wallet-balance', [AdminDashboardController::class, 'getGladWalletBalance']);
Route::get('/get-artx-wallet-balance', [AdminDashboardController::class, 'getArtxWalletBalance']);

// Users Routes
Route::get('users', [UsersController::class, 'index'])->name('users.index');
Route::get('edit-user/{id}', [UsersController::class, 'edit'])->name('edit-user');
Route::put('update-user/{id}', [UsersController::class, 'update'])->name('update-user');
Route::post('suspend-user/{id}', [UsersController::class, 'suspend'])->name('suspend-user');
Route::post('block-user/{id}', [UsersController::class, 'block'])->name('block-user');
Route::post('unsuspend-user/{id}', [UsersController::class, 'unsuspend'])->name('unsuspend-user');
Route::post('unblock-user/{id}', [UsersController::class, 'unblock'])->name('unblock-user');
Route::delete('delete-user/{id}', [UsersController::class, 'destroy'])->name('delete-user');
Route::get('users/{id}/transactions', [UsersController::class, 'fetchTransactions'])->name('users.transactions');

//Data Settings
Route::get('data_settings', [DataSettingsController::class, 'index']);


Route::get('/data-settings', function () { return view('service.data');})->name('data.settings');
Route::get('/airtime-settings', function () { return view('service.airtime');})->name('airtime.settings');
Route::get('/voucher-settings', function () { return view('service.voucher');})->name('voucher.settings');

//Wallet Transaction
Route::get('wallet_transac', [WalletTransactionsController::class, 'index'])->name('wallet_transac.index');
Route::post('wallet_transac/{id}/walletrefund', [WalletTransactionsController::class, 'walletrefund'])->name('wallet_transac.refund');


//TRANSACTIONS

Route::match(['get', 'post'], 'transaction', [TransactionsController::class, 'index'])->name('transaction.index');
Route::post('transaction/{id}/refund', [TransactionsController::class, 'refund'])->name('transaction.refund');
Route::get('transaction/{transactionId}', [TransactionsController::class, 'show'])->name('transaction.reports');
Route::post('transaction/{requestId}/resolve', [TransactionsController::class, 'resolve'])->name('transaction.resolve');
Route::post('/transaction/refresh-status', [TransactionsController::class, 'refreshStatus'])->name('transaction.refresh');



//Reported Transaction
Route::get('reported', [ReportedController::class, 'index'])->name('reported.index');

// Support Tickets Management
Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
Route::get('tickets/statistics', [TicketController::class, 'statistics'])->name('tickets.statistics');
Route::post('tickets/{ticket}/reply', [TicketController::class, 'addReply'])->name('tickets.reply');
Route::patch('tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.updateStatus');
Route::post('tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
Route::post('reported/{id}/reportrefund', [ReportedController::class, 'reportedrefund'])->name('report.refund');
Route::get('reported/{transactionId}', [ReportedController::class, 'show'])->name('reported.reports');
Route::get('reported/resolved', [ReportedController::class, 'resolved'])->name('reported.resolved');
Route::get('attachments/{attachment}/download', [TicketController::class, 'downloadAttachment'])->name('attachments.download');
Route::delete('attachments/{attachment}', [TicketController::class, 'deleteAttachment'])->name('attachments.delete');


  // Notification
Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('notification-form', [NotificationController::class, 'create']);
Route::post('add-notification', [NotificationController::class, 'store'])->name('add-notification');
Route::get('edit-notification/{id}', [NotificationController::class, 'edit']);
Route::post('update-notification/{id}', [NotificationController::class, 'update']);
Route::get('delete-notification/{id}', [NotificationController::class, 'destroy']);
Route::delete('/notifications/delete', [NotificationController::class, 'delete'])->name('notifications.delete');


// search user
Route::get('/search-users', [UserSearchController::class, 'search'])->name('search.users');


  // Slider Routes
Route::get('sliders', [SliderController::class, 'index'])->name('sliders');
Route::get('slider-form', [SliderController::class, 'create'])->name('slider-form');
Route::post('add-slider', [SliderController::class, 'store'])->name('add-slider'); 
Route::get('edit-slider/{id}', [SliderController::class, 'edit'])->name('edit-slider');
Route::post('update-slider/{id}', [SliderController::class, 'update'])->name('update-slider');
Route::delete('delete-slider/{id}', [SliderController::class, 'destroy'])->name('delete-slider');

// Settings Routes
Route::get('settings', [SettingsController::class, 'index'])->name('settings');
Route::post('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
Route::post('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
Route::post('settings/api', [SettingsController::class, 'updateApiSettings'])->name('settings.api.update');
Route::post('settings/database', [SettingsController::class, 'updateDatabaseSettings'])->name('settings.database.update');


//Product lookup
Route::get('/product-explorer', [ProductLookupController::class, 'showForm'])
     ->name('product.explorer');
     
Route::post('/product-explorer/operator-products', [ProductLookupController::class, 'getOperatorProducts'])
     ->name('operator.products');
     
Route::get('/product-explorer/{operator_id}/{product_id}', [ProductLookupController::class, 'getProductDetails'])
     ->name('product.details');
     

  

});



Route::post('/vtpasswebhook', [TransactionsController::class, 'vtpassRequeryWebhook']);

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



Route::prefix('admin')->group(function () {
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    // Route::get('/transactions', 'Admin\TransactionController@index')->name('admin.transactions');
    // Route::get('/users', 'Admin\UserController@index')->name('admin.users');
    // Route::get('/reports', 'Admin\ReportController@index')->name('admin.reports');
    // Route::get('/settings', 'Admin\SettingsController@index')->name('admin.settings');
   // Route::get('/logout', [DashboardController::class, 'logout'])->name('admin.logout');
    // Route::get('/login', [DashboardController::class, 'showLoginForm'])->name('admin.login');
    // Route::post('/login', [DashboardController::class, 'login'])->name('admin.login');
    // Route::post('/logout', [DashboardController::class, 'logout'])->name('admin.logout');

    //Route::middleware(['auth', AdminMiddleWare::class, ])->group(function () {
   //     Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
   // });
   
});


