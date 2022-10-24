jQuery(function($) {
    function Init() {
        setButtonConfigureBounce();
        acym_helperListing.setSortableListing();
    }

    Init();

    function setButtonConfigureBounce() {
        $('#acym__bounce__button__config').on('click', function () {
            localStorage.setItem('acyconfiguration', 'bounce_handling');
        });
    }
});
