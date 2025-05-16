<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Notification;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithPagination;


class AppNotification extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['deleteNotification' => 'deleteNotification'];

    // Form fields
    public $notification_title;
    public $notification_message;
    public $include_image = false;
    public $image;
    public $sendTo = 'all'; 
    public $recipients; 
    public $showImageUpload = false; 
    public $showSpecificUsers = false; 
    public $searchTerm = ''; 

    // Validation rules
    protected $rules = [
        'notification_title' => 'required|string|max:255',
        'notification_message' => 'required|string',
        'include_image' => 'boolean',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'sendTo' => 'required|in:all,inactive_7_days,inactive_1_month,inactive_3_months,inactive_6_months',
    ];

    public function mount()
    {
        $this->recipients = collect(); 
    }

    // Render the component view
    public function render()
    {
        $users = [];
        if (!empty($this->searchTerm)) {
            $users = User::where('username', 'like', '%' . $this->searchTerm . '%')
                ->limit(5)
                ->get();
        }

        $notifications = Notification::latest()->paginate(6)->onEachSide(1);

        return view('livewire.app-notification', [
            'users' => $users,
            'notifications' => $notifications,
        ]);
    }


    // Update the recipients based on the selected option
    public function updateRecipientFilter()
    {
        switch ($this->sendTo) {
            case 'all':
                $this->recipients = User::whereNotNull('fcm_token')->get(); 
                break;

            case 'inactive_7_days':
                $this->recipients = User::whereDoesntHave('transactions', function ($query) {
                    $query->where('created_at', '>=', now()->subDays(7));
                })->whereNotNull('fcm_token')->get();
                break;

            case 'inactive_1_month':
                $this->recipients = User::whereDoesntHave('transactions', function ($query) {
                    $query->where('created_at', '>=', now()->subMonth());
                })->whereNotNull('fcm_token')->get();
                break;

            case 'inactive_3_months':
                $this->recipients = User::whereDoesntHave('transactions', function ($query) {
                    $query->where('created_at', '>=', now()->subMonths(3));
                })->whereNotNull('fcm_token')->get();
                break;

            case 'inactive_6_months':
                $this->recipients = User::whereDoesntHave('transactions', function ($query) {
                    $query->where('created_at', '>=', now()->subMonths(6));
                })->whereNotNull('fcm_token')->get();
                break;

            default:
                $this->recipients = collect();
                break;
        }
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
    $this->validate();

    $imageUrl = null;
    
    // Handle image upload if needed
    if ($this->include_image && $this->image) {
        try {
            $ext = $this->image->getClientOriginalExtension();
            $fileName = 'notif_'.time().'_'.Str::random(5).'.'.$ext;
            
            // Store in public disk
            $path = $this->image->storeAs('notifications', $fileName, 'public');
            
            // Use this format for the URL
            $imageUrl = 'storage/notifications/'.$fileName;
            
        } catch (\Exception $e) {
            session()->flash('failed', 'File upload failed: '.$e->getMessage());
            return;
        }
    }

    

    // Save the notification
    $notification = Notification::create([
        'notification_title'  => $this->notification_title,
        'notification_message'=> $this->notification_message,
        'image'              => $imageUrl,
        'receiver_id'        => $this->sendTo === 'all' ? null : json_encode($this->recipients->pluck('id')->toArray()),
    ]);

    // Send the notification
   // $this->sendNotification($notification);

    // Reset form fields
    $this->resetForm();
    flash()->success('Notification sent successfully!');
}






    // Send notification to users
    protected function sendNotification($notification)
    {
        $fcmService = new FCMService();

        // Send the push notification to each recipient
        foreach ($this->recipients as $user) {
            dispatch(function () use ($user, $notification, $fcmService) {
                $imagePath = $notification->image ? asset('storage/' . $notification->image) : null;
        
                $fcmService->sendNotification(
                    $user->fcm_token,
                    $notification->notification_title,
                    $notification->notification_message,
                    $imagePath
                );
            });
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
            'recipients',
            'showImageUpload',
        ]);
    }



public function confirmDelete($id)
{
    $this->emit('swal:confirm', [
        'type' => 'warning',
        'title' => 'Are you sure?',
        'text' => "You won't be able to revert this!",
        'id' => $id
    ]);
}

public function deleteNotification($id)
{
    $notification = Notification::find($id);
    
    if ($notification->image) {
        $imagePath = str_replace('storage/', '', $notification->image);
        Storage::disk('public')->delete($imagePath);
    }
    
    $notification->delete();
    
    flash()->success('Notification deleted successfully');
}

   
}