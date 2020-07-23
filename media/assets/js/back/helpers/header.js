const acym_helperHeader = {
    setVersionButton: function () {
        let $checkVersionButton = jQuery('#checkVersionButton');

        $checkVersionButton.on('click', function () {
            let $checkVersionArea = jQuery('#checkVersionArea');
            $checkVersionArea.html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');
            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=update&task=checkForNewVersion';
            jQuery.get(ajaxUrl, function (response) {
                response = acym_helper.parseJson(response);
                $checkVersionArea.html(response.content);
                jQuery('#acym__check__version__last__check').html(response.lastcheck);
            });
        });

        if (1 === $checkVersionButton.data('check')) {
            $checkVersionButton.click();
        }
    },
};