<?php

namespace App\Jobs;

use App\Services\FcmService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $title;
    public string $message;

    public function __construct(string $title, string $message)
    {
        $this->title = $title;
        $this->message = $message;
    }

    public function handle(FcmService $fcmService): void
    {
        $users = User::whereNotNull('fcm_token')->get();

        foreach ($users as $user) {
            try {
                $fcmService->sendNotification($user->fcm_token, $this->title, $this->message);
            } catch (\Exception $e) {
                Log::error("Failed to send FCM to user ID {$user->id}: " . $e->getMessage());
            }
        }
    }
}
