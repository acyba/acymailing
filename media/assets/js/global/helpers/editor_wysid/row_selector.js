const acym_editorWysidRowSelector = {
    setRowSelector: function () {
        setTimeout(function () {
            if (jQuery('.mce-tinymce-inline').is(':visible')) return true;
            jQuery('.acym__wysid__row__selector, .acym__wysid__element__toolbox').remove();
            jQuery('.acym__wysid__row__element').css('z-index', '100').each(function () {
                let $firstTbody = jQuery(this).find('> tbody');
                if ($firstTbody.css('background-color')
                    !== ''
                    && $firstTbody.css('background-color')
                    !== undefined
                    && $firstTbody.css('background-color')
                    !== 'inherit'
                    && $firstTbody.css('background-color')
                    !== 'rgba(0, 0, 0, 0)') {
                    jQuery(this).css('background-color', $firstTbody.css('background-color'));
                    $firstTbody.css('background-color', 'inherit').attr('bgcolor', '');
                }
                jQuery(this).prepend('<div class="acym__wysid__row__selector"></div>');
            });
            let $template = jQuery('.acym__wysid__template__content');
            let widthTemplate = $template.css('width').replace(/[^-\d\.]/g, '');
            let offsetTemplate = $template.offset();
            jQuery('.acym__wysid__row__selector').each(function () {
                let $parentSelector = jQuery(this).closest('.acym__wysid__row__element');
                let offsetTable = $parentSelector.offset();
                let borderWidthParent = $parentSelector.css('border-width') === '' ? 0 : parseInt($parentSelector.css('border-width').replace(/[^-\d\.]/g, ''));
                let paddingBottomParent = $parentSelector.css('padding-bottom') === '' ? 0 : parseInt($parentSelector.css('padding-bottom')
                                                                                                                     .replace(/[^-\d\.]/g, ''));
                let heightSelector = $parentSelector.height()
                                     + parseInt($parentSelector.css('padding-top').replace(/[^-\d\.]/g, ''))
                                     + paddingBottomParent
                                     + (borderWidthParent * 2);
                let leftSelector = '-' + (Math.round(offsetTable.left) - Math.round(offsetTemplate.left) + borderWidthParent) + 'px';
                jQuery(this)
                    .css({
                        width: widthTemplate + 'px',
                        height: heightSelector,
                        left: leftSelector,
                        top: (0 - borderWidthParent + 'px')
                    });
                jQuery(this)
                    .attr(
                        'style',
                        jQuery(this).attr('style') + 'width: ' + widthTemplate + 'px; height: ' + heightSelector + 'px; left: ' + leftSelector + ';top: ' + (0
                        - borderWidthParent
                        + 'px')
                    );
                let contentToolbox = '<div  class="acym__wysid__row__toolbox">';
                contentToolbox += '<i class="acymicon-content_copy acym__wysid__row__toolbox__copy acym__wysid__row__toolbox__actions"></i>';
                contentToolbox += '<i class="acymicon-arrows acym__wysid__row__element__toolbox__move acym__wysid__row__toolbox__actions"></i>';
                contentToolbox += '<i class="acymicon-delete acym__wysid__row__toolbox__actions acym__wysid__row__toolbox__delete__row"></i>';
                contentToolbox += '</div>';
                contentToolbox += '<div class="acym__wysid__row__height__container">';
                contentToolbox += '<i class="acymicon-code acym__wysid__row__toolbox__height"></i>';
                contentToolbox += '</div>';
                jQuery(this).append(contentToolbox);
            });

            jQuery('.acym__wysid__column__element').each(function () {
                let contentToolboxElement = '<div class="acym__wysid__element__toolbox">';
                contentToolboxElement += '<i class="acymicon-content_copy acym__wysid__element__toolbox__copy acym__wysid__element__toolbox__actions"></i>';
                contentToolboxElement += '<i class="acymicon-arrows acym__wysid__column__element__toolbox__move acym__wysid__element__toolbox__actions"></i>';
                contentToolboxElement += '<i class="acymicon-delete acym__wysid__element__toolbox__actions acym__wysid__element__toolbox__delete"></i>';
                contentToolboxElement += '</div>';
                jQuery(this).append(contentToolboxElement);
            });

            acym_editorWysidToolbox.setRefreshAfterToolbox();
            acym_editorWysidContextModal.setBlockContextModalWYSID();
        }, 150);
    }
};
