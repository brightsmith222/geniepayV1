<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-bell text-primary mr-2"></i>Push Notifications
        </h1>
    </div>

    <!-- Success Message -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Notification Form Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-paper-plane mr-2"></i>Compose Notification
            </h5>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="submit" class="needs-validation" novalidate>
                <!-- Title Field -->
                <div class="form-group mb-4">
                    <label for="notificationTitle" class="font-weight-bold text-primary">
                        <i class="fas fa-heading mr-2"></i>Title
                    </label>
                    <input type="text" class="form-control border-primary" id="notificationTitle"
                        wire:model="notification_title" placeholder="Enter notification title" required>
                    @error('notification_title')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Message Field -->
                <div class="form-group mb-4">
                    <label for="notificationMessage" class="font-weight-bold text-primary">
                        <i class="fas fa-comment-alt mr-2"></i>Message
                    </label>
                    <textarea class="form-control border-primary" id="notificationMessage" wire:model="notification_message" rows="4"
                        placeholder="Enter your notification message here" required></textarea>
                    @error('notification_message')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Image Toggle -->
                <div class="form-group mb-4">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="includeImageToggle"
                            wire:model="include_image" wire:click="$toggle('showImageUpload')">
                        <label class="custom-control-label font-weight-bold text-primary" for="includeImageToggle">
                            <i class="fas fa-image mr-2"></i>Include Image
                        </label>
                    </div>
                </div>

                <!-- Image Upload (Conditional) -->
                @if ($showImageUpload)
                    <div class="form-group mb-4 border-left border-primary pl-3">
                        <label for="notificationImage" class="font-weight-bold text-primary">
                            <i class="fas fa-upload mr-2"></i>Upload Image
                        </label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="notificationImage" wire:model="image">
                            <label class="custom-file-label" for="notificationImage">Choose image file...</label>
                        </div>
                        @error('image')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        <!-- Image Preview -->
                        @if ($image)
                            @if (method_exists($image, 'temporaryUrl'))
                                <div class="mt-3 text-center">
                                    <img src="{{ $image->temporaryUrl() }}" alt="Image Preview"
                                        class="img-thumbnail preview-image">
                                    <p class="text-muted mt-2">Image Preview</p>
                                </div>
                            @else
                                <p class="text-warning mt-2">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>Image preview not available
                                </p>
                            @endif
                        @endif
                    </div>
                @endif

                <!-- Recipient Selection -->
                <div class="form-group mb-4">

                    <!-- Recipient Selection -->
                    <div class="form-group mb-4">
                        <label for="sendTo" class="font-weight-bold text-primary d-block">
                            <i class="fas fa-users mr-2"></i>Send To
                        </label>
                        <select id="sendTo" class="form-control border-primary" wire:model="sendTo"
                            wire:change="updateRecipientFilter">
                            <option value="all">All Users</option>
                            <option value="inactive_7_days">Users inactive for 7 days</option>
                            <option value="inactive_1_month">Users inactive for 1 month</option>
                            <option value="inactive_3_months">Users inactive for 3 months</option>
                            <option value="inactive_6_months">Users inactive for 6 months</option>
                        </select>
                        @error('sendTo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    

                    @error('sendTo')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="form-group text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-paper-plane mr-2"></i> Send Notification
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification History Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-history mr-2"></i>Notification History
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Image</th>
                            <th>Recipients</th>
                            <th>Date Sent</th>
                            <th>Actions</th> <!-- New column -->
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notifications as $notification)
                            <tr wire:key="notification-{{ $notification->id }}">
                                <td>{{ $notification->id }}</td>
                                <td class="font-weight-bold">{{ $notification->notification_title }}</td>
                                <td>{{ Str::limit($notification->notification_message, 50) }}</td>
                                <td>
                                    @if ($notification->image)
                                        <img src="{{ asset($notification->image) }}" alt="Notification Image"
                                            class="img-thumbnail notification-image" style="max-width: 80px;"
                                            onerror="this.onerror=null; this.style.display='none'">
                                    @else
                                        <span class="text-muted">No image</span>
                                    @endif
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $notification->receiver_id ? 'badge-info' : 'badge-success' }}">
                                        {{ $notification->receiver_id ? 'Specific Users' : 'All Users' }}
                                    </span>
                                </td>
                                <td>{{ $notification->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <button
                                        onclick="confirm('Are you sure?') ? @this.call('deleteNotification', {{ $notification->id }}) : false"
                                        class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                        <h4 class="text-muted">No Notifications Found</h4>
                                        <p class="text-muted">You haven't sent any notifications yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($notifications->count() > 0)
                <div class="d-flex justify-content-center mt-3">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
