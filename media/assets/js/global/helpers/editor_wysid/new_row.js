const acym_editorWysidNewRow = {
    addRow1WYSID: function (ui) {
        let content = '<table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255);" cellpadding="0" cellspacing="0" border="0">';
        content += '<tbody>';
        content += '<tr>';

        content += '<th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '</tr>';
        content += '</tbody>';
        content += '</table>';
        jQuery(ui).replaceWith(content);
    },
    addRow2WYSID: function (ui) {
        let content = '<table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255);" cellpadding="0" cellspacing="0" border="0">';
        content += '<tbody>';
        content += '<tr>';

        content += '<th class="small-12 medium-6 large-6 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '<th class="small-12 medium-6 large-6 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '</tr>';
        content += '</tbody>';
        content += '</table>';
        jQuery(ui).replaceWith(content);
    },
    addRow3WYSID: function (ui) {
        let content = '<table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255);" cellpadding="0" cellspacing="0" border="0">';
        content += '<tbody>';
        content += '<tr>';

        content += '<th class="small-12 medium-4 large-4 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '<th class="small-12 medium-4 large-4 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '<th class="small-12 medium-4 large-4 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '</tr>';
        content += '</tbody>';
        content += '</table>';
        jQuery(ui).replaceWith(content);
    },
    addRow4WYSID: function (ui) {
        let content = '<table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255);" cellpadding="0" cellspacing="0" border="0">';
        content += '<tbody>';
        content += '<tr>';

        content += '<th class="small-12 medium-3 large-3 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '<th class="small-12 medium-3 large-3 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '<th class="small-12 medium-3 large-3 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '<th class="small-12 medium-3 large-3 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '</tr>';
        content += '</tbody>';
        content += '</table>';
        jQuery(ui).replaceWith(content);
    },
    addRow5WYSID: function (ui) {
        let content = '<table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255);" cellpadding="0" cellspacing="0" border="0">';
        content += '<tbody>';
        content += '<tr>';

        content += '<th class="small-12 medium-8 large-8 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '<th class="small-12 medium-4 large-4 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '</tr>';
        content += '</tbody>';
        content += '</table>';
        jQuery(ui).replaceWith(content);
    },
    addRow6WYSID: function (ui) {
        let content = '<table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255);" cellpadding="0" cellspacing="0" border="0">';
        content += '<tbody>';
        content += '<tr>';

        content += '<th class="small-12 medium-4 large-4 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '<th class="small-12 medium-8 large-8 columns acym__wysid__row__element__th" valign="top">';
        content += '<table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0"><tbody></tbody></table>';
        content += '</th>';

        content += '</tr>';
        content += '</tbody>';
        content += '</table>';
        jQuery(ui).replaceWith(content);
    },
    addCustomRow: function (ui) {
        let zoneId = jQuery(ui).attr('data-acym-zone-id');
        jQuery(ui).replaceWith('<i id="inserted_custom_zone_spinner" class="acymicon-circle-o-notch acymicon-spin"></i>');

        const data = {
            ctrl: ACYM_IS_ADMIN ? 'zones' : 'frontzones',
            task: 'getForInsertion',
            zoneId: zoneId
        };

        acym_helper.post(ACYM_AJAX_URL, data).then(response => {
            let spinnerInsertion = jQuery('#inserted_custom_zone_spinner');
            if (response.error) {
                acym_editorWysidNotifications.addEditorNotification({
                    'message': '<div class="cell auto acym__autosave__notification">' + response.message + '</div>',
                    'level': 'error'
                }, 3000, true);
                spinnerInsertion.replaceWith('');
            } else {
                let $container = spinnerInsertion.parent();
                spinnerInsertion.replaceWith(response.data.content);

                // Make sure the DContents in the duplicated container have a different id
                $container.find('tr[data-dynamic]').each(function () {
                    jQuery(this).attr('id', acym_editorWysidDynamic.getUniqueId());
                });

                acym_helperEditorWysid.setColumnRefreshUiWYSID();
                acym_editorWysidImage.setImageWidthHeightOnInsert();
                acym_editorWysidContextModal.setButtonOptions();
                acym_editorWysidContextModal.setSpaceOptions();
                acym_editorWysidContextModal.setFollowOptions();
                acym_editorWysidContextModal.setSeparatorOptions();
                acym_editorWysidFontStyle.applyCssOnAllElementTypesBasedOnSettings();
                acym_editorWysidDynamic.setDContentActions();
                acym_editorWysidTinymce.addTinyMceWYSID();
                acym_editorWysidRowSelector.setZoneAndBlockOverlays();
            }
        });
    }
};
