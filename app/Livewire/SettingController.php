<?php

namespace App\Livewire;
use App\Models\User;
use App\Models\GeneralSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class SettingController extends Component
{
    public $name;
    public $email;
    public $current_password;
    public $new_password;
    public $new_password_confirmation;
    public $activeTab = 'profile';
    public $referralEnabled;
    public $virtualEnabled;
    public $virtualCharge;
    public $vtpassEnabled;
    public $gladDataEnabled;
    public $artxDataEnabled;
    public $gladAirtimeEnabled;
    public $artxAirtimeEnabled;
    public $artxgiftcardEnabled;
    public $cardPaymentEnabled;
    public $cardCharge;
    public $referralBonus;
    public $maintenanceEnabled;
    protected $listeners = ['deleteAccount'];
    


    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        session()->put('active_tab', $tab);
    }
    

    public function mount()
{
    $user = Auth::user();
    $this->name = $user->full_name;
    $this->email = $user->email;
    $this->activeTab = session()->get('active_tab', 'profile');

    // Initialize toggle values from database
    $this->referralEnabled = (bool) GeneralSettings::where('name', 'referral')->value('is_enabled');
    $this->virtualEnabled = (bool) GeneralSettings::where('name', 'virtual_charge')->value('is_enabled');
    $this->vtpassEnabled = (bool) GeneralSettings::where('name', 'vtpass')->value('is_enabled');
    $this->gladDataEnabled = (bool) GeneralSettings::where('name', 'glad_data')->value('is_enabled');
    $this->artxDataEnabled = (bool) GeneralSettings::where('name', 'artx_data')->value('is_enabled');
    $this->gladAirtimeEnabled = (bool) GeneralSettings::where('name', 'glad_airtime')->value('is_enabled');
    $this->artxAirtimeEnabled = (bool) GeneralSettings::where('name', 'artx_airtime')->value('is_enabled');
    $this->artxgiftcardEnabled = (bool) GeneralSettings::where('name', 'artx_giftcard')->value('is_enabled');
    $this->cardPaymentEnabled = (bool) GeneralSettings::where('name', 'card_payment')->value('is_enabled');
    $this->maintenanceEnabled = (bool) GeneralSettings::where('name', 'maintenance')->value('is_enabled');
    $this->referralBonus = GeneralSettings::where('name', 'referral')->value('referral_bonus') ?? 0;
    $this->virtualCharge = GeneralSettings::where('name', 'virtual_charge')->value('virtual_percentage') ?? 0;
    $this->cardCharge = GeneralSettings::where('name', 'card_payment')->value('card_charge') ?? 0;

}


// Update database when the toggle is changed
public function toggleSetting($name)
{
    $setting = GeneralSettings::firstOrCreate(['name' => $name]);
    $setting->is_enabled = !$setting->is_enabled;
    $setting->save();

    if ($name === 'referral') {
        $this->referralEnabled = $setting->is_enabled;
    }

    if ($name === 'virtual_charges') {
        $this->virtualEnabled = $setting->is_enabled;
    }

    if ($name === 'card_payment') {
        $this->cardPaymentEnabled = $setting->is_enabled;
    }

    if ($name === 'vtpass') {
        $this->vtpassEnabled = $setting->is_enabled;
    }

    if ($name === 'glad_airtime') {
        $this->gladAirtimeEnabled = $setting->is_enabled;
    }

    if ($name === 'artx_airtime') {
        $this->artxAirtimeEnabled = $setting->is_enabled;
    }

    if ($name === 'glad_data') {
        $this->gladDataEnabled = $setting->is_enabled;
    }

    if ($name === 'artx_data') {
        $this->artxDataEnabled = $setting->is_enabled;
    }

    if ($name === 'artx_giftcard') {
        $this->artxgiftcardEnabled = $setting->is_enabled;
    }

    if ($name === 'maintenance') {
        $this->maintenanceEnabled = $setting->is_enabled;
    }
    

    flash()->success('Setting updated successfully!');
}



public function saveReferralBonus()
{
    Log::info('Saving referral bonus: ' . $this->referralBonus);

    GeneralSettings::updateOrCreate(
        ['name' => 'referral'],
        ['referral_bonus' => $this->referralBonus]
    );

    flash()->success('Referral bonus updated successfully!');

}

public function saveVirtualCharge()
{
    Log::info('Saving referral bonus: ' . $this->virtualCharge);

    GeneralSettings::updateOrCreate(
        ['name' => 'virtual_charge'],
        ['virtual_percentage' => $this->virtualCharge]
    );

    flash()->success('Virtual charge updated successfully!');

}

public function saveCardCharge()
{
    Log::info('Saving card charge: ' . $this->cardCharge);

    GeneralSettings::updateOrCreate(
        ['name' => 'card_payment'],
        ['card_charge' => $this->cardCharge]
    );

    flash()->success('Card charge updated successfully!');

}
  
  
// Update profile information
    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(Auth::id())],
        ]);

        Auth::user()->update([
            'full_name' => $this->name,
            'email' => $this->email,
        ]);

        flash()->success('Profile updated successfully!');
    }

    // Update password
    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($this->current_password, Auth::user()->password)) {
            flash()->error('Current password is incorrect');

            session()->put('active_tab', 'security');
            $this->activeTab = 'security';

            return;
        }

        // Check if the new password is the same as the old password
    if (Hash::check($this->new_password, Auth::user()->password)) {
        flash()->error('New password cannot be the same as the current password');

        session()->put('active_tab', 'security');
        $this->activeTab = 'security';

        return; 
    }

        Auth::user()->update([
            'password' => Hash::make($this->new_password)
        ]);

        flash()->success('Password changed successfully!');

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->put('active_tab', 'security');
        $this->activeTab = 'security';
    }

    


    // Delete account
    public function deleteAccount()
{
    $user = Auth::user();
    $userId = $user->id;
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    User::where('id', $userId)->delete(); // Use forceDelete() if using soft deletes
    return redirect('/')->with('success', 'Your account has been deleted.');
}

    /**
     * Get the maintenance mode status
     * @return bool
     */
    public function maintenance()
    {
        return (bool) GeneralSettings::where('name', 'maintenance')->value('is_enabled');
    }

    /**
     * Static method to check maintenance mode from anywhere in the application
     * @return bool
     */
    public static function isMaintenanceMode()
    {
        return (bool) GeneralSettings::where('name', 'maintenance')->value('is_enabled');
    }

    public function render()
    {
        return view('livewire.setting-controller');
    }
}
