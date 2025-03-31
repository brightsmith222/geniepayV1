<?php

namespace App\Livewire;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class SettingController extends Component
{
    public $name;
    public $email;
    public $current_password;
    public $new_password;
    public $new_password_confirmation;
    public $activeTab = 'profile';

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
    }

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

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($this->current_password, Auth::user()->password)) {
            $this->addError('current_password', 'Current password is incorrect');
            flash()->error('Current password is incorrect');

            session()->put('active_tab', 'security');
            $this->activeTab = 'security';
        }

        Auth::user()->update([
            'password' => Hash::make($this->new_password)
        ]);

        flash()->success('Password changed successfully!');

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->put('active_tab', 'security');
        $this->activeTab = 'security';
    }

    public function render()
    {
        return view('livewire.setting-controller');
    }
}
