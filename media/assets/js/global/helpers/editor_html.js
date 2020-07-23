const acym_helperEditorHtml = {
    initEditorHtml: function () {
        acym_helperEditorHtml.setEditorField();
    },
    setEditorField: function () {
        jQuery('#acym_form').on('submit', function () {
            if (typeof acyOnSaveEditor === 'function') acyOnSaveEditor();
        });
    },
};