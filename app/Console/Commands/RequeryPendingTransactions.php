<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transactions;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\VtpassService;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RequeryPendingTransactions extends Command
{
    protected $signature = 'transactions:requery';
    protected $description = 'Automatically check and update status of pending transactions';

    public function handle()
    {
        $cutoff = \Carbon\Carbon::now()->subHours(6);

        $pending = Transactions::where('status', 'Pending')
            ->where('created_at', '>=', $cutoff)->get();

        $this->info("Checking " . $pending->count() . " recent pending transactions...");

        foreach ($pending as $txn) {
            \App\Jobs\RequeryTransactionJob::dispatch($txn);
        }

        $this->info("Completed.");
    }


}
