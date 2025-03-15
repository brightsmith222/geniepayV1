$(document).ready(function () {
    // Toggle sidebar on mobile
    $('#toggleSidebar').on('click', function () {
        $('#sidebar, .navbar, .content').toggleClass('active');
    });

    // Close sidebar when clicking outside (only if sidebar is open)
    $(document).on('click', function (event) {
        if (!$(event.target).closest('#sidebar, #toggleSidebar').length && $('#sidebar').hasClass('active')) {
            $('#sidebar, .navbar, .content').removeClass('active');
        }
    });

    // Prevent dropdown from closing when clicking inside
    $('#avatarDropdown').on('click', function (event) {
        event.stopPropagation();
    });

    
    // Initialize search functionality for each table
    function initializeSearch(tableId) {
        $(`#${tableId}`).closest('.card').find('.search-box').on('input', function () {
            const searchTerm = $(this).val().toLowerCase();
            $(`#${tableId} tbody tr`).each(function () {
                const rowText = $(this).text().toLowerCase();
                if (rowText.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }

    // Initialize search for each table
    initializeSearch('all-transaction-table');
    initializeSearch('data-transaction-table');
    initializeSearch('airtime-transaction-table');
    initializeSearch('cable-transaction-table');
    initializeSearch('electricity-transaction-table');
    initializeSearch('exam-transaction-table');
    initializeSearch('wallet-transaction-table');

/*
function performSearch(searchInput) {
    const route = $(searchInput).data('route'); // Get the route URL
    const searchTerm = $(searchInput).val(); // Get the search term
    const table = $(searchInput).data('table'); // Get the table selector
    const pagination = $(searchInput).data('pagination'); // Get the pagination selector

    $.ajax({
        url: route,
        method: 'GET',
        data: { search: searchTerm },
        success: function (response) {
            // Update the table and pagination
            $(table + ' tbody').html($(response).find(table + ' tbody').html());
            $(pagination).html($(response).find(pagination).html());
        },
        error: function (xhr, status, error) {
            console.error('AJAX Error:', error); // Log any errors
            console.log(xhr.responseText); // Log the server response
        }
    });
}

// Attach the search function to all search inputs
$('.search-box').on('input', function () {
    performSearch(this); // Call the generic search function
});  

*/

    //Avavar dropdown menu
    $('#avatarDropdown').on('click', function (event) {
        event.stopPropagation(); // Prevents closing when clicking on the avatar
        $('#profileMenu').toggle(); // Show/hide the menu
    });

    // Close the avatar menu when clicking outside
    $(document).on('click', function () {
        $('#profileMenu').hide();
    });

    // Prevent menu from closing when clicking inside
    $('#profileMenu').on('click', function (event) {
        event.stopPropagation();
    });

    //Currency formatter function
    let formatter = new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2
    });

    // Transaction Modal Functionality
    $(document).on('click', '.visibility', function () {
        let row = $(this).closest('tr');
        $('#modalInvoice').text(row.data('invoice'));
        $('#modalStatus').text(row.data('status'));
        $('#modalService').text(row.data('service'));
        $('#modalUsername').text(row.data('username'));
        $('#modalProvider').text(row.data('provider'));
        $('#modalPlan').text(row.data('plan'));
        $('#modalAmount').text(formatter.format(row.data("amount") || 0));
        $('#modalPhone').text(row.data('phone'));
        $('#modalCard').text(row.data('card'));
        $('#modalMeter').text(row.data('meter'));
        $('#modalQuantity').text(row.data('quantity'));
        $('#modalToken').text(row.data('token'));
        $('#modalEpin').text(row.data('epin'));
        $('#refundBtn').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#refundBtn').prop('disabled', true);
        } else {
            $('#refundBtn').prop('disabled', false);
        }
    });

    // Open Data Transaction Modal
    $(document).on("click", ".datavisibility", function () {
        let row = $(this).closest("tr");
        $("#modalInvoices").text(row.data("invoice"));
        $("#modalStatuss").text(row.data("status"));
        $("#modalServices").text(row.data("service"));
        $("#modalUsernames").text(row.data("username"));
        $("#modalAmounts").text(formatter.format(row.data("amount") || 0));
        $("#modalPhones").text(row.data("phone"));
        $('#refundBtns').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#refundBtns').prop('disabled', true);
        } else {
            $('#refundBtns').prop('disabled', false);
        }
        $("#datatransactionModal").modal("show");
    });

    // Airtime Modal Functionality
    $(document).on("click", ".airtimevisibility", function () {
        let row = $(this).closest("tr");
        $("#airtimemodalInvoice").text(row.data("invoice"));
        $("#airtimemodalStatus").text(row.data("status"));
        $("#airtimemodalService").text(row.data("service"));
        $("#airtimemodalUsername").text(row.data("username"));
        $("#airtimemodalAmount").text(formatter.format(row.data("amount") || 0));
        $("#airtimemodalPhone").text(row.data("phone"));
        $('#airtimerefundBtn').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#airtimerefundBtn').prop('disabled', true);
        } else {
            $('#airtimerefundBtn').prop('disabled', false);
        }
        $("#airtimetransactionModal").modal("show");
    });

    // Cable Modal Functionality
    $(document).on("click", ".cablevisibility", function () {
        let row = $(this).closest("tr");
        $("#cablemodalInvoice").text(row.data("invoice"));
        $("#cablemodalStatus").text(row.data("status"));
        $("#cablemodalService").text(row.data("service"));
        $("#cablemodalUsername").text(row.data("username"));
        $("#cablemodalAmount").text(formatter.format(row.data("amount") || 0));
        $("#cablemodalPhone").text(row.data("phone"));
        $('#cablerefundBtn').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#cablerefundBtn').prop('disabled', true);
        } else {
            $('#cablerefundBtn').prop('disabled', false);
        }
        $("#cabletransactionModal").modal("show");
    });

    // Electricity Modal Functionality
    $(document).on("click", ".electricityvisibility", function () {
        let row = $(this).closest("tr");
        $("#electricitymodalInvoice").text(row.data("invoice"));
        $("#electricitymodalStatus").text(row.data("status"));
        $("#electricitymodalService").text(row.data("service"));
        $("#electricitymodalUsername").text(row.data("username"));
        $("#electricitymodalAmount").text(formatter.format(row.data("amount") || 0));
        $("#electricitymodalPhone").text(row.data("phone"));
        $('#electricityrefundBtn').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#electricityrefundBtn').prop('disabled', true);
        } else {
            $('#electricityrefundBtn').prop('disabled', false);
        }
        $("#electricitytransactionModal").modal("show");
    });

    // Exam Modal Functionality
    $(document).on("click", ".examvisibility", function () {
        let row = $(this).closest("tr");
        $("#exammodalInvoice").text(row.data("invoice"));
        $("#exammodalStatus").text(row.data("status"));
        $("#exammodalService").text(row.data("service"));
        $("#exammodalUsername").text(row.data("username"));
        $("#exammodalAmount").text(formatter.format(row.data("amount") || 0));
        $("#exammodalPhone").text(row.data("phone"));
        $('#examrefundBtn').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#examrefundBtn').prop('disabled', true);
        } else {
            $('#examrefundBtn').prop('disabled', false);
        }
        $("#examtransactionModal").modal("show");
    });

    // Wallet Modal Functionality
    $(document).on("click", ".walletvisibility", function () {
        let row = $(this).closest("tr");
        $("#walletmodalInvoice").text(row.data("invoice"));
        $("#walletmodalStatus").text(row.data("status"));
        $("#walletmodalUsername").text(row.data("username"));
        $("#walletmodalType").text(row.data("type"));
        $("#walletmodalService").text(row.data("service"));
        $("#walletmodalSender").text(row.data("sender-email"));
        $("#walletmodalReceiver").text(row.data("receiver-email"));
        $("#walletmodalAmount").text(formatter.format(row.data("amount") || 0));
        $("#walletmodalBalanceBefore").text(formatter.format(row.data("balance-before") || 0));
        $("#walletmodalBalanceAfter").text(formatter.format(row.data("balance-after") || 0));
        $('#walletrefundBtn').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#walletrefundBtn').prop('disabled', true);
        } else {
            $('#walletrefundBtn').prop('disabled', false);
        }
        $("#wallettransactionModal").modal("show");
    });

    // Reported Modal Functionality
    $(document).on("click", ".reportedvisibility", function () {
        let row = $(this).closest("tr");
        $("#reportedmodalInvoice").text(row.data("invoice"));
        $("#reportedmodalStatus").text(row.data("status"));
        $("#reportedmodalUsername").text(row.data("username"));
        $("#reportedmodalType").text(row.data("type"));
        $("#reportedmodalService").text(row.data("service"));
        $("#reportedmodalSender").text(row.data("sender-email"));
        $("#reportedmodalReceiver").text(row.data("receiver-email"));
        $("#reportedmodalAmount").text(formatter.format(row.data("amount") || 0));
        $("#reportedmodalBalanceBefore").text(formatter.format(row.data("balance-before") || 0));
        $("#reportedmodalBalanceAfter").text(formatter.format(row.data("balance-after") || 0));
        $('#reportedrefundBtn').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#reportedrefundBtn').prop('disabled', true);
        } else {
            $('#reportedrefundBtn').prop('disabled', false);
        }
        $("#reportedtransactionModal").modal("show");
    });


    // Show/hide specific users dropdown based on radio selection
    $('input[name="sendTo"]').on('change', function () {
        if ($('#sendToSpecific').is(':checked')) {
            $('#specificUsersGroup').show();
        } else {
            $('#specificUsersGroup').hide();
        }
    });

    // Show/hide image upload input based on toggle
    $('#includeImageToggle').on('change', function () {
        if ($(this).is(':checked')) {
            $('#imageUploadGroup').show();
        } else {
            $('#imageUploadGroup').hide();
        }
    });

    // Filter users based on search input
    $('#userSearch').on('input', function () {
        const searchTerm = $(this).val().toLowerCase();
        $('#userList .form-check').each(function () {
            const userText = $(this).text().toLowerCase();
            if (userText.includes(searchTerm)) {
                $(this).removeClass('d-none'); // Show matching users
            } else {
                $(this).addClass('d-none'); // Hide non-matching users
            }
        });
    });

    // Display selected users
    $('#userList').on('change', '.user-checkbox', function () {
        const selectedUsers = [];
        $('#userList .user-checkbox:checked').each(function () {
            selectedUsers.push($(this).next('label').text());
        });
        $('#selectedUsersList').html(selectedUsers.join(', '));
    });


    // ******** START OF ACTIVE TRANSACTION TAB *******

    function setActiveTab(tabId) {
        $('.nav-link').removeClass('active');
        $('.tab-pane').removeClass('show active');
        $(`#${tabId}-tab`).addClass('active');
        $(`#${tabId}`).addClass('show active');
    }

    function saveActiveTab(tabId) {
        sessionStorage.setItem('activeTab', tabId);
    }

    function loadActiveTab() {
        const activeTab = sessionStorage.getItem('activeTab');
        if (activeTab) {
            setActiveTab(activeTab);
        } else {
            setActiveTab('all-transaction-tab');
        }
    }

    $('.nav-link').on('click', function () {
        const tabId = $(this).attr('aria-controls');
        saveActiveTab(tabId);
    });

    loadActiveTab();

    // ******** END OF ACTIVE TRANSACTION TAB *******


    // ******** START OF ACTIVE SIDEBAR LINK *******
    function setActiveSidebarLink() {
        const currentUrl = window.location.href;
        $('#sidebar ul li a').each(function () {
            const linkUrl = $(this).attr('href');
            if (currentUrl.includes(linkUrl)) {
                $(this).parent().addClass('active');
            } else {
                $(this).parent().removeClass('active');
            }
        });
    }

    setActiveSidebarLink();

    // ******** END OF ACTIVE SIDEBAR LINK *******


    // ******* START OF TAB FOR DATA SETTING ******
    function setActiveDataTab(tabId) {
        $(`#${tabId}-tab`).addClass('active');
        $(`#${tabId}`).addClass('show active');
    }

    function saveActiveDataTab(tabId) {
        sessionStorage.setItem('activeDataTab', tabId);
    }

    function loadActiveDataTab() {
        const activeDataTab = sessionStorage.getItem('activeDataTab');
        if (activeDataTab) {
            setActiveDataTab(activeDataTab);
        } else {
            setActiveDataTab('net-mtn-tab');
        }
    }

    $('.data-nav-link').on('click', function () {
        const tabId = $(this).attr('aria-controls');
        saveActiveDataTab(tabId);
    });

    loadActiveDataTab();

    // ******* END OF TAB FOR DATA SETTING ******


   
    // ******** START OF ALL TRANSACTION REFUND **********

    $(document).on('click', '.refund-btn', function () {
        let transactionId = $(this).data('id');
        let refundButton = $(this);

    // Show a confirmation dialog before proceeding
    Swal.fire({
        title: 'Are you sure?',
        text: 'You are about to refund this transaction. This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, refund it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with the refund if the user confirms
            $.ajax({
                url: `/transaction/${transactionId}/refund`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    // Display success or error message using SweetAlert2
                    if (response.type === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            confirmButtonColor: '#3085d6',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Reload the page or update the UI
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#d33',
                        });
                    }
                },
                error: function (xhr) {
                    // Handle AJAX errors
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON.message || 'An error occurred. Please try again.',
                        confirmButtonColor: '#d33',
                    });
                }
            });
        }
    });
});

// ******** END OF ALL TRANSACTION REFUND **********



   // ******** START OF WALLET REFUND **********

    $(document).on('click', '.refund-btns', function () {
        let transactionId = $(this).data('id');
        let refundButton = $(this);

    // Show a confirmation dialog before proceeding
    Swal.fire({
        title: 'Are you sure?',
        text: 'You are about to refund this transaction. This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, refund it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with the refund if the user confirms
            $.ajax({
                url: `/wallet_transac/${transactionId}/walletrefund`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    // Display success or error message using SweetAlert2
                    if (response.type === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            confirmButtonColor: '#3085d6',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Reload the page or update the UI
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#d33',
                        });
                    }
                },
                error: function (xhr) {
                    // Handle AJAX errors
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON.message || 'An error occurred. Please try again.',
                        confirmButtonColor: '#d33',
                    });
                }
            });
        }
    });
});

// ******** END OF WALLET REFUND **********


   // ********** START OF REFUND REPORTED TRANSACTION *********

   $(document).on('click', '.refunds-btns', function () {
    let transactionId = $(this).data('id');

    // Show a confirmation dialog before proceeding
    Swal.fire({
        title: 'Are you sure?',
        text: 'You are about to refund this transaction. This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, refund it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with the refund if the user confirms
            $.ajax({
                url: `/reported/${transactionId}/reportrefund`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    // Display success or error message using SweetAlert2
                    if (response.type === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            confirmButtonColor: '#3085d6',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Reload the page or update the UI
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#d33',
                        });
                    }
                },
                error: function (xhr) {
                    // Handle AJAX errors
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON.message || 'An error occurred. Please try again.',
                        confirmButtonColor: '#d33',
                    });
                }
            });
        }
    });
});

// ******** END OF REFUND REPORTED TRANSACTION *******



    // ********** START OF PAGINATION **********
    const paginationLinks = document.querySelectorAll('.pagination a');

    // Save the current page to localStorage when a pagination link is clicked
    paginationLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            const page = new URL(link.href).searchParams.get('page');
            localStorage.setItem('sliderCurrentPage', page);
        });
    });

    // Restore the current page from localStorage
    const currentPage = localStorage.getItem('sliderCurrentPage');
    if (currentPage) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', currentPage);
        window.location.href = url.toString();
    }

    // ********** END OF PAGINATION **********







});