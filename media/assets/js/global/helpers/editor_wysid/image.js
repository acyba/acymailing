const acym_editorWysidImage = {
    setDoubleClickImage: function () {
        jQuery('.acym__wysid__tinymce--image img').off('dblclick').on('dblclick', function () {
            if (jQuery(this).hasClass('acym__wysid__media__giphy')) {
                acym_editorWysidNewContent.addGiphyWYSID(jQuery(this).closest('.acym__wysid__column__element'));
            } else if (jQuery(this).hasClass('acym__wysid__media__unsplash')) {
                acym_editorWysidNewContent.addUnsplashWYSID(jQuery(this).closest('.acym__wysid__column__element'));
            } else {
                acym_editorWysidImage.doubleClickImage(jQuery(this));
            }
        });
    },
    doubleClickImage: function ($element) {
        acym_editorWysidImage.openMediaManager($element.closest('.acym__wysid__column__element'));
    },
    addBackgroundImgToRows: function ($element) {
        let $deleteImage = jQuery('#acym__wysid__context__block__background-image__remove');

        if ('none' !== $element.css('background-image')) {
            $deleteImage.show();
        } else {
            $deleteImage.hide();
        }

        jQuery('#acym__wysid__context__block__background-image').off('click').on('click', function () {
            acym_editorWysidImage.openMediaManager($element, true);
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
                $insertedImg.each(function () {
                    jQuery(this).attr('height', jQuery(this).height()).attr('width', jQuery(this).width());
                });
            }, 200);
        }
    },
    openMediaManager: function (ui, rows) {
        if (ACYM_CMS === 'wordpress') {
            acym_editorWysidWordpress.addMediaWPWYSID(ui, rows);
        } else {
            acym_editorWysidJoomla.addMediaJoomlaWYSID(ui, rows);
        }
    },
    setChangeBuiltWithImage: function () {
        jQuery('[name="acym__wysid__built-with__text__color"]').off('change').on('change', function () {
            let $imageBuiltWith = jQuery('[title="poweredby"]');
            let selected = jQuery(this).val();
            let previous = selected === 'white' ? 'black' : 'white';
            $imageBuiltWith.attr('src', $imageBuiltWith.attr('src').replace(previous, selected));
        });
    }
};
