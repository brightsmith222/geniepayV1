@extends('layout')

@section('dashboard-content')

<h3>Edit User</h3>
<form action="{{ route('update-user', $user->id) }}" method="POST">
    @csrf
    @method('PUT')
    <!-- Personal Info Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Personal Info</h5>
            <div class="form-group row">
                <label for="userName" class="col-sm-3 col-form-label">Username</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="userName" name="username" value="{{ $user->username }}">
                </div>
            </div>
            <div class="form-group row">
                <label for="fullName" class="col-sm-3 col-form-label">Full Name</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="fullName" name="full_name" value="{{ $user->full_name }}">
                </div>
            </div>
            <div class="form-group row">
                <label for="email" class="col-sm-3 col-form-label">Email</label>
                <div class="col-sm-9">
                    <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}">
                </div>
            </div>
            <div class="form-group row">
                <label for="phoneNumber" class="col-sm-3 col-form-label">Phone Number</label>
                <div class="col-sm-9">
                    <input type="number" class="form-control" id="phoneNumber" name="phone_number" value="{{ $user->phone_number }}">
                </div>
            </div>
        </div>
    </div>

    <!-- Address Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Advance Details</h5>
            <div class="form-group row">
                <label for="accountBalance" class="col-sm-3 col-form-label">Account Balance</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="accountBalance" value="{{ $user->wallet_balance ?? '0' }}" disabled>
                </div>
            </div>
            <div class="form-group row">
                <label for="gender" class="col-sm-3 col-form-label">Gender</label>
                <div class="col-sm-9">
                    <select class="form-control" id="gender" name="gender">
                        <option value="Male" {{ $user->gender === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ $user->gender === 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ $user->gender === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label for="pin" class="col-sm-3 col-form-label">User Pin</label>
                <div class="col-sm-9">
                    <input type="number" class="form-control" id="pin" name="pin" value="{{ $user->pin }}">
                </div>
            </div>
            <div class="form-group row">
                <label for="userRole" class="col-sm-3 col-form-label">User Role</label>
                <div class="col-sm-9">
                    <select class="form-control" id="userRole" name="role">
                        <option value="0" {{ $user->role === '0' ? 'selected' : '' }}>User</option>
                        <option value="1" {{ $user->role === '1' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="form-group row">
        <div class="col-sm-9 offset-sm-3">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </div>
</form>

@stop