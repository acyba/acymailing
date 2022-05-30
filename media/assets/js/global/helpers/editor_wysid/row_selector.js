const acym_editorWysidRowSelector = {
    deleteOverlays: function () {
        jQuery('.acym__wysid__row__selector, .acym__wysid__element__toolbox').remove();
    },
    setZoneAndBlockOverlays: function () {
        // This timeout is to make sure the content has loaded, to have the correct overlay height
        setTimeout(function () {
            if (jQuery('.mce-tinymce-inline').is(':visible')) return true;

            acym_editorWysidRowSelector.deleteOverlays();

            // Add overlay and action buttons on zones
            jQuery('.acym__wysid__row__element').css('z-index', '100').each(function () {
                let $thisRow = jQuery(this);
                let $firstTbody = $thisRow.find('> tbody');
                let firstTbodyColor = $firstTbody.css('background-color');
                if (!acym_helper.empty(firstTbodyColor) && firstTbodyColor !== 'inherit' && firstTbodyColor !== 'rgba(0, 0, 0, 0)') {
                    $thisRow.css('background-color', firstTbodyColor);
                    $firstTbody.css('background-color', 'inherit').attr('bgcolor', '');
                }

                let zoneOverlay = '<div class="acym__wysid__row__selector">';
                zoneOverlay += '<div  class="acym__wysid__row__toolbox">';
                zoneOverlay += '<i class="acymicon-floppy-o acym__wysid__row__toolbox__save acym__wysid__row__toolbox__actions"></i>';
                zoneOverlay += '<i class="acymicon-content_copy acym__wysid__row__toolbox__copy acym__wysid__row__toolbox__actions"></i>';
                zoneOverlay += '<i class="acymicon-arrow-up acym__wysid__row__toolbox__moveup acym__wysid__row__toolbox__actions"></i>';
                zoneOverlay += '<i class="acymicon-arrow-down acym__wysid__row__toolbox__movedown acym__wysid__row__toolbox__actions"></i>';
                zoneOverlay += '<i class="acymicon-arrows acym__wysid__row__element__toolbox__move acym__wysid__row__toolbox__actions"></i>';
                zoneOverlay += '<i class="acymicon-delete acym__wysid__row__toolbox__actions acym__wysid__row__toolbox__delete__row"></i>';
                zoneOverlay += '</div>';
                zoneOverlay += '<div class="acym__wysid__row__height__container">';
                zoneOverlay += '<i class="acymicon-code acym__wysid__row__toolbox__height"></i>';
                zoneOverlay += '</div>';
                zoneOverlay += '</div>';
                $thisRow.prepend(zoneOverlay);
            });
            acym_editorWysidRowSelector.resizeZoneOverlays();

            // Add overlay and action buttons on blocks
            jQuery('.acym__wysid__column__element').each(function () {
                let blockOverlay = '<div class="acym__wysid__element__toolbox">';
                blockOverlay += '<i class="acymicon-content_copy acym__wysid__element__toolbox__copy acym__wysid__element__toolbox__actions"></i>';
                blockOverlay += '<i class="acymicon-arrow-up acym__wysid__element__toolbox__moveup acym__wysid__element__toolbox__actions"></i>';
                blockOverlay += '<i class="acymicon-arrow-down acym__wysid__element__toolbox__movedown acym__wysid__element__toolbox__actions"></i>';
                blockOverlay += '<i class="acymicon-arrows acym__wysid__column__element__toolbox__move acym__wysid__element__toolbox__actions"></i>';
                blockOverlay += '<i class="acymicon-delete acym__wysid__element__toolbox__delete acym__wysid__element__toolbox__actions"></i>';
                blockOverlay += '</div>';
                jQuery(this).append(blockOverlay);
            });

            acym_editorWysidToolbox.setOverlayActions();
            acym_editorWysidContextModal.setZoneOptions();
        }, 150);
    },
    resizeZoneOverlays: function () {
        let $emailContentContainer = jQuery('.acym__wysid__template__content');
        let widthTemplate = $emailContentContainer.css('width').replace(/[^-\d\.]/g, '');
        let offsetTemplate = $emailContentContainer.offset();

        jQuery('.acym__wysid__row__selector').each(function () {
            let $overlay = jQuery(this);
            let $zoneElement = $overlay.closest('.acym__wysid__row__element');
            let offsetTable = $zoneElement.offset();

            let borderWidthParent = $zoneElement.css('border-width') === '' ? 0 : parseInt($zoneElement.css('border-width').replace(/[^-\d\.]/g, ''));
            let paddingBottomParent = $zoneElement.css('padding-bottom') === '' ? 0 : parseInt($zoneElement.css('padding-bottom')
                                                                                                           .replace(/[^-\d\.]/g, ''));
            let heightSelector = $zoneElement.height()
                                 + parseInt($zoneElement.css('padding-top').replace(/[^-\d\.]/g, ''))
                                 + paddingBottomParent
                                 + (borderWidthParent * 2);
            let leftSelector = '-' + (Math.round(offsetTable.left) - Math.round(offsetTemplate.left) + borderWidthParent) + 'px';

            $overlay.css({
                width: widthTemplate + 'px',
                height: heightSelector,
                left: leftSelector,
                top: (0 - borderWidthParent + 'px')
            });
            $overlay.attr(
                'style',
                $overlay.attr('style') + 'width: ' + widthTemplate + 'px; height: ' + heightSelector + 'px; left: ' + leftSelector + ';top: ' + (0
                - borderWidthParent
                + 'px')
            );
        });
    },
    hideOverlays: function () {
        jQuery('.acym__wysid__row__selector').addClass('acym__wysid__row__selector--hidden');
        jQuery('.acym__wysid__element__toolbox').addClass('acym__wysid__element__toolbox--hidden');
    },
    showOverlays: function () {
        jQuery('.acym__wysid__row__selector--hidden').removeClass('acym__wysid__row__selector--hidden');
        jQuery('.acym__wysid__element__toolbox--hidden').removeClass('acym__wysid__element__toolbox--hidden');
    }
};
