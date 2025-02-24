@extends('layout')

@section('dashboard-content')

<h1>Push Notification</h1>

        <!-- Notification Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Compose Notification</h5>
                <form>
                    <div class="form-group">
                        <label for="notificationTitle">Title</label>
                        <input type="text" class="form-control" id="notificationTitle" placeholder="Enter notification title">
                    </div>
                    <div class="form-group">
                        <label for="notificationMessage">Message</label>
                        <textarea class="form-control" id="notificationMessage" rows="3" placeholder="Enter notification message"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Include Image</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="includeImageToggle">
                            <label class="custom-control-label" for="includeImageToggle">Add an image to the notification</label>
                        </div>
                    </div>
                    <div class="form-group" id="imageUploadGroup" style="display: none;">
                        <label for="notificationImage">Upload Image</label>
                        <input type="file" class="form-control-file" id="notificationImage">
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
                        <label for="userSearch">Search Users</label>
                        <input type="text" class="form-control mb-3" id="userSearch" placeholder="Search users...">
                        <div id="userList">
                            <div class="form-check">
                                <input class="form-check-input user-checkbox" type="checkbox" value="1" id="user1">
                                <label class="form-check-label" for="user1">User 1</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input user-checkbox" type="checkbox" value="2" id="user2">
                                <label class="form-check-label" for="user2">User 2</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input user-checkbox" type="checkbox" value="3" id="user3">
                                <label class="form-check-label" for="user3">User 3</label>
                            </div>
                            <!-- Hidden users -->
                            <div class="form-check d-none">
                                <input class="form-check-input user-checkbox" type="checkbox" value="4" id="user4">
                                <label class="form-check-label" for="user4">User 4</label>
                            </div>
                            <div class="form-check d-none">
                                <input class="form-check-input user-checkbox" type="checkbox" value="5" id="user5">
                                <label class="form-check-label" for="user5">User 5</label>
                            </div>
                            <!-- Add more users as needed -->
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
                            <tr>
                                <td>1</td>
                                <td>New Feature Update</td>
                                <td>We have added a new feature to the dashboard.</td>
                                <td><img src="image1.jpg" alt="Notification Image" width="50"></td>
                                <td>All Users</td>
                                <td>2023-10-01</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Maintenance Notice</td>
                                <td>The system will be down for maintenance on 2023-10-05.</td>
                                <td>No Image</td>
                                <td>Specific Users</td>
                                <td>2023-10-02</td>
                            </tr>
                            <!-- Add more rows as needed -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

@stop