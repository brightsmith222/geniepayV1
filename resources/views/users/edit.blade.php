@extends('layout')

@section('dashboard-content')

<h3>Edit User</h3>
        <form>
            <!-- Personal Info Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Personal Info</h5>
                    <div class="form-group row">
                        <label for="userName" class="col-sm-3 col-form-label">Username</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="userName" value="Johnnydoe">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="fullName" class="col-sm-3 col-form-label">Full Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="fullName" value="John Doe">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="email" class="col-sm-3 col-form-label">Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="email" value="johnnydoe@gmail.com">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="phoneNumber" class="col-sm-3 col-form-label">Phone Number</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="phoneNumber" value="07030323456">
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
                            <input type="text" class="form-control" id="accountBalance" value="#10,000" disabled>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="gender" class="col-sm-3 col-form-label">Gender</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="gender">
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="pin" class="col-sm-3 col-form-label">User Pin</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="pin" value="1122">
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label for="userRole" class="col-sm-3 col-form-label">User Role</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="userRole">
                                <option>User</option>
                                <option>Admin</option>
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