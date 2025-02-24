<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use App\Services\FCMService;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notifications = Notification::all();
        return view('notification.index', compact('notifications'));
    }

    public function notificationsAdmin(Request $request)
    {
        $user = $request->user();
        $notifications = Notification::where('receiver_id', '=', $user->id)->orderBy('created_at', 'desc')->get();
        
        return view('notification.notifications', compact('notifications'));
    }


    public function adminNotifications(Request $request)
    {
        try {
            $user = $request->user();
            // $notifications = Notification::all();
            $notifications = Notification::where('receiver_id', '=', $user->id)->orderBy('created_at', 'desc')
            ->take(4)
            ->get();
            $user->has_read = 1;
            $user->save();
            return response()->json(
                [
                    'success' => true,
                    'data' => $notifications
                ]
            );
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . throw $th
            ]);
        }
    }

    public function checkReadStatus(Request $request)
    {
        try {
            // Check if there are any unread notifications for the authenticated user
            $user = $request->user();
            $hasRead = $user->has_read;

            if ($hasRead == 1) {
                return response()->json([
                    'success' => true,
                    'data' => true
                ]);
            } else {
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('notification.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_title' => 'string|required',
            'notification_message' => 'string|required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'

        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('failed', $validator->errors()->first());
            
        }

        try {

            $notification_title = $request->input('notification_title');
            $notification_message = $request->input('notification_message');
            $image = $request->file('image');

            $model = new Notification();
            $model->notification_title = $notification_title;
            // $model->notification_message = $notification_message;

            $strippedMessage = html_entity_decode($notification_message);
            $strippedMessage = strip_tags($strippedMessage);
            $model->notification_message = $strippedMessage;

            if (
                $image != null
            ) {

                $ext = $image->getClientOriginalExtension();
                $fileName = rand(10000, 50000) . '.' . $ext;
                if ($ext == 'jpg' || $ext == 'png') {
                    if ($image->move(public_path(), $fileName)) {

                        $model->image = url('/') . '/' . $fileName;
                    } else {
                        return redirect()->back()->with('failed', 'failed to upload, please check your internet');
                    }
                } else {
                    return redirect()->back()->with('failed', 'Please upload png or jpg/jpeg');
                }

            }

            if ($model->save()) {
                User::where('has_read', 1)->update(['has_read' => 0]);
                $users = User::whereNotNull('fcm_token')->get();

                foreach ($users as $user) {
                    $this->fcmService->sendNotification(
                        $user->fcm_token,
                        $notification_title,
                        $strippedMessage,
                    );
                }
                return redirect()->back()->with('success', 'Notification  saved successfully!');
            }
            return redirect()->back()->with('failed', 'Notification could not be saved!');
        } catch (RequestException $e) {
            // Handle exceptions that occur during the HTTP request
            Log::error("Request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error("An error occurred: " . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Notification $notification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $notification = Notification::find($id);
        return view('notification.edit', compact('notification'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $notification_title = $request->input('notification_title');
        $notification_message = $request->input('notification_message');
        $image = $request->file('image');

        // if (
        //     $image != null
        // ) {

        $model = Notification::find($id);
        $model->notification_title = $notification_title;
        // $model->notification_message = $notification_message;
        $strippedMessage = html_entity_decode($notification_message);
            $strippedMessage = strip_tags($strippedMessage);
            $model->notification_message = $strippedMessage;

        if ($image != null) {

            $ext = $image->getClientOriginalExtension();
            $fileName = rand(10000, 50000) . '.' . $ext;
            if ($ext == 'jpg' || $ext == 'png') {
                if ($image->move(public_path(), $fileName)) {

                    $model->image = url('/') . '/' . $fileName;
                } else {
                    return redirect()->back()->with('failed', 'failed to upload, please check your internet');
                }
            } else {
                return redirect()->back()->with('failed', 'Please upload png or jpg/jpeg');
            }
        } else {

            $model->image = $request->input('image_update');
        }


        if ($model->save()) {
            return redirect()->back()->with('success', 'Notification  updated successfully!');
        }
        return redirect()->back()->with('failed', 'Notification could not be updated!');
        // }
        // return redirect()->back()->with('failed', 'Please fill all the compulsory fields!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $delete =  Notification::destroy($id);

        if ($delete) {
            return redirect()->back()->with('deleted', 'deleted successfully!');
        }

        return redirect()->back()->with('delete-failed', 'Could not be deleted!');
    }

    public function saveToken(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                // $user->update(['fcm_token' => $request->fcm_token]);
                $user->fcm_token = $request->fcm_token;
                $user->save();
                Log::info('token saved ' . $request->fcm_token);
                return response()->json(['success' => true]);
            }
            Log::info('token not saved');
            return response()->json(['success' => false], 401);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error response: ' . throw $th);
        }
    }
}
