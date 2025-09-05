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
use App\Http\Controllers\Api\SmileController;
use App\Http\Controllers\Api\SpectranetController;
use App\Http\Controllers\Api\BeneficiaryController;
use App\Http\Controllers\Api\MobilePinController;
use App\Http\Controllers\Api\NinePsbWebhookController;
use App\Http\Controllers\Api\HashTestController;
use App\Http\Controllers\Api\InternationalAirtimeController;
use App\Http\Controllers\Api\TicketController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route::get('/get_data_plan', [BuyDataController::class, 'getDataPlan']);
Route::middleware(['auth:sanctum'])->group(function () {

Route::get('/user', [UserController::class, 'user']);
Route::post('/change-password', [UserController::class, 'changePassword']);
Route::post('/set-transaction-pin', [UserController::class, 'setTransactionPin']);
Route::post('/update-user', [UserController::class, 'updateUser']);
Route::get('/user-referred', [UserController::class, 'getReferredUsers']);
Route::get('/referral-bonus', [UserController::class, 'getReferralBonus']);
Route::get('/virtual-charge', [UserController::class, 'getVirtualCharge']);
Route::get('/get-virtual', [UserController::class, 'getVirtualAccount']);
Route::post('/upload-image', [UserController::class, 'uploadProfileImage']);
Route::post('/send-change-pin-verification-code', [UserController::class, 'sendChangePinVerificationCode']);
Route::post('/login-with-pin', [UserController::class, 'loginWithPin']);
Route::get('/card-payment', [UserController::class, 'isCardPaymentActivated']);
Route::get('/active-service', [UserController::class, 'getServices']);

// Support Tickets
Route::get('/tickets', [TicketController::class, 'index']);
Route::post('/tickets', [TicketController::class, 'store']);
Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply']);
Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
Route::get('/attachments/{attachment}/view', [TicketController::class, 'viewAttachment']);
Route::get('/attachments/{attachment}/download', [TicketController::class, 'downloadAttachment']);


    
Route::get('/get_data_plan', [BuyDataController::class, 'getDataPlan']);
Route::post('/buy_data', [BuyDataController::class, 'buyData']);
Route::post('/detect_international_number', [BuyDataController::class, 'detectInternationalNumber']);
Route::get('/get_international_plans/{operatorId}', [BuyDataController::class, 'getInternationalPlans']);
Route::post('/buy_international_data', [BuyDataController::class, 'buyInternationalData']);

// Mobile Pins
Route::post('/parse-msisdn', [MobilePinController::class, 'parseMsisdn']);
Route::get('/operators/{country}', [MobilePinController::class, 'getOperators']);
Route::get('/products/{operator}', [MobilePinController::class, 'getOperatorProducts']);
Route::post('/mobile-pin/execute', [MobilePinController::class, 'execTransaction']);

Route::post('/topup', [AirtimeController::class, 'buyAirtime']);

Route::post('/result_checker', [ExamsController::class,'buyResultChecker']);
Route::post('/jamb/verify', [ExamsController::class, 'verifyJambProfile']);
Route::post('/jamb/purchase', [ExamsController::class, 'purchaseJambPin']);
Route::get('/jamb/variations', [ExamsController::class, 'fetchJambVariations']);
Route::get('/waec/variations', [ExamsController::class, 'getAllWaecVariations']);
Route::post('/waec/purchase', [ExamsController::class, 'purchaseWaecPin']);



Route::post('/verify-smart-card', [CableTvController::class, 'verifySmartCard']);
Route::post('/cable-subscription', [CableTvController::class, 'cableSubscription']);
Route::get('/cable-providers', [CableTvController::class, 'getCableProviders']);
Route::get('/cable-plans/{serviceID}', [CableTvController::class, 'getCablePlan']);


Route::get('/electricity-providers', [ElectricityController::class, 'getElectricityProviders']);


Route::post('/send-pushNotification', [NotificationController::class, 'sendPushNotif']);
Route::get('/get-notifications', [NotificationController::class, 'index']);
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

Route::post('/monnify-pay-with-card', [PaymentController::class, 'monnifyPayWithCard']);
Route::post('/paystack-pay-with-card', [PaymentController::class, 'paystackPayWithCard']);
Route::get('/transactions', [PaymentController::class, 'transactions']);
Route::get('/wallet_transactions', [PaymentController::class, 'walletTransactions']);
Route::post('/verify-receiver', [PaymentController::class, 'verifyReceiver']);
Route::post('/transfer', [PaymentController::class, 'transfer']);
Route::post('/paystack-callback', [PaymentController::class, 'paystackCallback']);

// Sliders
Route::get('/sliders', [SliderController::class, 'index']);
Route::get('/slider/{id}', [SliderController::class, 'show']);


Route::post('/transaction-report', [TransactionReportController::class, 'transactionReport']);

// Gift Cards

Route::get('/giftcard/countries', [GiftCardController::class, 'getCountries']);
Route::get('/giftcard/operators', [GiftCardController::class, 'getGiftCards']);
Route::get('/giftcard/denominations', [GiftCardController::class, 'getDenominations']);
Route::post('/purchase/giftcard', [GiftCardController::class, 'purchase']);
Route::get('/giftcard-country', [GiftCardController::class, 'getCountry']);
Route::get('/giftcard/products/{countryIso}', [GiftCardController::class, 'getProductsByCountry']);
Route::get('/giftcard-product', [GiftCardController::class, 'getProductDetails']);
Route::post('/giftcard-purchase', [GiftCardController::class, 'purchasecard']);
Route::post('/giftcard-redeem/{orderId}', [GiftCardController::class, 'getGiftCardRedeemCode']);


// eSIM
Route::get('/esim/operators', [EsimController::class, 'getEsims']);
Route::get('/esim/denominations', [EsimController::class, 'getDenominations']);
Route::post('/esim/purchase', [EsimController::class, 'purchase']);

//Smile Data
Route::post('/smile-verify', [SmileController::class, 'verifySmileAccount']);
Route::get('/smile-plans', [SmileController::class, 'getSmilePlans']);
Route::post('/internet-purchase', [SmileController::class, 'purchaseInternetData']);

//Spectranet Data
Route::get('/spectranet-plans', [SpectranetController::class, 'getSpectranetPlans']);
Route::post('/spectranet-purchase', [SpectranetController::class, 'purchaseSpectranetData']);

// Beneficiaries
Route::post('/beneficiaries', [BeneficiaryController::class, 'index']);
Route::get('/all-beneficiaries', [BeneficiaryController::class, 'all']);
Route::delete('/beneficiary/{id}', [BeneficiaryController::class, 'delete']);

// International Airtime
//Route::post('/detect-country', [InternationalAirtimeController::class, 'detectCountry']);
Route::get('/airtime-operators', [InternationalAirtimeController::class, 'getAirtimeOperators']);
Route::get('/data-operators', [InternationalAirtimeController::class, 'getDataOperators']);
Route::get('/get-operator/{id}', [InternationalAirtimeController::class, 'getOperatorById']);
Route::post('/purchase', [InternationalAirtimeController::class, 'purchase']);
Route::post('/all-operator', [InternationalAirtimeController::class, 'getAllOperators']);





});

Route::post('/login', [UserController::class, 'userLogin']);
Route::post('/register', [UserController::class, 'registerUser']);
Route::post('/verify-code', [UserController::class, 'verifyCode']);
Route::post('/send-verification-code', [UserController::class, 'sendVerificationCode']);
Route::post('/recover-password', [UserController::class, 'recoverPassword']);
Route::post('/send-forgot-password-code', [UserController::class, 'sendForgotPasswordCode']);

Route::post('/v1/monnify-webhook', [PaymentController::class, 'monnifyWebhook']);
Route::post('/v1/paystack-webhook', [PaymentController::class, 'paystackWebhook']);

// webhook for 9PSB
Route::post('/webhook/9psb-notify', [NinePsbWebhookController::class, 'handle']);

Route::post('/generate-hash', [HashTestController::class, 'generate']);
Route::get('/maintenance', [UserController::class, 'maintenance']);

Route::post('/test-mail', [UserController::class, 'testMail']);




 
  
