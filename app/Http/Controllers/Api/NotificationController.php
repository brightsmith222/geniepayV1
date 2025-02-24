<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\RequestException;
use App\Models\Notification;



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


    public function checkReadStatus(Request $request)
    {
        try {
            // $hasRead = Notification::where('has_read', '=', 0)->first();
            // Check if there are any unread notifications for the authenticated user
            $user = $request->user();
        $hasRead = $user->has_read;

            if ($hasRead == 1) {
                return response()->json([
                    'status' => true,
                    'data' => true
                ]);
            } 
            else {
                return response()->json([
                    'status' => true,
                    'data' => false
                ]);
            }
        } catch (\Throwable $th) {
            Log::error('Error: ' . throw $th);
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . throw $th
            ]);
        }
    }


    public function sendPushNotif(Request $request){

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'message' => 'required|string',
            'fcm_token' => 'required|string',


        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                // 'message' => $validator->errors()
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {

            $this->fcmService->sendNotification(
                $request->fcm_token,
                $request->title,
                $request->message,
            );

            Log::info("Request  was successful:");
            return response()->json([
                'status' => true,
                'message' => 'Message sent successfully'
            ], 200);
            
        } catch (RequestException $e) {
            // Handle exceptions that occur during the HTTP request
            Log::error("Request failed: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error("An error occurred: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }





    }
}
