<div class="settings-container">
    <div class="settings-menu">
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
        <div wire:click="setActiveTab('api')" 
        class="menu-item {{ $activeTab === 'api' ? 'active-tab' : '' }}">
            <i class="fas fa-key"></i>
            API Settings
        </div>
        <div wire:click="setActiveTab('database')" 
        class="menu-item {{ $activeTab === 'database' ? 'active-tab' : '' }}">
            <i class="fas fa-key"></i>
            Database Settings
        </div>
        <div wire:click="setActiveTab('danger')" 
        class="menu-item {{ $activeTab === 'danger' ? 'active-tab' : '' }}">
            <i class="fas fa-exclamation-triangle"></i>
            Danger Zone
        </div>
    </div>
    
    <div class="settings-content">
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
                            <h3>Name</h3>
                            <input type="text" wire:model="name" required>
                            @error('name') <span class="error">{{ $message }}</span> @enderror
                        </div>
            
                        <div class="form-group">
                            <h3>Email Address</h3>
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
            <p>Customize your application experience.</p>
            
            <div class="section">
                <h2>Notification Settings</h2>
                <p>Manage how you receive notifications.</p>
                
                <div class="form-group">
                    <label class="toggle-switch">
                        <input type="checkbox" class="toggle-input" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Email notifications</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="toggle-switch">
                        <input type="checkbox" class="toggle-input" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Push notifications</span>
                    </label>
                </div>
                
                <button class="btn btn-primary">Save Preferences</button>
            </div>
            
            
        </div>

        <!-- API Section -->
        <div id="api" class="content-section" @if($activeTab === 'api') style="display:block;" @else style="display:none;" @endif>
            <h1>API Settings</h1>
            <p>Configure your API connections and credentials.</p>
            
            <div class="api-tabs">
                <div class="api-tab-header">
                    <div class="api-tab active" data-tab="vtpass">VTpass API</div>
                    <div class="api-tab" data-tab="glad">Glad API</div>
                </div>
                
                <div class="api-tab-content active" id="vtpass">
                    <div class="api-card">
                        <label class="toggle-switch">
                            <input type="checkbox" class="toggle-input" checked>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Enable VTpass API</span>
                        </label>

                        <div class="form-group">
                            <label for="vtpass-api-key">API Key</label>
                            <div class="text-input-container">
                                <input type="text" id="vtpass-api-key" class="api-input" placeholder="Enter your VTpass API key">
                                
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="vtpass-public-key">Public Key</label>
                            <div class="text-input-container">
                                <input type="text" id="vtpass-public-key" class="api-input" placeholder="Enter your VTpass public key">
                                
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="vtpass-private-key">Private Key</label>
                            <div class="text-input-container">
                                <input type="password" id="vtpass-private-key" class="api-input" placeholder="Enter your VTpass private key">
                                <button class="btn-toggle-visibility" data-target="vtpass-private-key">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button class="btn btn-primary">Save VTpass Settings</button>
                    </div>
                </div>
                
                <div class="api-tab-content" id="glad">
                    <div class="api-card">
                        <label class="toggle-switch">
                            <input type="checkbox" class="toggle-input">
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Enable Glad API</span>
                        </label>

                        <div class="form-group">
                            <label for="glad-api-key">API Key</label>
                            <div class="text-input-container">
                                <input type="text" id="glad-api-key" class="api-input" placeholder="Enter your Glad API key">
                                
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="glad-public-key">Public Key</label>
                            <div class="text-input-container">
                                <input type="text" id="glad-public-key" class="api-input" placeholder="Enter your Glad public key">
                                
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="glad-private-key">Private Key</label>
                            <div class="text-input-container">
                                <input type="password" id="glad-private-key" class="api-input" placeholder="Enter your Glad private key">
                                <button class="btn-toggle-visibility" data-target="glad-private-key">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button class="btn btn-primary">Save Glad Settings</button>
                    </div>
                </div>

            </div>
        </div>
        
        <!-- Database  Section -->
        <div id="database" class="content-section" @if($activeTab === 'database') style="display:block;" @else style="display:none;" @endif>
            <h1>Danger Zone</h1>
            
            <div class="database-zone">
                
                    <div class="form-group">
                        <label for="database-name">Database Name</label>
                        <div class="text-input-container">
                            <input type="text" id="database-name" class="api-input" placeholder="Enter Database Name">
                            
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="database-username">Database Username</label>
                        <div class="text-input-container">
                            <input type="text" id="database-username" class="api-input" placeholder="Enter Database Username">
                            
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="database-password">Database Password</label>
                        <div class="text-input-container">
                            <input type="password" id="database-password" class="api-input" placeholder="Enter Database Password">
                            <button class="btn-toggle-visibility" data-target="database-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button class="btn btn-primary">Save Settings</button>
                
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
                
                <button class="btn btn-danger">Delete Account</button>
            </div>
        </div>
    </div>
</div>
