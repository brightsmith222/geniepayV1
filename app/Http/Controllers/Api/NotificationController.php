<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\RequestException;
use App\Models\Notification;
use App\Models\User;



class NotificationController extends Controller
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    } 


    public function index(Request $request)
    {

        try {
            $user = $request->user();
            $notifications = Notification::where('receiver_id', '=', null)->orWhere('receiver_id', '=', $user->id)->get();
            $user->has_read = 1;
            $user->save();

            return response()->json(

                [
                    'status' => true,
                    'data' => $notifications
                ],
                200
            );
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => false,
                    'message' => throw $th
                ],
                500
            );
        }
    }


    public function show($id)
    {
        try {
            $notification = Notification::find($id);
            return response()->json(
                [
                    'status' => true,
                    'data' => $notification

                ],
                200

            );
        } catch (\Throwable $th) {
            Log::error('Error: ' . throw $th);
            return response()->json(
                [
                    'status' => false,
                    'message' => throw $th
                ],
                500
            );
        }
    }


    // public function checkReadStatus(Request $request)
    // {
    //     try {
    //          $hasRead = Notification::where('is_read', '=', 0)->first();
    //         // Check if there are any unread notifications for the authenticated user
    //     $hasRead = $user->has_read;

    //         if ($hasRead == 1) {
    //             return response()->json([
    //                 'status' => true,
    //                 'data' => true
    //             ]);
    //         } 
    //         else {
    //             return response()->json([
    //                 'status' => true,
    //                 'data' => false
    //             ]);
    //         }
    //     } catch (\Throwable $th) {
    //         Log::error('Error: ' . throw $th);
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Error: ' . throw $th
    //         ]);
    //     }
    // }

public function markAsRead(Request $request, $id)
{
    try {
        $user = $request->user();
        $notification = Notification::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('receiver_id', $user->id)
                  ->orWhereNull('receiver_id');
            })
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found.'
            ], 404);
        }

        $notification->is_read = 1;
        $notification->save();

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read.'
        ]);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => false,
            'message' => 'Error: ' . $th->getMessage()
        ], 500);
    }
}

public function sendPushNotif(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|string',
        'message' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    try {
        $tokens = User::whereNotNull('fcm_token')->pluck('fcm_token')->toArray();

        foreach ($tokens as $token) {
            $this->fcmService->sendNotification(
                $token,
                $request->title,
                $request->message,
            );
        }

        Log::info("Broadcast notification sent to all users.");
        return response()->json([
            'status' => true,
            'message' => 'Message sent to all users successfully'
        ], 200);

    } catch (\Exception $e) {
        Log::error("Broadcast error: " . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

}
