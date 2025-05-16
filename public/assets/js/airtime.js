$(document).ready(function () {
    // ******* START OF TAB FOR DATA SETTING ******

    function setActiveDataTabs(tabId) {
        $('.airtime-nav-link').removeClass('active'); // Remove active class from all tab links
        $('.airtime-tab-pane').removeClass('show active'); // Remove active from all tab content

        $(`#${tabId}-tab`).addClass('active'); // Activate the selected tab link
        $(`#${tabId}`).addClass('show active'); // Show the selected tab content
    }

    function saveActiveDataTabs(tabId) {
        sessionStorage.setItem('activeDataTabs', tabId);
    }

    function loadActiveDataTabs() {
        const activeDataTabs = sessionStorage.getItem('activeDataTabs');
        if (activeDataTabs) {
            setActiveDataTabs(activeDataTabs);
        } else {
            setActiveDataTabs('net-mtn'); // Default to MTN
        }
    }

    // Update the click event to save the correct tab ID
    $('.airtime-nav-link').on('click', function () {
        const tabId = $(this).attr('id').replace('-tab', ''); // Extract the tab ID
        saveActiveDataTabs(tabId);
    });

    loadActiveDataTabs();

    // ******* END OF TAB FOR DATA SETTING ******
});