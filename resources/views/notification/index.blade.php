@extends('layout')

@section('dashboard-content')
<!-- Debugging: Check if $users is available -->
@if(isset($users))
    <p>Users variable is available.</p>
@else
    <p>Users variable is NOT available.</p>
@endif

<h1>Push Notification</h1>

<!-- Notification Form -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Compose Notification</h5>
        <form action="{{ route('add-notification') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="notificationTitle">Title</label>
                <input type="text" class="form-control" id="notificationTitle" name="notification_title" placeholder="Enter notification title">
            </div>
            <div class="form-group">
                <label for="notificationMessage">Message</label>
                <textarea class="form-control" id="notificationMessage" name="notification_message" rows="3" placeholder="Enter notification message"></textarea>
            </div>
            <div class="form-group">
                <label>Include Image</label>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="includeImageToggle" name="include_image">
                    <label class="custom-control-label" for="includeImageToggle">Add an image to the notification</label>
                </div>
            </div>
            <div class="form-group" id="imageUploadGroup" style="display: none;">
                <label for="notificationImage">Upload Image</label>
                <input type="file" class="form-control-file" id="notificationImage" name="image">
            </div>
            <div class="form-group">
                <label>Send To</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="sendTo" id="sendToAll" value="all" checked>
                    <label class="form-check-label" for="sendToAll">
                        All Users
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="sendTo" id="sendToSpecific" value="specific">
                    <label class="form-check-label" for="sendToSpecific">
                        Specific Users
                    </label>
                </div>
            </div>
            <div class="form-group" id="specificUsersGroup" style="display: none;">
                <input type="hidden" id="notifyIndexRoute" value="{{ route('notifications.index') }}">
                                  <div class="search-container">
                                    <form>
                                        <input type="text" 
                                               id="notifySearchInput" 
                                               class="form-control search-box" 
                                               placeholder="Search user..." 
                                               data-route="{{ route('notifications.index') }}" 
                                               data-table="#notify-table" 
                                               data-pagination=".notify-pagination">
                                        <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                                    </form>
                                  </div>
                <div id="userList">
                    @foreach($users->slice(0, 4) as $user)
                        <div class="form-check">
                            <input class="form-check-input user-checkbox" type="checkbox" value="{{ $user->id }}" id="user{{ $user->id }}">
                            <label class="form-check-label" for="user{{ $user->id }}">{{ $user->username }}</label>
                        </div>
                    @endforeach
                </div>
                
                <!-- Hidden Users -->
                <div id="hiddenUsers" style="display: none;">
                    @foreach($users->slice(4) as $user)
                        <div class="form-check">
                            <input class="form-check-input user-checkbox" type="checkbox" value="{{ $user->id }}" id="user{{ $user->id }}">
                            <label class="form-check-label" for="user{{ $user->id }}">{{ $user->username }}</label>
                        </div>
                    @endforeach
                </div>
                <div id="selectedUsers" class="mt-3">
                    <strong>Selected Users:</strong>
                    <div id="selectedUsersList"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Send Notification</button>
        </form>
    </div>
</div>

<!-- Table to Display Notification History -->
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Notification History</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Image</th>
                        <th>Sent To</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notifications as $notification)
                        <tr>
                            <td>{{ $notification->id }}</td>
                            <td>{{ $notification->notification_title }}</td>
                            <td>{{ $notification->notification_message }}</td>
                            <td>
                                @if($notification->image)
                                    <img src="{{ $notification->image }}" alt="Notification Image" width="50">
                                @else
                                    No Image
                                @endif
                            </td>
                            <td>{{ $notification->receiver_id ? 'Specific Users' : 'All Users' }}</td>
                            <td>{{ $notification->created_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Handle form submission for notification
    $('form').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const selectedUsers = $('#userList .user-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        formData.append('specific_users', selectedUsers);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                alert('Notification sent successfully!');
                location.reload();
            },
            error: function(response) {
                alert('Failed to send notification.');
            }
        });
    });
</script>
@stop