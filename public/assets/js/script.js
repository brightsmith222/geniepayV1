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

    // Table Pagination and Search
    const rowsPerPage = 5; // Number of rows per page
    const table = $('table tbody');
    let rows = table.find('tr');
    let filteredRows = rows; // Initially, all rows are visible
    let pageCount = Math.ceil(filteredRows.length / rowsPerPage);

    // Create Bootstrap pagination
    const pagination = $('<nav aria-label="Table navigation"><ul class="pagination justify-content-center"></ul></nav>');
    function updatePagination() {
        pagination.find('ul').empty();
        for (let i = 1; i <= pageCount; i++) {
            pagination.find('ul').append(`<li class="page-item"><a class="page-link" href="#">${i}</a></li>`);
        }
    }
    $('.table-responsive').after(pagination);

    // Function to show rows for a specific page
    function showPage(page) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        rows.hide();
        filteredRows.slice(start, end).show();
        $('.page-item').removeClass('active');
        $('.page-item').eq(page - 1).addClass('active'); // Improved pagination highlighting
        sessionStorage.setItem('currentPage', page); // Use sessionStorage instead of localStorage
    }

    // Get the current page from session storage (default to 1 if not set)
    let currentPage = sessionStorage.getItem('currentPage') || 1;

    // Show the saved page (or the first page by default)
    showPage(currentPage);

    // Handle pagination button clicks
    $('.pagination').on('click', '.page-link', function (e) {
        e.preventDefault();
        const page = $(this).text();
        currentPage = page;
        showPage(page);
    });

    // Optimized Search functionality
    $('#searchInput').on('input', function () {
        const searchTerm = $(this).val().toLowerCase();
        filteredRows = rows.filter(function () {
            return $(this).text().toLowerCase().includes(searchTerm);
        }).detach(); // Detach elements for better performance

        table.append(filteredRows); // Reattach filtered rows
        pageCount = Math.ceil(filteredRows.length / rowsPerPage);
        updatePagination();
        showPage(1);
    });

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

    // Actions inside modal
    $("#refundBtn").click(function () {
        alert("Refund initiated for " + $("#modalInvoice").text());
    });

    $("#debitBtn").click(function () {
        alert("Debit initiated for " + $("#modalInvoice").text());
    });

    $("#saveBtn").click(function () {
        alert("Changes saved for " + $("#modalInvoice").text());
    });

    //javascript function for refund
    document.querySelectorAll('.visibility').forEach(button => {
        button.addEventListener('click', function () {
            let row = this.closest('tr');
            let transactionId = row.children[0].textContent.trim(); // Invoice/Transaction ID

            document.getElementById('refundBtn').setAttribute('href', `/transactions/refund/${transactionId}`);
        });
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