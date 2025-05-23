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




});

    // Get the toggle button
    const darkModeToggle = document.getElementById('darkModeToggle');

    // Check the user's saved preference
    const savedMode = localStorage.getItem('theme');
    if (savedMode === 'dark') {
        document.body.classList.add('dark-mode');
    }

    // Toggle dark mode on button click
    darkModeToggle.addEventListener('click', () => {
        const isDarkMode = document.body.classList.toggle('dark-mode');

        // Save the user's preference
        if (isDarkMode) {
            localStorage.setItem('theme', 'dark');
        } else {
            localStorage.setItem('theme', 'light');
        }
    });

function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    if (submenu.style.display === "none") {
        submenu.style.display = "block";
    } else {
        submenu.style.display = "none";
    }
}