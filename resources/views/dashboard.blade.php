@extends('layout')

@section('dashboard-content')
<div>

<div class="card mb-4">
  <div class="card-body">
  <!-- Reported transaction area -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <!-- Report Stats -->
    <div class="report-stat flex-grow-1">
      <span class="material-icons-outlined me-2">report_problem</span>
      <div class="ticker-container flex-grow-1 overflow-hidden" style="height: 24px;">
        <div class="ticker-track" id="reportTicker">
          <div class="ticker-item text-truncate">
            ⚠️ Loading reported transactions...
          </div>
        </div>
      </div>
      <span class="badge reported-badge ms-2 px-3 py-1" id="reportedCountBadge">
        ??
    </span>
    </div> 
  </div>
  
    <!-- Header -->
    <div class="header mb-4 d-flex">
      <div>
        <h4>{{ $greeting }}</h4>
        <span>Here is what's happening with your projects today:</span>
      </div>
      <div>
        <div class="time-display">
          <div class="time-block">
            <div class="time-number" id="hours">00</div>
          </div><span>:</span>
          <div class="time-block">
            <div class="time-number" id="minutes">00</div>
          </div>
          <div class="time-block">
            <div class="time-number" id="ampm">AM</div>
          </div>
        </div>
        <div id="current-date" class="date-display"></div>
      </div>
    </div>

  

   <!-- Available Balance for vtpass, artx and glad -->
  <div class="row">
    <!-- Wallet Balance -->
        <div class="col-sm-12 col-md-6 col-lg-4">
    <div class="wallet-wrapper">
  <div class="wallet-balance">
    <span class="material-icons-outlined">account_balance_wallet</span>
    <span id="wallet-balance" class="wallet-balance-value">Loading...</span>
    <span class="toggle-balance" data-target="#wallet-balance" style="cursor:pointer;">
      <i class="fa fa-eye"></i>
    </span>
  </div>
  <button id="refreshBalance" class="btn btn-sm btn-light refresh-balance">⟳ Vtpass</button>
</div>
    </div>
    <!-- Wallet Balance for artx -->

      <div class="col-sm-12 col-md-6 col-lg-4">
    <div class="wallet-wrapper">
  <div class="wallet-balance">
    <span class="material-icons-outlined">account_balance_wallet</span>
    <span id="wallet-balance-artx" class="wallet-balance-value">Loading...</span>
    <span class="toggle-balance" data-target="#wallet-balance-artx" style="cursor:pointer;">
      <i class="fa fa-eye"></i>
    </span>
  </div>
  <button id="refreshBalance" class="btn btn-sm btn-light refresh-balance">⟳ Artx</button>
</div>
  </div>
    <!-- Wallet Balance for glad -->
    <div class="col-sm-12 col-md-6 col-lg-4">
    <div class="wallet-wrapper">
  <div class="wallet-balance">
    <span class="material-icons-outlined">account_balance_wallet</span>
    <span id="wallet-balance-glad" class="wallet-balance-value">Loading...</span>
    <span class="toggle-balance" data-target="#wallet-balance-glad" style="cursor:pointer;">
      <i class="fa fa-eye"></i>
    </span>
  </div>
  <button id="refreshBalance" class="btn btn-sm btn-light refresh-balance">⟳ Glad</button>
</div>
    </div>
  </div>
</div>
</div>
  <!-- Sales Statistics -->
  <div class="card mb-4">
    <div class="card-body">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h5 class="card-title mb-1">Sales Statistics</h5>
      <p class="card-subtitle text-muted">Track and manage income effortlessly</p>
    </div>
    <div class="mb-3">
      <label for="sort">Filter By:</label>
      <!-- Sales Filter Dropdown -->
<select class="revenue-date-range" id="revenue-sort" onchange="handleFilterChange(this.value, 'sales')">
    <option value="all_time" {{ $filter === 'all_time' ? 'selected' : '' }}>All Time</option>
    <option value="today" {{ $filter === 'today' ? 'selected' : '' }}>Today</option>
    <option value="yesterday" {{ $filter === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
    <option value="this_month" {{ $filter === 'this_month' ? 'selected' : '' }}>This Month</option>
    <option value="last_month" {{ $filter === 'last_month' ? 'selected' : '' }}>Last Month</option>
    <option value="this_year" {{ $filter === 'this_year' ? 'selected' : '' }}>This Year</option>
    <option value="last_year" {{ $filter === 'last_year' ? 'selected' : '' }}>Last Year</option>
    <option value="custom">Custom</option>
</select>

<!-- Date Range Picker for Sales -->
<div id="custom-date-range-sales" style="display: none; margin-top: 10px;">
    <input type="text" id="date-range-sales" placeholder="Select Date Range">
    <button onclick="applyCustomFilter('sales')">Apply</button>
</div>
    </div>
  </div>
  <div class="row">
    <!-- First Column -->
    <div class="col-md-12">
      <div class="row">
        <!-- Top Row -->
        <div class="col-md-4">
          <div class="card total-sale">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Sales</h6>
                <h3 class="card-text mb-2" id="total-sales">₦{{ number_format($totalSales, 2) }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics">
                    @php
                        $percentageChange = $previousTotalSales != 0 
                            ? (($totalSales - $previousTotalSales) / $previousTotalSales) * 100 
                            : 0;
                        echo number_format($percentageChange, 2) . '%';
                    @endphp
                  </span> 
                  vs ₦{{ number_format($previousTotalSales, 2) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card data-sale">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Data Sales</h6>
                <h3 class="card-text mb-2" id="total-data-sales">₦{{ number_format($totalDataSales, 2) }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics-data-sales">
                    @php
                        $percentageChangeData = $previousTotalDataSales != 0 
                            ? (($totalDataSales - $previousTotalDataSales) / $previousTotalDataSales) * 100 
                            : 0;
                        echo number_format($percentageChangeData, 2) . '%';
                    @endphp
                  </span> 
                  vs ₦{{ number_format($previousTotalDataSales, 2) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card airtime-sale">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Airtime Sales</h6>
                <h3 class="card-text mb-2" id="total-airtime-sales">₦{{ number_format($totalAirtimeSales, 2) }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics-airtime-sales">
                    @php
                        $percentageChangeAirtime = $previousTotalAirtimeSales != 0 
                            ? (($totalAirtimeSales - $previousTotalAirtimeSales) / $previousTotalAirtimeSales) * 100 
                            : 0;
                        echo number_format($percentageChangeAirtime, 2) . '%';
                    @endphp
                  </span> 
                  vs ₦{{ number_format($previousTotalAirtimeSales, 2) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              
            </div>
          </div>


        </div>
        <!-- Bottom Row -->
        <div class="col-md-4">
          <div class="card electricity-sale">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Electricity Sales</h6>
                <h3 class="card-text mb-2" id="total-electricity-sales">₦{{ number_format($totalElectricitySales, 2) }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics-electricity-sales">
                    @php
                        $percentageChangeElectricity = $previousTotalElectricitySales != 0 
                            ? (($totalElectricitySales - $previousTotalElectricitySales) / $previousTotalElectricitySales) * 100 
                            : 0;
                        echo number_format($percentageChangeElectricity, 2) . '%';
                    @endphp
                  </span> 
                  vs ₦{{ number_format($previousTotalElectricitySales, 2) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              
            </div>
          </div>


        </div>
        <div class="col-md-4">
          <div class="card cable-sale">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Cable Sales</h6>
                <h3 class="card-text mb-2" id="total-cable-sales">₦{{ number_format($totalCableSales, 2) }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics-cable-sales">
                    @php
                        $percentageChangeCable = $previousTotalCableSales != 0 
                            ? (($totalCableSales - $previousTotalCableSales) / $previousTotalCableSales) * 100 
                            : 0;
                        echo number_format($percentageChangeCable, 2) . '%';
                    @endphp
                  </span> 
                  vs ₦{{ number_format($previousTotalCableSales, 2) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card exam-sale">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Exam Sales</h6>
                <h3 class="card-text mb-2" id="total-exam-sales">₦{{ number_format($totalExamSales, 2) }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics-exam-sales">
                    @php
                        $percentageChangeExam = $previousTotalExamSales != 0 
                            ? (($totalExamSales - $previousTotalExamSales) / $previousTotalExamSales) * 100 
                            : 0;
                        echo number_format($percentageChangeExam, 2) . '%';
                    @endphp
                  </span> 
                  vs ₦{{ number_format($previousTotalExamSales, 2) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
  <!-- User Statistics -->
  <div class="card mb-4">
    <div class="card-body">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h5 class="card-title mb-1">Users Overview</h5>
      <p class="card-subtitle text-muted">Track and manage users effortlessly</p>
    </div>
    <div class="mb-3">
      <label for="sort">Filter By:</label>
      <!-- Users Filter Dropdown -->
<select class="users-date-range" id="user-sort" onchange="handleFilterChange(this.value, 'users')">
    <option value="all_time" {{ $filter === 'all_time' ? 'selected' : '' }}>All Time</option>
    <option value="today" {{ $filter === 'today' ? 'selected' : '' }}>Today</option>
    <option value="yesterday" {{ $filter === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
    <option value="this_month" {{ $filter === 'this_month' ? 'selected' : '' }}>This Month</option>
    <option value="last_month" {{ $filter === 'last_month' ? 'selected' : '' }}>Last Month</option>
    <option value="this_year" {{ $filter === 'this_year' ? 'selected' : '' }}>This Year</option>
    <option value="last_year" {{ $filter === 'last_year' ? 'selected' : '' }}>Last Year</option>
    <option value="custom">Custom</option>
</select>

<!-- Date Range Picker for Users -->
<div id="custom-date-range-users" style="display: none; margin-top: 10px;">
    <input type="text" id="date-range-users" placeholder="Select Date Range">
    <button onclick="applyCustomFilter('users')">Apply</button>
</div>
    </div>
  </div>
  <div class="row">
    <!-- First Column -->
    <div class="col-md-8">
      <div class="row">
        <!-- Top Row -->
        <div class="col-md-6">
          <div class="card all-users">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Users</h6>
                <h3 class="card-text mb-2" id="total-users">{{ $totalUsers }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics">
                    @php
                        $percentageChangeTotal = $previousTotalUsers != 0 
                            ? (($totalUsers - $previousTotalUsers) / $previousTotalUsers) * 100 
                            : 0;
                        echo number_format($percentageChangeTotal, 2) . '%';
                    @endphp
                </span> 
                vs {{ number_format($previousTotalUsers) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
              </div>
              
            </div>
          </div>

        </div>
        <div class="col-md-6">
          <div class="card active-users">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Active Users</h6>
                <h3 class="card-text mb-2" id="active-users">{{ $activeUsers }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics">
                      @php
                          $percentageChangeUsers = $previousActiveUsers != 0 
                              ? (($activeUsers - $previousActiveUsers) / $previousActiveUsers) * 100 
                              : 0;
                          echo number_format($percentageChangeUsers, 2) . '%';
                      @endphp
                  </span> 
                  vs {{ number_format($previousActiveUsers) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-check"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg></span>
              </div>
              
            </div>
          </div>

        </div>
        <!-- Bottom Row -->
        <div class="col-md-6">
          <div class="card suspend-users">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Suspend Users</h6>
                <h3 class="card-text mb-2" id="suspended-users">{{ $suspendedUsers }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics">
                      @php
                          $percentageChangeSuspended = $previousSuspendedUsers != 0 
                              ? (($suspendedUsers - $previousSuspendedUsers) / $previousSuspendedUsers) * 100 
                              : 0;
                          echo number_format($percentageChangeSuspended, 2) . '%';
                      @endphp
                  </span> 
                  vs {{ number_format($previousSuspendedUsers) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-x"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg></span>
              </div>
              
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card block-users">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                <h6 class="card-title">Total Blocked Users</h6>
                <h3 class="card-text mb-2" id="blocked-users">{{ $blockedUsers }}</h3>
                <p class="card-subtext">
                  <span class="negative" id="statistics">
                      @php
                          $percentageChangeBlocked = $previousBlockedUsers != 0 
                              ? (($blockedUsers - $previousBlockedUsers) / $previousBlockedUsers) * 100 
                              : 0;
                          echo number_format($percentageChangeBlocked, 2) . '%';
                      @endphp
                  </span> 
                  vs {{ number_format($previousBlockedUsers) }} (prev.)
                </p>
              </div>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-minus"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="23" y1="11" x2="17" y2="11"></line></svg></span>
              </div>
              
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Second Column -->
    <div class="col-md-4">
      <div class="card report-users h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
            <h6 class="card-title">Active Reported</h6>
            <h3 class="card-text mb-2" id="reported-transactions">{{ $reportedTransactionsCount }}</h3>
            <p class="card-subtext">
              <span class="negative" id="statistics">
                  @php
                      $percentageChangeTransactions = $previousReportedTransactionsCount != 0 
                          ? (($reportedTransactionsCount - $previousReportedTransactionsCount) / $previousReportedTransactionsCount) * 100 
                          : 0;
                      echo number_format($percentageChangeTransactions, 2) . '%';
                  @endphp
              </span> 
              vs {{ number_format($previousReportedTransactionsCount) }} (prev.)
            </p>
          </div>
            <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-triangle"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></span>
          </div>
          
        </div>
      </div>


    </div>
  </div>
</div>
</div>
</div>
@endsection


@section('scripts')
    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
@endsection