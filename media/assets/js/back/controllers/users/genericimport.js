jQuery(function ($) {

    function Init() {
        $('.fieldAssignment').select2({theme: 'foundation'});
        if (ACYM_CMS === 'joomla') {
            acym_helperJoomla.adjustContainerMainWidth();
            let $leftMenu = jQuery('#acym__joomla__left-menu');
            if (!$leftMenu.length) {
                jQuery('.sidebar-toggle.item.item-level-1').on('click', function (e) {
                    setTimeout(function () {
                        acym_helperJoomla.adjustContainerMainWidth();
                    }, 300);
                });
            }
        }
    }

    Init();
});
