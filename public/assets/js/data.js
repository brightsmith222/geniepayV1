$(document).ready(function () {
    // ******* START OF TAB FOR DATA SETTING ******

    function setActiveDataTab(tabId) {
        $('.data-nav-link').removeClass('active'); // Remove active class from all tab links
        $('.data-tab-pane').removeClass('show active'); // Remove active from all tab content

        $(`#${tabId}-tab`).addClass('active'); // Activate the selected tab link
        $(`#${tabId}`).addClass('show active'); // Show the selected tab content
    }

    function saveActiveDataTab(tabId) {
        sessionStorage.setItem('activeDataTab', tabId);
    }

    function loadActiveDataTab() {
        const activeDataTab = sessionStorage.getItem('activeDataTab');
        if (activeDataTab) {
            setActiveDataTab(activeDataTab);
        } else {
            setActiveDataTab('net-mtn'); // Default to MTN
        }
    }

    $('.data-nav-link').on('click', function () {
        const tabId = $(this).attr('aria-controls');
        saveActiveDataTab(tabId);
    });

    loadActiveDataTab();

    // ******* END OF TAB FOR DATA SETTING ******
});
