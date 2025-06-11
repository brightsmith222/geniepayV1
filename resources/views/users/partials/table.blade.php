<table class="table">
    <thead>
        <tr>
            <th>#ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Wallet Balance</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
        <tr data-user-id="{{ $user->id }}">
            <td>{{ $user->id }}</td>
            <td>{{ $user->username }}</td>
            <td>{{ $user->full_name }}</td>
            <td>{{ $user->email }}</td>
            <td>₦{{ number_format($user->wallet_balance ?? 0, 2) }}</td>
            <td>
                <span class="status {{ $user->status === 'active' ? 'completed' : 'cancel' }}">
                    {{ $user->status }}
                </span>
            </td>
            <td>
                @if (!$user->isAdmin())
                <div class="dropdown">
                    <span class="action-button" data-toggle="dropdown">&#8942;</span>
                    <section class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('edit-user', $user->id) }}">
                            <i class="fas fa-edit mr-2"></i> Edit
                        </a>
                        
                        @if ($user->status === 'active')
                            <button class="dropdown-item action-btn suspend-button" 
                                    data-id="{{ $user->id }}" 
                                    data-action="suspend">
                                <i class="fas fa-pause mr-2"></i> Suspend
                            </button>
                            <button class="dropdown-item action-btn text-warning" 
                                    data-id="{{ $user->id }}" 
                                    data-action="block">
                                <i class="fas fa-ban mr-2"></i> Block
                            </button>
                        @elseif ($user->status === 'suspended')
                            <button class="dropdown-item action-btn suspend-button" 
                                    data-id="{{ $user->id }}" 
                                    data-action="unsuspend">
                                <i class="fas fa-play mr-2"></i> Unsuspend
                            </button>
                        @elseif ($user->status === 'blocked')
                            <button class="dropdown-item action-btn" 
                                    data-id="{{ $user->id }}" 
                                    data-action="unblock">
                                <i class="fas fa-lock-open mr-2"></i> Unblock
                            </button>
                        @endif
                        
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item action-btn text-danger" 
                                data-id="{{ $user->id }}" 
                                data-action="delete">
                            <i class="fas fa-trash-alt mr-2"></i> Delete
                        </button>
                    </section>
                </div>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center">No users found</td>
        </tr>
        @endforelse
    </tbody>
</table>