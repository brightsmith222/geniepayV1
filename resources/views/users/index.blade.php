@extends('layout')

@section('dashboard-content')
<div>
    <h1>Users</h1>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Users Management</h5>
                    
                   <!-- Modern Filter + Search Header -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

                        <!-- Search Field -->
                        <div class="position-relative" style="max-width: 320px; width: 100%;">
                            <input type="text"
                                id="userSearchInput"
                                class="form-control pe-5 py-2 rounded-pill border-secondary-subtle shadow-sm"
                                placeholder="Search users..."
                                value="{{ $searchTerm ?? '' }}">
                            <span class="position-absolute top-50 end-0 translate-middle-y pe-3 text-muted">
                                <i class="material-icons-outlined" style="font-size: 18px; line-height: 1;">search</i>
                            </span>
                        </div>
                        

                        <!-- Sort Dropdown -->
                        <div class="d-flex align-items-center gap-2">
                            <label for="userSort" class="text-muted fw-semibold mb-0">Sort:</label>
                            <select id="userSort"
                                    class="form-select rounded-pill shadow-sm border-secondary-subtle"
                                    style="min-width: 240px;">
                                <option value="created_at_desc" {{ $sortColumn == 'created_at' && $sortDirection == 'desc' ? 'selected' : '' }}>🕒 Newest First</option>
                                <option value="created_at_asc" {{ $sortColumn == 'created_at' && $sortDirection == 'asc' ? 'selected' : '' }}>📅 Oldest First</option>
                                <option value="username_asc" {{ $sortColumn == 'username' && $sortDirection == 'asc' ? 'selected' : '' }}>🔤 Username (A-Z)</option>
                                <option value="username_desc" {{ $sortColumn == 'username' && $sortDirection == 'desc' ? 'selected' : '' }}>🔡 Username (Z-A)</option>
                                <option value="wallet_balance_desc" {{ $sortColumn == 'wallet_balance' && $sortDirection == 'desc' ? 'selected' : '' }}>💰 Balance (High → Low)</option>
                                <option value="wallet_balance_asc" {{ $sortColumn == 'wallet_balance' && $sortDirection == 'asc' ? 'selected' : '' }}>💸 Balance (Low → High)</option>
                            </select>
                        </div>

                    </div>

                    
                    <!-- Users Table Container -->
                    <div id="users-table-container" class="table-responsive">
                     @include('users.partials.table', ['users' => $users])
                    </div>
                    
                    <!-- Pagination Container -->
                    <div id="users-pagination-container" class="d-flex justify-content-center mt-4">
                         {{$users->links('vendor.pagination.bootstrap-4')}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden fields for routes and CSRF -->
<input type="hidden" id="usersIndexRoute" value="{{ route('users.index') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

@endsection

@section('scripts')
<script src="{{ URL::to('assets/js/user.js')}}"></script>
@endsection