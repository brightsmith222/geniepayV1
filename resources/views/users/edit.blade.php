@extends('layout')

@section('dashboard-content')

<div id="userEditWrapper" data-user-id="{{ $user->id }}" data-transactions-url="{{ route('users.transactions', $user->id) }}">
    <div class="row">
        <!-- Left Column: Edit User -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">
                    <i class="fas fa-user-edit text-primary mr-2"></i>Edit User Profile
                </h3>
                <span class="badge badge-{{ $user->status === 'active' ? 'success' : ($user->status === 'suspended' ? 'warning' : 'danger') }}">
                    {{ ucfirst($user->status) }}
                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never logged in' }}
                </span>
            </div>

            <form id="userEditForm" action="{{ route('update-user', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <!-- Personal Info Section -->
                <div class="user-card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-id-card text-primary mr-2"></i>Personal Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label for="userName" class="col-sm-3 col-form-label font-weight-500">Username</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="userName" name="username" value="{{ $user->username }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="fullName" class="col-sm-3 col-form-label font-weight-500">Full Name</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-signature"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="fullName" name="full_name" value="{{ $user->full_name }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="email" class="col-sm-3 col-form-label font-weight-500">Email</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="phoneNumber" class="col-sm-3 col-form-label font-weight-500">Phone Number</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <input type="number" class="form-control" id="phoneNumber" name="phone_number" value="{{ $user->phone_number }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                <!-- Account Details Section -->
                <div class="user-card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-cog text-primary mr-2"></i>Account Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label for="accountBalance" class="col-sm-3 col-form-label font-weight-500">Account Balance</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-wallet"></i></span>
                                    </div>
                                    <input type="text" class="form-control font-weight-bold" id="accountBalance" value="₦{{ number_format($user->wallet_balance ?? 0, 2) }}" disabled>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="gender" class="col-sm-3 col-form-label font-weight-500">Gender</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                    </div>
                                    <select class="form-control selectpicker" id="gender" name="gender">
                                        <option value="Male" {{ $user->gender === 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ $user->gender === 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ $user->gender === 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="pin" class="col-sm-3 col-form-label font-weight-500">User Pin</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="number" class="form-control" id="pin" name="pin" value="{{ $user->pin }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="userRole" class="col-sm-3 col-form-label font-weight-500">User Role</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                    </div>
                                    <select class="form-control selectpicker" id="userRole" name="role">
                                        <option value="0" {{ $user->role === '0' ? 'selected' : '' }}>Regular User</option>
                                        <option value="1" {{ $user->role === '1' ? 'selected' : '' }}>Administrator</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                <!-- Save Button -->
                <div class="form-group row">
                    <div class="col-sm-9 offset-sm-3">
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-lg px-4 ml-2">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column: Last 5 Transactions -->
        <div class="col-lg-4">
            <div class="user-card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    
                </div>
                <div class="card-body p-0">
                    <div id="transactionsContainer">
                        @include('users.partials.last-transactions', ['transactions' => $transactions])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ URL::to('assets/js/edit-user.js')}}"></script>
@endsection
