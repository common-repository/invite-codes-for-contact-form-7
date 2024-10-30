document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.nav-tab');
    const activeTabInput = document.getElementById('active_tab');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function (event) {
            event.preventDefault();
            tabs.forEach(function (t) {
                t.classList.remove('nav-tab-active');
            });
            tab.classList.add('nav-tab-active');
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(function (content) {
                content.style.display = 'none';
            });
            const activeContent = document.querySelector(tab.getAttribute('href'));
            if (activeContent) {
                activeContent.style.display = 'block';
            }
            localStorage.setItem('icfcf7_active_tab', tab.getAttribute('href'));
            activeTabInput.value = tab.getAttribute('href');
        });
    });

    const activeTab = localStorage.getItem('icfcf7_active_tab');
    if (activeTab) {
        document.querySelectorAll('.nav-tab').forEach(function (tab) {
            tab.classList.remove('nav-tab-active');
        });
        document.querySelectorAll('.tab-content').forEach(function (content) {
            content.style.display = 'none';
        });
        const targetTab = document.querySelector('a[href="' + activeTab + '"]');
        if (targetTab) {
            targetTab.classList.add('nav-tab-active');
            const targetContent = document.querySelector(activeTab);
            if (targetContent) {
                targetContent.style.display = 'block';
            }
            if (activeTabInput) {
                activeTabInput.value = activeTab;
            }
        }
    }
});

function copyGeneratedCodes() {
    // Get the text field
    var copyText = document.getElementById("GeneratedCodes");

    // Select the text field
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices

    // Copy the text inside the text field
    navigator.clipboard.writeText(copyText.value)
        .then(function () {
            alert(wp.i18n.__('Copied the codes to clipboard.', 'invite-codes-for-contact-form-7'));
        })
        .catch(function (err) {
            alert(wp.i18n.__( 'Failed to copy the codes.', 'invite-codes-for-contact-form-7' ));
        });
}