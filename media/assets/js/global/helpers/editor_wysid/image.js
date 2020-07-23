const acym_editorWysidImage = {
    setDoubleClickImage: function () {
        jQuery('.acym__wysid__tinymce--image img').off('dblclick').on('dblclick', function () {
            if (jQuery(this).hasClass('acym__wysid__media__giphy')) {
                acym_editorWysidNewContent.addGiphyWYSID(jQuery(this).closest('.acym__wysid__column__element'));
            } else {
                acym_editorWysidImage.doubleClickImage(jQuery(this));
            }
        });
    },
    doubleClickImage: function ($element) {
        acym_editorWysidNewContent.addMediaWysid($element.closest('.acym__wysid__column__element'));
    },
    addBackgroundImgToRows: function ($element) {
        let $deleteImage = jQuery('#acym__wysid__context__block__background-image__remove');

        if ('none' !== $element.css('background-image')) {
            $deleteImage.show();
        } else {
            $deleteImage.hide();
        }

        jQuery('#acym__wysid__context__block__background-image').off('click').on('click', function () {
            acym_editorWysidNewContent.addMediaWysid($element, true);
        });

        $deleteImage.off('click').on('click', function () {
            $element.css({'background-image': 'none'});
            jQuery(this).hide();
        });
    },
    setImageWidthHeightOnInsert: function () {
        if (jQuery('.acym__wysid__media__inserted').length > 0) {
            setTimeout(function () {
                let $insertedImg = jQuery('.acym__wysid__media__inserted');
                $insertedImg.attr('height', $insertedImg.height()).attr('width', $insertedImg.width());
            }, 200);
        }
    },
};
