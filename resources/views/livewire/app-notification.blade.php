<div>
    <h1>Push Notification</h1>

    <!-- Display success message -->
    @if (session()->has('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Notification Form -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Compose Notification</h5>
            <form wire:submit.prevent="submit">
                <div class="form-group">
                    <label for="notificationTitle">Title</label>
                    <input type="text" class="form-control" id="notificationTitle" wire:model="notification_title" placeholder="Enter notification title">
                    @error('notification_title') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                
                <div class="form-group">
                    <label for="notificationMessage">Message</label>
                    <textarea class="form-control" id="notificationMessage" wire:model="notification_message" rows="3" placeholder="Enter notification message"></textarea>
                    @error('notification_message') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Include Image</label>
                    <div class="custom-control custom-switch">
                        <input 
                            type="checkbox" 
                            class="custom-control-input" 
                            id="includeImageToggle" 
                            wire:model="include_image"
                            wire:click="$toggle('showImageUpload')">
                        <label class="custom-control-label" for="includeImageToggle">Add an image to the notification</label>
                    </div>
                </div>
                
                @if ($showImageUpload)
    <div class="form-group">
        <label for="notificationImage">Upload Image</label>
        <input type="file" class="form-control-file" id="notificationImage" wire:model="image">
        @error('image') <span class="text-danger">{{ $message }}</span> @enderror

        <!-- Live Preview -->
        @if ($image)
    @if (method_exists($image, 'temporaryUrl'))
        <div class="mt-2">
            <img src="{{ $image->temporaryUrl() }}" 
                 alt="Image Preview" 
                 width="150" 
                 class="img-thumbnail">
        </div>
    @else
        <p class="text-warning">Image preview is not available.</p>
    @endif
@endif

    </div>
@endif


                <!-- Send To Radio Buttons -->
                <div class="form-group">
                    <label>Send To</label>
                    <div class="form-check">
                        <input 
                            class="form-check-input" 
                            type="radio" 
                            name="sendTo" 
                            id="sendToAll" 
                            wire:model="sendTo" 
                            value="all" 
                            wire:click="$set('showSpecificUsers', false)">
                        <label class="form-check-label" for="sendToAll">
                            All Users
                        </label>
                    </div>
                    
                    @error('sendTo') <span class="text-danger">{{ $message }}</span> @enderror
                </div>


                <button type="submit" class="btn btn-primary" onclick="submitForm()">Send Notification</button>
            </form>
        </div>
    </div>

    <!-- Notification History Table -->
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
                        @foreach ($notifications as $notification)
                            <tr>
                                <td>{{ $notification->id }}</td>
                                <td>{{ $notification->notification_title }}</td>
                                <td>{{ $notification->notification_message }}</td>
                                <td>
                                    @if (!empty($notification->image))
                                        <img src="{{ asset('storage/'.$notification->image) }}" 
                                             alt="Notification Image" 
                                             width="50"
                                             onerror="this.style.display=''">
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
            
            <!-- Pagination Links -->
            <div class="d-flex justify-content-center">
                {{ $notifications->links() }}
            </div>
            
        </div>
    </div>
</div>

<script>
    let selectedUsers = [];

    function searchUsers() {
        let searchTerm = document.getElementById("userSearch").value;

        if (searchTerm.length < 1) {
            document.getElementById("userList").innerHTML = "";
            return;
        }

        fetch(`/search-users?query=${encodeURIComponent(searchTerm)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(users => {
                let userListHTML = "";

                if (users.length > 0) {
                    users.forEach(user => {
                        userListHTML += `
                            <div class="form-check">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input" 
                                    id="user${user.id}" 
                                    onclick="toggleUser(${user.id}, '${user.username}')">
                                <label class="form-check-label" for="user${user.id}">${user.username}</label>
                            </div>`;
                    });
                } else {
                    userListHTML = "<p class='text-muted'>No users found.</p>";
                }

                document.getElementById("userList").innerHTML = userListHTML;
            })
            .catch(error => console.error("Fetch error:", error));
    }

    function toggleUser(userId, username) {
    let index = selectedUsers.findIndex(user => user.id === userId);
    
    if (index !== -1) {
        selectedUsers.splice(index, 1); // Remove user if already selected
    } else {
        selectedUsers.push({ id: userId, username: username }); // Add user
    }

    // Update the hidden input field with the new selected users list
    document.getElementById("selectedUsersInput").value = JSON.stringify(selectedUsers);

    // Update the UI to show selected users
    updateSelectedUsersUI();
}


    function updateSelectedUsersUI() {
        let selectedUsersList = document.getElementById("selectedUsersList");
        selectedUsersList.innerHTML = "";

        selectedUsers.forEach(user => {
            selectedUsersList.innerHTML += `
                <span class="badge badge-primary mr-2">${user.username}</span>
            `;
        });
    }

    function submitForm() {
        document.getElementById("selectedUsersInput").value = JSON.stringify(selectedUsers);
    }
</script>