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
        this.openMediaManage($element.closest('.acym__wysid__column__element'));
    },
    addBackgroundImgToRows: function ($element) {
        let $deleteImage = jQuery('#acym__wysid__context__block__background-image__remove');

        if ('none' !== $element.css('background-image')) {
            $deleteImage.show();
        } else {
            $deleteImage.hide();
        }

        jQuery('#acym__wysid__context__block__background-image').off('click').on('click', function () {
            acym_editorWysidImage.openMediaManage($element, true);
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
    openMediaManage: function (ui, rows) {
        if (ACYM_CMS === 'wordpress') {
            acym_editorWysidWordpress.addMediaWPWYSID(ui, rows);
        } else {
            acym_editorWysidJoomla.addMediaJoomlaWYSID(ui, rows);
        }
    },
    setChangeBuiltWithImage: function () {
        jQuery('[name="acym__wysid__built-with__text__color"]').on('change', function () {
            let $imageBuiltWith = jQuery('[title="poweredby"]');
            let selected = jQuery(this).val();
            let previous = selected === 'white' ? 'black' : 'white';
            $imageBuiltWith.attr('src', $imageBuiltWith.attr('src').replace(previous, selected));
        });
    }
};
