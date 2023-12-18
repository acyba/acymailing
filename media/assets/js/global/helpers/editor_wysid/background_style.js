const acym_editorWysidBackgroundStyle = {
    updateBgSize: function () {
        jQuery('[name="acym__wysid__background-size"]').on('change', function (event) {
            let size = jQuery(this).val();
            const $content = jQuery('.acym__wysid__template__content');
            $content.css('background-size', size);
        });
    },
    updateBgRepeat: function () {
        jQuery('[name="acym__wysid__background-repeat"]').on('change', function (event) {
            let repeat = jQuery(this).val();
            const $content = jQuery('.acym__wysid__template__content');
            $content.css('background-repeat', repeat);
        });
    },
    updateBgPosition: function () {
        jQuery('[name="acym__wysid__background-position"]').on('change', function (event) {
            let position = jQuery(this).val();
            const $content = jQuery('.acym__wysid__template__content');
            $content.css('background-position', position);
        });
    }
};
