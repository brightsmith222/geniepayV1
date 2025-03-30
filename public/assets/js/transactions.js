$(document).ready(function () {

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

function filterData(filter, type, startDate = null, endDate = null) {
    let url = `/filter-data`;
    let formData = new FormData();
    formData.append('filter', filter);
    formData.append('type', type);
    if (startDate && endDate) {
        formData.append('startDate', startDate);
        formData.append('endDate', endDate);
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        console.log(data); // Log the server response
        // Update the UI with the filtered data
    })
    .catch(error => console.error('Error fetching data:', error));
}


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


});