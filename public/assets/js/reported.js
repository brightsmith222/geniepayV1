$(document).ready(function() {
    // Initialize CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    

    // Currency formatter
    const formatter = new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2
    });

    // Debounce function for search
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    $(function () {
        // Restore active tab on page load
        const savedTab = localStorage.getItem('activeTransactionTab');
        if (savedTab && $('#transactionTabs a[href="' + savedTab + '"]').length) {
            $('#transactionTabs a[href="' + savedTab + '"]').tab('show');
        } else {
            $('#transactionTabs a:first').tab('show'); // fallback
        }
    
        // Load data based on restored tab
        const tabId = (savedTab || '#reported').substring(1);
        if (tabId === 'resolved') {
            loadResolvedTransactions();
        } else {
            loadReportedTransactions();
        }
    
        // Handle tab click
        $('#transactionTabs a').on('click', function (e) {
            e.preventDefault();
            const tabId = $(this).attr('href');
            localStorage.setItem('activeTransactionTab', tabId);
            $(this).tab('show');
    
            const tabContentId = tabId.substring(1);
            if (tabContentId === 'resolved') {
                loadResolvedTransactions();
            } else {
                loadReportedTransactions();
            }
        });
    });
    

    // Load functions for both tabs
    function loadReportedTransactions(params = {}) {
        loadTransactions('reported', params);
    }

    function loadResolvedTransactions(params = {}) {
        loadTransactions('resolved', params);
    }

    function loadTransactions(type, params = {}) {
        const container = $(`#${type}-table-container`);
    
        const sortValue = $(`#${type}Sort`).val();
        let sortColumn = type === 'resolved' ? 'updated_at' : 'created_at';
        let sortDirection = 'desc';
        
        if (sortValue) {
            const parts = sortValue.split('_');
            if (parts.length === 2 && ['asc', 'desc'].includes(parts[1])) {
                sortColumn = parts[0];
                sortDirection = parts[1];
            }
        }
        
        // Get search input value and ensure it's a string
        const searchInput = $(`#${type}SearchInput`).val();
        const searchTerm = typeof searchInput === 'string' ? searchInput : '';
    
    
        const requestParams = {
            page: params.page || 1,
            sort_column: sortColumn,
            sort_direction: sortDirection
        };
        
        if (searchTerm.trim() !== '') {
            requestParams[type === 'resolved' ? 'resolved_search' : 'search'] = searchTerm;
        }
    
        container.html(loadingSpinner()); // Show loading spinner

        $.ajax({
            url: $(`#${type}IndexRoute`).val(),
            type: 'GET',
            data: requestParams,
            success: function(response) {
                const section = response[type]; 
                container.html(section.table);
                $(`#${type}-pagination-container`).html(section.pagination);
            },
            error: handleAjaxError.bind(null, type)
        });
    }
    

    function loadingSpinner() {
        return `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p>Loading transactions...</p>
            </div>
        `;
    }

    function handleAjaxError(type, xhr) {
        console.error(`Error loading ${type} transactions:`, xhr.responseText);
        $(`#${type}-table-container`).html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                Error loading transactions. Please try again.
            </div>
        `);
    }

    // Initialize event listeners
    function initEventListeners() {
        // Search inputs
        $('#reportedSearchInput, #resolvedSearchInput').on('keyup', debounce(function() {
            const type = this.id.replace('SearchInput', '');
            loadTransactions(type);
        }, 300));

        // Sort dropdowns
        $('#reportedSort, #resolvedSort').on('change', function() {
            const type = this.id.replace('Sort', '');
            loadTransactions(type);
        });

        // Pagination
        $(document).on('click', '#reported-pagination-container a, #resolved-pagination-container a', function(e) {
            e.preventDefault();
            const page = $(this).attr('href').split('page=')[1];
            const container = $(this).closest('[id$="-pagination-container"]');
            const type = container.attr('id').replace('-pagination-container', '');
            loadTransactions(type, { page: page });
        });
    }


    // Initial load
    initEventListeners();
    loadReportedTransactions();  // Load reported transactions initially
});
