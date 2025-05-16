$(document).ready(function() {
     // Initialize CSRF token for all AJAX requests
     $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
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

    // Parse sort value into column and direction
    function parseSortValue(sortValue) {
        if (!sortValue) return { column: 'created_at', direction: 'desc' };
        
        const parts = sortValue.split('_');
        return {
            column: parts[0],
            direction: parts[1] || 'desc'
        };
    }

    // Load users with current parameters
    function loadUsers(params = {}) {
        const sortValue = $('#userSort').val();
        const { column, direction } = parseSortValue(sortValue);
        
        const requestParams = {
            search: $('#userSearchInput').val(),
            sort_column: column,
            sort_direction: direction,
            page: params.page || 1
        };

        // Show loading indicator
        $('#users-table-container').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p>Loading users...</p>
            </div>
        `);
        
        $.ajax({
            url: $('#usersIndexRoute').val(),
            type: 'GET',
            data: requestParams,
            success: function(response) {
                if (response.table) {
                    $('#users-table-container').html(response.table);
                    $('#users-pagination-container').html(response.pagination);
                }
            },
            error: function(xhr) {
                console.error('Error loading users:', xhr.responseText);
                $('#users-table-container').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Error loading users. Please try again.
                    </div>
                `);
            }
        });
    }    

    // Handle user actions with SweetAlert
    function handleUserAction(userId, action) {
        let url, method, title, text, successMessage;
        
        switch(action) {
            case 'suspend':
                url = `/suspend-user/${userId}`;
                method = 'POST';
                title = 'Suspend User';
                text = 'Are you sure you want to suspend this user?';
                successMessage = 'User suspended successfully';
                break;
            case 'unsuspend':
                url = `/unsuspend-user/${userId}`;
                method = 'POST';
                title = 'Unsuspend User';
                text = 'Are you sure you want to unsuspend this user?';
                successMessage = 'User unsuspended successfully';
                break;
            case 'block':
                url = `/block-user/${userId}`;
                method = 'POST';
                title = 'Block User';
                text = 'Are you sure you want to block this user?';
                successMessage = 'User blocked successfully';
                break;
            case 'unblock':
                url = `/unblock-user/${userId}`;
                method = 'POST';
                title = 'Unblock User';
                text = 'Are you sure you want to unblock this user?';
                successMessage = 'User unblocked successfully';
                break;
            case 'delete':
                url = `/delete-user/${userId}`;
                method = 'DELETE';
                title = 'Delete User';
                text = 'This will permanently delete the user. This action cannot be undone!';
                successMessage = 'User deleted successfully';
                break;
            default:
                console.error('Unknown action:', action);
                return;
        }

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: url,
                    type: method,
                    dataType: 'json'
                }).then(response => {
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(
                        `Request failed: ${error.responseJSON?.message || error.statusText}`
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value.success) {
                Swal.fire({
                    title: 'Success!',
                    text: successMessage,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    // Force a complete refresh of the table with current filters
                    loadUsers({
                        search: $('#userSearchInput').val(),
                        sort: $('#userSort').val()
                    });
                });
            }
        });
    }

   // Initialize event listeners
   function initEventListeners() {
    // Search input
    $('#userSearchInput').on('keyup', debounce(function() {
        loadUsers();
    }, 300));

    // Sort dropdown
    $('#userSort').on('change', function() {
        loadUsers();
    });

    // Action buttons
    $(document).on('click', '.action-btn', function(e) {
        e.preventDefault();
        const userId = $(this).data('id');
        const action = $(this).data('action');
        handleUserAction(userId, action);
    });

    // Pagination clicks
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        const page = $(this).attr('href').split('page=')[1];
        loadUsers({ page: page });
    });
}

// Initial load
initEventListeners();
loadUsers();

});
