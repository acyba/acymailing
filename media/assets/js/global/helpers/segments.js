const acym_helperSegments = {
    beforeSave: function () {
        let canSubmit = jQuery('[name^=acym_action]').length > 0;
        if (!canSubmit) acym_helperNotification.addNotification(ACYM_JS_TXT.ACYM_PLEASE_SELECT_FILTERS, 'error', true);
        return canSubmit;
    }
};
