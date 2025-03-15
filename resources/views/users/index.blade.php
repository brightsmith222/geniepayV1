@extends('layout')

@section('dashboard-content')

<h1>Users</h1>
  <div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Task Overview</h5>
                <div class="d-flex justify-content-end mb-3">
                    <input type="hidden" id="usersIndexRoute" value="{{ route('users.index') }}">
                    <div class="search-container">
                        <form>
                            <input type="text" 
                                   id="userSearchInput" 
                                   class="form-control search-box" 
                                   placeholder="Search users..." 
                                   data-route="{{ route('users.index') }}" 
                                   data-table="#users-table" 
                                   data-pagination=".users-pagination">
                            <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                        </form>
                    </div>
                </div>
                <!-- Sorting Dropdown -->
                <div class="mb-3">
                    <label for="userSort">Sort By:</label>
                    <form id="userSortForm" method="POST" action="{{ route('users.index') }}">
                        @csrf <!-- Add CSRF token for POST requests -->
                        <select id="userSort" name="sort" onchange="handleUserSort(this.value);">
                            <option value="created_at_desc" {{ request('sort') === 'created_at_desc' ? 'selected' : '' }}>
                                Newest First
                            </option>
                            <option value="created_at_asc" {{ request('sort') === 'created_at_asc' ? 'selected' : '' }}>
                                Oldest First
                            </option>
                            <option value="wallet_balance_desc" {{ request('sort') === 'wallet_balance_desc' ? 'selected' : '' }}>
                                Wallet Balance (High to Low)
                            </option>
                            <option value="wallet_balance_asc" {{ request('sort') === 'wallet_balance_asc' ? 'selected' : '' }}>
                                Wallet Balance (Low to High)
                            </option>
                            <option value="status_asc" {{ request('sort') === 'status_asc' ? 'selected' : '' }}>
                                Status (A-Z)
                            </option>
                            <option value="status_desc" {{ request('sort') === 'status_desc' ? 'selected' : '' }}>
                                Status (Z-A)
                            </option>
                        </select>
                    </form>
                </div>
                <div class="table-responsive">
                   
                    <table class="table" id="users-table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Username</th>
                                <th>Full Names</th>
                                <th>Email</th>
                                <th>Current Bal</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->username }}</td>
                                <td>{{ $user->full_name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>₦{{ number_format($user->wallet_balance ?? 0, 2) }}</td>
                                <td>
                                    <span class="status {{ $user->status === 'active' ? 'completed' : 'cancel' }}">{{ $user->status }}</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <span class="action-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8942;</span>
                                        <section class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('edit-user', $user->id) }}">Edit</a>
                                            <a class="dropdown-item" href="#" onclick="confirmSuspend({{ $user->id }})">Suspend</a>
                                            <a class="dropdown-item" href="#" onclick="confirmBlock({{ $user->id }})">Block</a>
                                            <a class="dropdown-item" href="#" onclick="confirmDelete({{ $user->id }})">Delete</a>
                                        </section>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Confirm Suspend Action
    function confirmSuspend(userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to suspend this user!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, suspend!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/suspend-user/${userId}`;
            }
        });
    }

    // Confirm Block Action
    function confirmBlock(userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to block this user!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, block!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/block-user/${userId}`;
            }
        });
    }

    // Confirm Delete Action
    function confirmDelete(userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete this user! This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/delete-user/${userId}`;
            }
        });
    }


    
</script>

@stop