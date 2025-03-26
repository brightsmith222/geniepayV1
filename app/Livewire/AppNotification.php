<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Notification;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;


class AppNotification extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Form fields
    public $notification_title;
    public $notification_message;
    public $include_image = false;
    public $image;
    public $sendTo = 'all';
    public $specific_users = [];
    public $selectedUsers = []; 
    public $showImageUpload = false; 
    public $showSpecificUsers = false; 
    public $searchTerm = ''; 

    // Validation rules
    protected $rules = [
        'notification_title' => 'required|string|max:255',
        'notification_message' => 'required|string',
        'include_image' => 'boolean',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'sendTo' => 'required|in:all,specific',
        'specific_users' => 'required_if:sendTo,specific|array',
    ];

    // Render the component view
    public function render()
{
    // Fetch users dynamically for the search
    $users = [];
    if (!empty($this->searchTerm)) {
        $users = User::where('username', 'like', '%' . $this->searchTerm . '%')
            ->limit(5)
            ->get();
    }

    // Fetch latest notifications with pagination
    $notifications = Notification::latest()->paginate(6);

    // Get selected users' details
    $selectedUserModels = User::whereIn('id', $this->selectedUsers)->get();

    return view('livewire.app-notification', [
        'users' => $users,
        'notifications' => $notifications,
        'selectedUserModels' => $selectedUserModels,
    ])->extends('layouts.app')->section('content');
}

protected function getListeners()
{
    return [
        'notificationAdded' => '$refresh',
    ];
}

    


    // Handle form submission
    public function submit()
{
    $imagePath = null;
    
    $this->validate();

    // Handle image upload if needed
    if ($this->include_image && $this->image) {
        $imagePath = $this->image->store('notifications', 'public');
    }

    // Decode the selected users if they're specific
    $receiverId = $this->sendTo === 'all' 
        ? null  
        : json_encode($this->selectedUsers); 

    // Save the notification
    $notification = Notification::create([
        'notification_title'  => $this->notification_title,
        'notification_message'=> $this->notification_message,
        'image'               => $imagePath,
        'receiver_id'         => $receiverId,
    ]);

    // Send the notification
    $this->sendNotification($notification);

    // Emit an event to refresh the table
    $this->dispatch('notificationAdded');

    // Reset form fields
    $this->reset();
}






    // Send notification to users
    protected function sendNotification($notification)
{
    $fcmService = new FCMService();

    // Determine which users to send the notification to
    if ($this->sendTo === 'all') {
        // Send to all users who have an FCM token
        $users = User::whereNotNull('fcm_token')->get();
    } else {
        // Send to specific users who have an FCM token
        $users = User::whereIn('id', $this->selectedUsers)
            ->whereNotNull('fcm_token')
            ->get();
    }

    // Send the push notification to each user
    foreach ($users as $user) {
        // If there's an image, pass it; otherwise, pass null
        $imagePath = $notification->image ? asset('storage/' . $notification->image) : null;

        $fcmService->sendNotification(
            $user->fcm_token,
            $notification->notification_title,
            $notification->notification_message,
            $imagePath // Include the image if available
        );
    }
}



    // Reset the form fields
    protected function resetForm()
    {
        $this->reset([
            'notification_title',
            'notification_message',
            'include_image',
            'image',
            'sendTo',
            'specific_users',
        ]);
    }

    public function toggleUser($userId)
{
    if (in_array($userId, $this->selectedUsers)) {
        // Remove user from selected list
        $this->selectedUsers = array_values(array_diff($this->selectedUsers, [$userId]));
    } else {
        // Add user to selected list
        $this->selectedUsers[] = $userId;
    }

    // Force Livewire to refresh the UI
    $this->dispatch('refreshComponent');
}


public function selectFirstUser()
{
    // Get the first user from the filtered list
    $user = User::where('username', 'like', '%' . $this->searchTerm . '%')->first();

    if ($user) {
        // Toggle the user selection
        $this->toggleUser($user->id);
        
        // Clear the search term after selection
        $this->searchTerm = '';
    }
}


   
}