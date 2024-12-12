const acym_helperImage = {
    openMediaManager(callbackSuccess, callbackCancel = null) {
        if (ACYM_CMS === 'wordpress') {
            acym_heleperWordPressGlobal.openMediaManager(callbackSuccess, callbackCancel);
        } else {
            acym_helperJoomlaGlobal.openMediaManager(callbackSuccess, callbackCancel, false);
        }
    }
};
