<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <!-- Search Box -->
    <div class="position-relative" style="max-width: 320px; width: 100%;">
        <input type="text" 
                id="reportedSearchInput"
                class="form-control pe-5 py-2 rounded-pill border border-secondary-subtle shadow-sm"
                placeholder="Search reported transactions..."
                value="{{ $searchTerm ?? '' }}">
        <span class="search-icon"><i class="material-icons-outlined">search</i></span>
    </div>
    
    <!-- Sorting Dropdown -->
    <div class="d-flex align-items-center gap-2">
        <label for="reportedSort" class="text-muted fw-semibold mb-0">Sort:</label>
        <select id="reportedSort" class="form-select rounded-pill shadow-sm border-secondary-subtle"
        style="min-width: 240px;">
            <option value="created_at_desc" {{ ($sortColumn ?? 'created_at') == 'created_at' && ($sortDirection ?? 'desc') == 'desc' ? 'selected' : '' }}>🕒 Newest First</option>
            <option value="created_at_asc" {{ ($sortColumn ?? 'created_at') == 'created_at' && ($sortDirection ?? 'desc') == 'asc' ? 'selected' : '' }}>📅 Oldest First</option>
            <option value="amount_desc" {{ ($sortColumn ?? 'created_at') == 'amount' && ($sortDirection ?? 'desc') == 'desc' ? 'selected' : '' }}>💰 Amount (High to Low)</option>
            <option value="amount_asc" {{ ($sortColumn ?? 'created_at') == 'amount' && ($sortDirection ?? 'desc') == 'asc' ? 'selected' : '' }}>💸 Amount (Low to High)</option>
</select>
    </div>
</div>

<!-- Transactions Table Container -->
<div id="reported-table-container" class="table-responsive">
    @include('reported.partials.table', ['transactions' => $reportedTransactions])
</div>

<!-- Pagination Container -->
<div id="reported-pagination-container" class="d-flex justify-content-center mt-4">
    {{ $reportedTransactions->links('vendor.pagination.bootstrap-4') }}
</div>