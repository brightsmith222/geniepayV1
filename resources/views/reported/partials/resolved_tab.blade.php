<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <!-- Search Box -->
    <div class="position-relative" style="max-width: 320px; width: 100%;">
        <input type="text" 
                id="resolvedSearchInput"
                class="form-control pe-5 py-2 rounded-pill border border-secondary-subtle shadow-sm"
                placeholder="Search resolved transactions..."
                value="{{ $resolvedSearchTerm ?? '' }}">
        <span class="search-icon"><i class="material-icons-outlined">search</i></span>
    </div>
    
    <!-- Sorting Dropdown -->
    <div class="d-flex align-items-center gap-2">
        <label for="resolvedSort" class="text-muted fw-semibold mb-0">Sort:</label>
        <select id="resolvedSort" class="form-select rounded-pill shadow-sm border-secondary-subtle"
                style="min-width: 240px;">
            <option value="created_at_desc" {{ $resolvedSortColumn == 'updated_at' && $resolvedSortDirection == 'desc' ? 'selected' : '' }}>🕒 Newest First</option>
            <option value="created_at_asc" {{ $resolvedSortColumn == 'updated_at' && $resolvedSortDirection == 'asc' ? 'selected' : '' }}>📅 Oldest First</option>
            <option value="amount_desc" {{ $resolvedSortColumn == 'amount' && $resolvedSortDirection == 'desc' ? 'selected' : '' }}>💰 Amount (High to Low)</option>
            <option value="amount_asc" {{ $resolvedSortColumn == 'amount' && $resolvedSortDirection == 'asc' ? 'selected' : '' }}>💸 Amount (Low to High)</option>
        </select>
    </div>
</div>

<!-- Transactions Table Container -->
<div id="resolved-table-container" class="table-responsive">
    @include('reported.partials.resolved_table', ['transactions' => $resolvedTransactions])
</div>


<!-- Pagination Container -->
<div id="resolved-pagination-container" class="d-flex justify-content-center mt-4">
    {{ $resolvedTransactions->links('vendor.pagination.bootstrap-4') }}
</div>