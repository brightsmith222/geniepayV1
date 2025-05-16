<?php

use App\Http\Controllers\Api\AirtimeController;
use App\Http\Controllers\Api\BuyDataController;
use App\Http\Controllers\Api\CableTvController;
use App\Http\Controllers\Api\ElectricityController;
use App\Http\Controllers\Api\ExamsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SliderController;
use App\Http\Controllers\Api\TransactionReportController;
use App\Http\Controllers\Api\GiftCardController;
use App\Http\Controllers\Api\EsimController;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route::get('/get_data_plan', [BuyDataController::class, 'getDataPlan']);
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', [UserController::class, 'user']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::post('/set-transaction-pin', [UserController::class, 'setTransactionPin']);
    Route::post('/update-user', [UserController::class, 'updateUser']);


    
Route::get('/get_data_plan', [BuyDataController::class, 'getDataPlan']);
Route::post('/buy_data', [BuyDataController::class, 'buyData']);
Route::post('/topup', [AirtimeController::class, 'buyAirtime']);
Route::post('/result_checker', [ExamsController::class,'buyResultChecker']);
Route::post('/verify-smart-card', [CableTvController::class, 'verifySmartCard']);
Route::post('/cable-subscription', [CableTvController::class, 'cableSubscription']);
Route::get('/cable-providers', [CableTvController::class, 'getCableProviders']);
Route::get('/cable-plans/{serviceID}', [CableTvController::class, 'getCablePlan']);

Route::get('/electricity-providers', [ElectricityController::class, 'getElectricityProviders']);


Route::post('/send-pushNotification', [NotificationController::class, 'sendPushNotif']);
Route::get('/get-notifications', [NotificationController::class, 'index']);


Route::post('/monnify-pay-with-card', [PaymentController::class, 'monnifyPayWithCard']);
Route::post('/paystack-pay-with-card', [PaymentController::class, 'paystackPayWithCard']);
Route::get('/transactions', [PaymentController::class, 'transactions']);
Route::get('/wallet_transactions', [PaymentController::class, 'walletTransactions']);
Route::post('/verify-receiver', [PaymentController::class, 'verifyReceiver']);
Route::post('/transfer', [PaymentController::class, 'transfer']);

// Sliders
Route::get('/sliders', [SliderController::class, 'index']);
Route::get('/slider/{id}', [SliderController::class, 'show']);


Route::post('/transaction-report', [TransactionReportController::class, 'transactionReport']);

// Gift Cards
Route::get('/countries', [GiftCardController::class, 'getCountries']);
Route::get('/giftcards', [GiftCardController::class, 'getGiftCards']);
Route::get('/denominations', [GiftCardController::class, 'getDenominations']);
Route::post('/purchase-giftcard', [GiftCardController::class, 'purchase']);

// eSIM
Route::get('/esim-countries', [EsimController::class, 'getCountries']);
Route::get('/esim-plans', [EsimController::class, 'getPlans']);
Route::post('/esim-purchase', [EsimController::class, 'purchase']);


});

Route::post('/login', [UserController::class, 'userLogin']);
Route::post('/register', [UserController::class, 'registerUser']);
Route::post('/verify-code', [UserController::class, 'verifyCode']);
Route::post('/send-verification-code', [UserController::class, 'sendVerificationCode']);
Route::post('/recover-password', [UserController::class, 'recoverPassword']);

Route::post('/v1/monnify-webhook', [PaymentController::class, 'monnifyWebhook']);
Route::post('/v1/paystack-webhook', [PaymentController::class, 'paystackWebhook']);

Route::post('/test-mail', [UserController::class, 'testMail']);




 
  
