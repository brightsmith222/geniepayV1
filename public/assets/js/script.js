$(document).ready(function () {
    const rowsPerPage = 5; // Define rowsPerPage globally

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

    // Initialize pagination and search for each table
    function initializeTable(tableId) {
        
        const table = $(`#${tableId} tbody`);
        let rows = table.find('tr');
        let filteredRows = rows; // Initially, all rows are visible
        let pageCount = Math.ceil(filteredRows.length / rowsPerPage);

        // Create Bootstrap pagination
        const pagination = $(`<nav aria-label="Table navigation"><ul class="pagination justify-content-center"></ul></nav>`);
        function updatePagination() {
            pagination.find('ul').empty();
            for (let i = 1; i <= pageCount; i++) {
                pagination.find('ul').append(`<li class="page-item"><a class="page-link" href="#">${i}</a></li>`);
            }
        }
        $(`#${tableId}`).closest('.card').find('.card-body').append(pagination);

        // Function to show rows for a specific page
        function showPage(page) {
            
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            rows.hide();
            filteredRows.slice(start, end).show();
            pagination.find('.page-item').removeClass('active');
            pagination.find(`.page-item:eq(${page - 1})`).addClass('active');
            sessionStorage.setItem(`${tableId}-currentPage`, page);
        }

        // Get the current page from session storage (default to 1 if not set)
        let currentPage = sessionStorage.getItem(`${tableId}-currentPage`) || 1;

        // Show the saved page (or the first page by default)
        showPage(currentPage);

        // Handle pagination button clicks
        pagination.on('click', '.page-link', function (e) {
            e.preventDefault();
            const page = $(this).text();
            currentPage = page;
            showPage(page);
        });

        // Optimized Search functionality
        $(`#${tableId}`).closest('.card').find('.search-box').on('input', function () {
            const searchTerm = $(this).val().toLowerCase();
            filteredRows = rows.filter(function () {
                return $(this).text().toLowerCase().includes(searchTerm);
            });
            pageCount = Math.ceil(filteredRows.length / rowsPerPage);
            updatePagination();
            showPage(1); // Always show the first page after search
        });
    }

    // Initialize each table
    initializeTable('all-transaction-table');
    initializeTable('data-transaction-table');
    initializeTable('airtime-transaction-table');
    initializeTable('cable-transaction-table');
    initializeTable('electricity-transaction-table');
    initializeTable('exam-transaction-table');

    $('#avatarDropdown').on('click', function (event) {
        event.stopPropagation(); // Prevents closing when clicking on the avatar
        $('#profileMenu').toggle(); // Show/hide the menu
    });

    // Close the menu when clicking outside
    $(document).on('click', function () {
        $('#profileMenu').hide();
    });

    // Prevent menu from closing when clicking inside
    $('#profileMenu').on('click', function (event) {
        event.stopPropagation();
    });

    // Transaction Modal Functionality
    $(document).on("click", ".visibility", function () {
        let row = $(this).closest("tr");

        $("#modalInvoice").text(row.find("td:eq(0)").text().trim());
        $("#modalStatus").text(row.find("td:eq(1)").text().trim());
        $("#modalService").text(row.find("td:eq(2)").text().trim());
        $("#modalUsername").text(row.find("td:eq(3)").text().trim());
        $("#modalProvider").text(row.find("td:eq(4)").text().trim());
        $("#modalPlan").text(row.find("td:eq(5)").text().trim());
        $("#modalAmount").text(row.find("td:eq(6)").text().trim());
        $("#modalPhone").text(row.find("td:eq(7)").text().trim());
        $("#modalCard").text(row.find("td:eq(8)").text().trim());
        $("#modalMeter").text(row.find("td:eq(9)").text().trim());
        $("#modalQuantity").text(row.find("td:eq(10)").text().trim());
        $("#modalToken").text(row.find("td:eq(11)").text().trim());

        $("#transactionModal").modal("show");
    });

    // Data Modal Functionality
    $(document).on("click", ".datavisibility", function () {
        let row = $(this).closest("tr");

        $("#modalInvoice").text(row.find("td:eq(0)").text().trim());
        $("#modalStatus").text(row.find("td:eq(1)").text().trim());
        $("#modalService").text(row.find("td:eq(2)").text().trim());
        $("#modalUsername").text(row.find("td:eq(3)").text().trim());
        $("#modalAmount").text(row.find("td:eq(6)").text().trim());
        $("#modalPhone").text(row.find("td:eq(7)").text().trim());
        $("#modalCard").text(row.find("td:eq(8)").text().trim());

        $("#datatransactionModal").modal("show");
    });

    // Airtime Modal Functionality
    $(document).on("click", ".airtimevisibility", function () {
        let row = $(this).closest("tr");

        $("#modalInvoice").text(row.find("td:eq(0)").text().trim());
        $("#modalStatus").text(row.find("td:eq(1)").text().trim());
        $("#modalService").text(row.find("td:eq(2)").text().trim());
        $("#modalUsername").text(row.find("td:eq(3)").text().trim());
        $("#modalAmount").text(row.find("td:eq(6)").text().trim());
        $("#modalPhone").text(row.find("td:eq(7)").text().trim());
        $("#modalCard").text(row.find("td:eq(8)").text().trim());

        $("#airtimetransactionModal").modal("show");
    });

    // Cable Modal Functionality
    $(document).on("click", ".cablevisibility", function () {
        let row = $(this).closest("tr");

        $("#modalInvoice").text(row.find("td:eq(0)").text().trim());
        $("#modalStatus").text(row.find("td:eq(1)").text().trim());
        $("#modalService").text(row.find("td:eq(2)").text().trim());
        $("#modalUsername").text(row.find("td:eq(3)").text().trim());
        $("#modalAmount").text(row.find("td:eq(6)").text().trim());
        $("#modalPhone").text(row.find("td:eq(7)").text().trim());
        $("#modalCard").text(row.find("td:eq(8)").text().trim());

        $("#cabletransactionModal").modal("show");
    });

    // Electricity Modal Functionality
    $(document).on("click", ".electricityvisibility", function () {
        let row = $(this).closest("tr");

        $("#modalInvoice").text(row.find("td:eq(0)").text().trim());
        $("#modalStatus").text(row.find("td:eq(1)").text().trim());
        $("#modalService").text(row.find("td:eq(2)").text().trim());
        $("#modalUsername").text(row.find("td:eq(3)").text().trim());
        $("#modalAmount").text(row.find("td:eq(6)").text().trim());
        $("#modalPhone").text(row.find("td:eq(7)").text().trim());
        $("#modalCard").text(row.find("td:eq(8)").text().trim());

        $("#electricitytransactionModal").modal("show");
    });

    // Exam Modal Functionality
    $(document).on("click", ".examvisibility", function () {
        let row = $(this).closest("tr");

        $("#modalInvoice").text(row.find("td:eq(0)").text().trim());
        $("#modalStatus").text(row.find("td:eq(1)").text().trim());
        $("#modalService").text(row.find("td:eq(2)").text().trim());
        $("#modalUsername").text(row.find("td:eq(3)").text().trim());
        $("#modalAmount").text(row.find("td:eq(6)").text().trim());
        $("#modalPhone").text(row.find("td:eq(7)").text().trim());
        $("#modalCard").text(row.find("td:eq(8)").text().trim());

        $("#examtransactionModal").modal("show");
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

    // Tab Persistence Logic (using sessionStorage)
    function setActiveTab(tabId) {
        // Remove 'active' class from all tabs and tab panes
        $('.nav-link').removeClass('active');
        $('.tab-pane').removeClass('show active');

        // Add 'active' class to the selected tab and tab pane
        $(`#${tabId}-tab`).addClass('active');
        $(`#${tabId}`).addClass('show active');
    }

    function saveActiveTab(tabId) {
        sessionStorage.setItem('activeTab', tabId); // Use sessionStorage instead of localStorage
    }

    function loadActiveTab() {
        const activeTab = sessionStorage.getItem('activeTab');
        if (activeTab) {
            setActiveTab(activeTab);
        } else {
            // Set the default active tab if no tab is saved in sessionStorage
            setActiveTab('all-transaction'); // Change this to the default tab ID
        }
    }

    // Event listener for tab clicks
    $('.nav-link').on('click', function () {
        const tabId = $(this).attr('aria-controls');
        saveActiveTab(tabId);
    });

    // Load the active tab when the page loads
    loadActiveTab();

    // Set the active sidebar link based on the current URL
    function setActiveSidebarLink() {
        const currentUrl = window.location.href; // Get the current URL
        $('#sidebar ul li a').each(function () {
            const linkUrl = $(this).attr('href'); // Get the href of the sidebar link
            if (currentUrl.includes(linkUrl)) {
                $(this).parent().addClass('active'); // Add 'active' class to the parent <li>
            } else {
                $(this).parent().removeClass('active'); // Remove 'active' class from other <li> elements
            }
        });
    }

    // Call the function to set the active sidebar link
    setActiveSidebarLink();
});