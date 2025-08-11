<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Transactions;
use App\Helpers\ReloadlyHelper;
use App\Mail\TransactionSuccessMail;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FetchGiftCardPin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reloadlyTransactionId;
    protected $localTransactionId;

    public function __construct($reloadlyTransactionId, $localTransactionId)
    {
        $this->reloadlyTransactionId = $reloadlyTransactionId;
        $this->localTransactionId = $localTransactionId;
    }

    public function handle()
    {
        $details = ReloadlyHelper::getCardDetails($this->reloadlyTransactionId);

        Log::info('Reloadly gift card details', ['details' => $details]);

        if ($details['success']) {
            $transaction = Transactions::find($this->localTransactionId);
            if ($transaction && !$transaction->epin) {
                $transaction->epin = $details['data']['pinCode'];
                $transaction->serial = $details['data']['serialNumber'];
                $transaction->instructions = $details['data']['redemptionInstructions'];
                $transaction->save();

                Log::info('Gift card PIN saved', [
                    'transaction_id' => $transaction->transaction_id,
                    'pin' => $details['data']['pinCode']
                ]);

                // ✅ Send Email to User
                $user = User::find($transaction->user_id);
                if ($user) {
                    Mail::to($user->email)->send(new TransactionSuccessMail(
                        $details['data'],
                        'Giftcard Purchase Details',
                        'Your giftcard purchase was successful. Below are your card details.'
                    ));
                }
            }
        } else {
            Log::warning('Gift card PIN not available yet', [
                'reloadly_transaction_id' => $this->reloadlyTransactionId
            ]);

            // Optional: retry after 30 seconds
            self::dispatch($this->reloadlyTransactionId, $this->localTransactionId)
                ->delay(now()->addSeconds(30));
        }
    }
}

