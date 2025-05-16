<?php

namespace App\Jobs;

use App\Models\Transactions;
use App\Models\WalletTransactions;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

class RequeryTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction;
    protected $walletTransaction;

    public function __construct(Transactions $transaction, WalletTransactions $walletTransaction)
    {
        $this->transaction = $transaction;
        $this->walletTransaction = $walletTransaction;
    }

    public function handle()
    {
        $txn = $this->transaction;
        $api = strtolower($txn->which_api);

        try {
            match ($api) {
                'artx' => $this->checkArtx($txn),
                'vtpass' => $this->checkVtpass($txn),
                'glad' => $this->checkGlad($txn),
                default => Log::warning("Unknown API: $api for {$txn->transaction_id}")
            };
        } catch (\Exception $e) {
            Log::error("Job error for txn {$txn->transaction_id}: " . $e->getMessage());
        }
    }

    protected function checkArtx($txn)
    {
        $salt = Str::random(40);
        $payload = [
            'auth' => [
                'username' => config('api.artx.username'),
                'salt' => $salt,
                'password' => hash('sha512', $salt . sha1(config('api.artx.password')))
            ],
            'version' => 5,
            'command' => 'getTransaction',
            'id' => $txn->transaction_id
        ];

        $res = Http::withoutverifying()->post(config('api.artx.base_url'), $payload)->json();
        $type = $res['status']['type'] ?? null;

        if ($type == 0) {
            $txn->update(['status' => 'Successful']);
        } elseif ($type == 2) {
            $this->refund($txn, 'ARTX');
        }
    }

    protected function checkVtpass($txn)
    {
        $headers = app(\App\Services\VtpassService::class)->getHeaders();

        $res = Http::withoutverifying()->withHeaders($headers)
            ->post(config('api.vtpass.base_url') . 'requery', ['request_id' => $txn->transaction_id])
            ->json();

        $code = $res['code'] ?? null;

        if ($code === '000') {
            $txn->update(['status' => 'Successful']);
        } elseif ($code === '0999') {
            $this->refund($txn, 'VTPASS');
        }
    }

    protected function checkGlad($txn)
    {
        $service = strtolower($txn->service);
        $urlMap = [
            'data' => "https://www.gladtidingsdata.com/api/data/requery/{$txn->transaction_id}",
            'airtime' => "https://www.gladtidingsdata.com/api/airtime/requery/{$txn->transaction_id}",
            'cable' => "https://www.gladtidingsdata.com/api/cable/requery/{$txn->transaction_id}",
            'electricity' => "https://www.gladtidingsdata.com/api/electricity/requery/{$txn->transaction_id}",
        ];

        $url = $urlMap[$service] ?? null;
        if (!$url) return;

        $res = Http::withoutverifying()->withHeaders([
            'Authorization' => 'Token ' . config('api.glad.api_key')
        ])->get($url)->json();

        $status = strtolower($res['Status'] ?? '');

        if ($status === 'successful') {
            $txn->update(['status' => 'Successful']);
        } elseif ($status === 'failed') {
            $this->refund($txn, 'Glad');
        }
    }

    protected function refund($txn, $source)
    {
        $user = User::where('id', $txn->user_id)->first();
        if (!$user) return;

        $user->wallet_balance += $txn->amount;
        $user->save();

        $txn->update(['status' => 'Refunded']);

        Log::info("{$source} transaction {$txn->transaction_id} failed — refunded user.");
    }
}
