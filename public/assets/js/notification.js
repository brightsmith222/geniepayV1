$(document).ready(function () {
    
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


});