@extends('layout')

@section('dashboard-content')

<div class="settings-container">
        <div class="settings-menu">
            <div class="menu-category">ACCOUNT</div>
            <div class="menu-item active" data-section="profile">
                <i class="fas fa-user"></i>
                Profile
            </div>
            <div class="menu-item" data-section="security">
                <i class="fas fa-lock"></i>
                Security
            </div>
            <div class="menu-item" data-section="preferences">
                <i class="fas fa-cog"></i>
                Preferences
            </div>
            
            <div class="menu-category">ACTIONS</div>
            <div class="menu-item" data-section="api">
                <i class="fas fa-cog"></i>
                API Settings
            </div>
            <div class="menu-item" data-section="danger">
                <i class="fas fa-exclamation-triangle"></i>
                Danger Zone
            </div>
        </div>
        
        <div class="settings-content">
            <!-- Profile Section -->
            <div id="profile" class="content-section active">
                <h1>Profile Settings</h1>
                <p>Manage your profile and account settings.</p>
                
                <div class="section">
                    <h2>Profile Information</h2>
                    <p>Update your name and email address.</p>
                    
                    <div class="form-group">
                        <h3>Name</h3>
                        <input type="text" id="name" value="test">
                    </div>
                    
                    <div class="form-group">
                        <h3>Email Address</h3>
                        <input type="email" id="email" value="test@example.com">
                    </div>
                    
                    <button class="btn btn-primary">Update Profile</button>
                </div>
            </div>
            
            <!-- Security Section -->
            <div id="security" class="content-section">
                <h1>Security Settings</h1>
                <p>Manage your account security and authentication.</p>
                
                <div class="section">
                    <h2>Change Password</h2>
                    <div class="form-group">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password">
                    </div>
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" id="confirm-password">
                    </div>
                    
                    <button class="btn btn-primary">Change Password</button>
                </div>
                
                <div class="section">
                    <h2>Two-Factor Authentication</h2>
                    <p>Add an extra layer of security to your account.</p>
                    <button class="btn btn-primary">Enable 2FA</button>
                </div>
            </div>
            
            <!-- Preferences Section -->
            <div id="preferences" class="content-section">
                <h1>Preferences</h1>
                <p>Customize your application experience.</p>
                
                <div class="section">
                    <h2>Notification Settings</h2>
                    <p>Manage how you receive notifications.</p>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" checked> Email notifications
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" checked> Push notifications
                        </label>
                    </div>
                    
                    <button class="btn btn-primary">Save Preferences</button>
                </div>
                
                <div class="section">
                    <h2>Language & Region</h2>
                    <div class="form-group">
                        <label for="language">Language</label>
                        <select id="language" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ddd;">
                            <option>English</option>
                            <option>Spanish</option>
                            <option>French</option>
                            <option>German</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-primary">Save Changes</button>
                </div>
            </div>

             <!-- Preferences Section -->
             <div id="api" class="content-section">
                <h1>API Settings</h1>
                <p>Customize your application experience.</p>
                
                <div class="section">
                   
                </div>
                
                <div class="section">
                    <h2>Language & Region</h2>
                    <div class="form-group">
                        <label for="language">Language</label>
                        <select id="language" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ddd;">
                            <option>English</option>
                            <option>Spanish</option>
                            <option>French</option>
                            <option>German</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-primary">Save Changes</button>
                </div>
            </div>
            
            <!-- Danger Zone Section -->
            <div id="danger" class="content-section">
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


@endsection

@section('scripts')
    <script src="{{ URL::to('assets/js/settings.js')}}"></script>
@endsection