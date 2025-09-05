<div class="settings-container">

    <!-- Tab Navigation for Small Screens -->
    <div class="settings-tabs" id="settingsTabs">
        <button wire:click="setActiveTab('profile')" 
                class="tab-item {{ $activeTab === 'profile' ? 'active-tab' : '' }}">
            <i class="fas fa-user"></i> Profile
        </button>
        <button wire:click="setActiveTab('security')" 
                class="tab-item {{ $activeTab === 'security' ? 'active-tab' : '' }}">
            <i class="fas fa-lock"></i> Security
        </button>
        <button wire:click="setActiveTab('preferences')" 
                class="tab-item {{ $activeTab === 'preferences' ? 'active-tab' : '' }}">
            <i class="fas fa-cog"></i> Preferences
        </button>
        <button wire:click="setActiveTab('danger')" 
                class="tab-item {{ $activeTab === 'danger' ? 'active-tab' : '' }}">
            <i class="fas fa-exclamation-triangle"></i> Danger Zone
        </button>
    </div>

        <!-- Sidebar Menu for Larger Screens -->
    <div class="settings-menu card">
        <div class="menu-category">ACCOUNT</div>
        <div wire:click="setActiveTab('profile')" 
        class="menu-item {{ $activeTab === 'profile' ? 'active-tab' : '' }}">
            <i class="fas fa-user"></i>
            Profile
        </div>
        <div wire:click="setActiveTab('security')" 
        class="menu-item {{ $activeTab === 'security' ? 'active-tab' : '' }}">
            <i class="fas fa-lock"></i>
            Security
        </div>
        <div wire:click="setActiveTab('preferences')" 
        class="menu-item {{ $activeTab === 'preferences' ? 'active-tab' : '' }}">
            <i class="fas fa-cog"></i>
            Preferences
        </div>
        
        <div class="menu-category">ACTIONS</div>
       
        <div wire:click="setActiveTab('danger')" 
        class="menu-item {{ $activeTab === 'danger' ? 'active-tab' : '' }}">
            <i class="fas fa-exclamation-triangle"></i>
            Danger Zone
        </div>
    </div>
    
    <div class="settings-content card">
        <div id="profile" class="content-section" @if($activeTab === 'profile') style="display:block;" @else style="display:none;" @endif>
            <h1>Profile Settings</h1>
            <p>Manage your profile and account settings.</p>
            
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            <div>
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
            
                <div class="section">
                    <h2>Profile Information</h2>
                    <p>Update your name and email address.</p>
            
                    <form wire:submit.prevent="updateProfile">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" wire:model="name" required>
                            @error('name') <span class="error">{{ $message }}</span> @enderror
                        </div>
            
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" wire:model="email" required>
                            @error('email') <span class="error">{{ $message }}</span> @enderror
                        </div>
            
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
            
        </div>
        
        <!-- Security Section -->
        <div id="security" class="content-section" @if($activeTab === 'security') style="display:block;" @else style="display:none;" @endif>
            <h1>Security Settings</h1>
            <p>Manage your account security and authentication.</p>
            
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            <div class="section">
                <h2>Change Password</h2>
                <form wire:submit.prevent="updatePassword">
                    @csrf
                    <div class="form-group">
                        <label for="current-password">Current Password</label>
                        <div class="text-input-container">
                            <input type="password" wire:model="current_password" id="current-password" required>
                            <button type="button" class="btn-toggle-visibility" data-target="current-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <div class="text-input-container">
                            <input type="password" wire:model="new_password" id="new-password" required>
                            <button type="button" class="btn-toggle-visibility" data-target="new-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('new_password')
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <div class="text-input-container">
                            <input type="password" wire:model="new_password_confirmation" id="confirm-password" required>
                            <button type="button" class="btn-toggle-visibility" data-target="confirm-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
        
        <!-- Preferences Section -->
        <div id="preferences" class="content-section" @if($activeTab === 'preferences') style="display:block;" @else style="display:none;" @endif>
            <h1>Preferences</h1>
            <p>Customize application settings.</p>
            
            <div class="section">
                <h2>Manage Settings</h2>
                <p>Enable or Disable a service.</p>
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <p>Enable/Disable Card Payment</p>
                <div class="form-group">
                    <label class="toggle-switch">
                        <span class="toggle-label">Card Payment</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('card_payment')" {{ $cardPaymentEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $cardPaymentEnabled ? 'state-on' : 'state-off' }}">
                                {{ $cardPaymentEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <p>Enable/Disable VTpass API</p>
                <div class="form-group">
                    <label class="toggle-switch">
                        <span class="toggle-label">VTpass API</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('vtpass')" {{ $vtpassEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $vtpassEnabled ? 'state-on' : 'state-off' }}">
                                {{ $vtpassEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                </div>
                    </div>
                </div>



                <div class="card shadow-sm">
                <div class="card-body p-3">
                    <p>Enable/Disable Airtime API</p>
                <div class="form-group">
                    <label class="toggle-switch">
                        <span class="toggle-label">ARTX API</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('artx_airtime')" {{ $artxAirtimeEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $artxAirtimeEnabled ? 'state-on' : 'state-off' }}">
                                {{ $artxAirtimeEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="toggle-switch">
                        <span class="toggle-label">Glad API</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('glad_airtime')" {{ $gladAirtimeEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $gladAirtimeEnabled ? 'state-on' : 'state-off' }}">
                                {{ $gladAirtimeEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
            
            <div class="card shadow-sm">
                <div class="card-body p-3">
                    <p>Enable/Disable Data API</p>
                <div class="form-group">
                    <label class="toggle-switch">
                        <span class="toggle-label">ARTX API</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('artx_data')" {{ $artxDataEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $artxDataEnabled ? 'state-on' : 'state-off' }}">
                                {{ $artxDataEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="toggle-switch">
                        <span class="toggle-label">Glad API</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('glad_data')" {{ $gladDataEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $gladDataEnabled ? 'state-on' : 'state-off' }}">
                                {{ $gladDataEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                </div>
            </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-3">
                    <p>Enable/Disable Giftcard API</p>
                <div class="form-group">
                    <label class="toggle-switch">
                        <span class="toggle-label">Enable Artx Giftcard</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('artx_giftcard')" {{ $artxgiftcardEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $artxgiftcardEnabled ? 'state-on' : 'state-off' }}">
                                {{ $artxgiftcardEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                </div>
                </div>
            </div>
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <p>Enable/Disable Referral Service</p>
                    <label class="toggle-switch">
                        <span class="toggle-label">Enable Referral</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('referral')" {{ $referralEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $referralEnabled ? 'state-on' : 'state-off' }}">
                                {{ $referralEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                    <div class="bonus-header">
                        <label for="referral_bonus" class="bonus-label">
                            <i class="fas fa-gift bonus-icon"></i>
                            Referral Bonus
                        </label>
                        <span class="bonus-currency">NGN</span>
                    </div>
                    
                    <div class="bonus-input-group">
                        <input type="number" 
                               id="referral_bonus" 
                               step="0.01" 
                               min="0"
                               wire:model.defer="referralBonus"
                               class="bonus-input"
                               placeholder="0.00">
                        
                               <button wire:click="saveReferralBonus" 
                               class="bonus-save-btn"
                               wire:loading.attr="disabled"
                               wire:target="saveReferralBonus">
                           <span wire:loading.remove wire:target="saveReferralBonus">
                               <i class="fas fa-check"></i> Update
                           </span>
                           <span wire:loading wire:target="saveReferralBonus">
                               <i class="fas fa-spinner fa-spin"></i> Saving
                           </span>
                       </button>
                    </div>
                    
                    <div class="bonus-hint">Set the amount rewarded for each successful referral</div>
                </div>
            </div>

            <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <p>Enable/Disable Virtual Account Charges</p>
                    <label class="toggle-switch">
                        <span class="toggle-label">Enable Charges</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('virtual_charge')" {{ $virtualEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $virtualEnabled ? 'state-on' : 'state-off' }}">
                                {{ $virtualEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                    <div class="bonus-header">
                        <label for="virtual_charge" class="bonus-label">
                            <i class="fas fa-gift bonus-icon"></i>
                            Charge Amount
                        </label>
                        <span class="bonus-currency">NGN</span>
                    </div>
                    
                    <div class="bonus-input-group">
                        <input type="number" 
                               id="virtual_charge" 
                               step="0.01" 
                               min="0"
                               wire:model.defer="virtualCharge"
                               class="bonus-input"
                               placeholder="0.00">
                        
                               <button wire:click="saveVirtualCharge" 
                               class="bonus-save-btn"
                               wire:loading.attr="disabled"
                               wire:target="saveVirtualCharge">
                           <span wire:loading.remove wire:target="saveVirtualCharge">
                               <i class="fas fa-check"></i> Update
                           </span>
                           <span wire:loading wire:target="saveVirtualCharge">
                               <i class="fas fa-spinner fa-spin"></i> Saving
                           </span>
                       </button>
                    </div>
                    
                    <div class="bonus-hint">Set the amount charged for virtual account transactions</div>
                </div>
            </div>

            <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <p>Enable/Disable Card Payment Charges</p>
                    <label class="toggle-switch">
                        <span class="toggle-label">Enable Card Charges</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('card_payment')" {{ $cardPaymentEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $cardPaymentEnabled ? 'state-on' : 'state-off' }}">
                                {{ $cardPaymentEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                    <div class="bonus-header">
                        <label for="card_charge" class="bonus-label">
                            <i class="fas fa-credit-card bonus-icon"></i>
                            Card Charge Amount
                        </label>
                        <span class="bonus-currency">NGN</span>
                    </div>
                    
                    <div class="bonus-input-group">
                        <input type="number" 
                               id="card_charge" 
                               step="0.01" 
                               min="0"
                               wire:model.defer="cardCharge"
                               class="bonus-input"
                               placeholder="0.00">
                        
                               <button wire:click="saveCardCharge" 
                               class="bonus-save-btn"
                               wire:loading.attr="disabled"
                               wire:target="saveCardCharge">
                           <span wire:loading.remove wire:target="saveCardCharge">
                               <i class="fas fa-check"></i> Update
                           </span>
                           <span wire:loading wire:target="saveCardCharge">
                               <i class="fas fa-spinner fa-spin"></i> Saving
                           </span>
                       </button>
                    </div>
                    
                    <div class="bonus-hint">Set the amount charged for card payment transactions</div>
                </div>
            </div>

            <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <p>Enable/Disable Maintenance Mode</p>
                    <label class="toggle-switch">
                        <span class="toggle-label">Maintenance Mode</span>
                        <input type="checkbox"class="toggle-input" @change="$wire.toggleSetting('maintenance')" {{ $maintenanceEnabled ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">
                            <span class="{{ $maintenanceEnabled ? 'state-on' : 'state-off' }}">
                                {{ $maintenanceEnabled ? 'On' : 'Off' }}
                            </span>
                        </span>
                    </label>
                    <div class="bonus-hint">Enable maintenance mode to temporarily disable the application for users</div>
                </div>
            </div>
                
                
            </div>
            
            
        </div>
        <!-- Danger Zone Section -->
        <div id="danger" class="content-section" @if($activeTab === 'danger') style="display:block;" @else style="display:none;" @endif>
            <h1>Danger Zone</h1>
            
            <div class="danger-zone">
                <h2>Delete Account</h2>
                <p>Delete your account and all of its resources.</p>
                
                <div class="form-group">
                    <h3 class="warning">Warning</h3>
                    <p class="warning">Please proceed with caution, this cannot be undone.</p>
                </div>
                
                <button class="btn btn-danger" id="deleteAccountBtn">
                    Delete Account
                </button>                
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('deleteAccountBtn').addEventListener('click', function () {
        Swal.fire({
            title: "Are you sure?",
            text: "This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('deleteAccount'); 
            }
        });
    });
</script>
