const acym_editorWysidToolbox = {
    setOverlayActions: function () {
        // Zone actions
        acym_editorWysidToolbox.zoneSave();
        acym_editorWysidToolbox.zoneCopy();
        acym_editorWysidToolbox.zoneMoveUp();
        acym_editorWysidToolbox.zoneMoveDown();
        acym_editorWysidToolbox.zoneResize();
        acym_editorWysidDragDrop.setZonesSortable();
        acym_editorWysidToolbox.zoneDelete();

        // Block actions
        acym_editorWysidToolbox.blockCopy();
        acym_editorWysidToolbox.blockMoveUp();
        acym_editorWysidToolbox.blockMoveDown();
        acym_editorWysidDragDrop.setBlocksDraggable();
        acym_editorWysidToolbox.blockDelete();
    },
    zoneSave: function () {
        jQuery('.acym__wysid__row__toolbox__save').off('click').on('click', function (e) {
            e.stopPropagation();
            acym_helperEditorWysid.$focusElement = jQuery(this).closest('.acym__wysid__row__element');
            acym_editorWysidNewContent.addCustomZoneWYSID();
        });
    },
    zoneCopy: function () {
        jQuery('.acym__wysid__row__toolbox__copy').off('click').on('click', function () {
            let $duplication = jQuery(this).closest('.acym__wysid__row__element').clone();
            acym_helperBlockSeparator.changeIdOnduplicate($duplication)
                                     .insertAfter(jQuery(this).closest('.acym__wysid__row__element'));

            // Make sure the DContents in the duplicated container have a different id
            $duplication.find('tr[data-dynamic]').each(function () {
                jQuery(this).attr('id', acym_editorWysidDynamic.getUniqueId());
            });

            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidContextModal.setButtonOptions();
            acym_editorWysidContextModal.setSpaceOptions();
            acym_editorWysidContextModal.setFollowOptions();
            acym_editorWysidContextModal.setSeparatorOptions();
            acym_editorWysidDynamic.setDContentActions();
            acym_editorWysidTinymce.addTinyMceWYSID();
        });
    },
    zoneMoveUp: function () {
        jQuery('.acym__wysid__row__toolbox__moveup').off('click').on('click', function (e) {
            e.stopPropagation();
            acym_helperEditorWysid.$focusElement = jQuery(this).closest('.acym__wysid__row__element');
            let previousRow = acym_helperEditorWysid.$focusElement.prev();
            if (previousRow.length > 0) {
                acym_helperEditorWysid.$focusElement.insertBefore(previousRow);
                acym_editorWysidVersioning.setUndoAndAutoSave();
            }
        });
    },
    zoneMoveDown: function () {
        jQuery('.acym__wysid__row__toolbox__movedown').off('click').on('click', function (e) {
            e.stopPropagation();
            acym_helperEditorWysid.$focusElement = jQuery(this).closest('.acym__wysid__row__element');
            let nextRow = acym_helperEditorWysid.$focusElement.next();
            if (nextRow.length > 0 && 'acym__powered_by_acymailing' !== nextRow.attr('id')) {
                acym_helperEditorWysid.$focusElement.insertAfter(nextRow);
                acym_editorWysidVersioning.setUndoAndAutoSave();
            }
        });
    },
    zoneResize: function () {
        jQuery('.acym__wysid__row__toolbox__height').off('mousedown').on('mousedown', function () {
            acym_helperEditorWysid.clicking = true;
            acym_helperEditorWysid.$resizingElement = jQuery(this)
                .closest('table')
                .css({
                    'border-collapse': 'initial',
                    'border-spacing': ''
                });
            jQuery(document).on('mousemove', function (event) {
                if (!acym_helperEditorWysid.clicking) return;

                let delta = acym_helperEditorWysid.$resizingElement.offset().top;
                let height = event.pageY - (delta - 10);
                acym_helperEditorWysid.$resizingElement.find('th:first').height(height).attr('height', height);
                acym_helperEditorWysid.$resizingElement.find('.acym__wysid__row__selector')
                                      .css('height', acym_helperEditorWysid.$resizingElement.css('height'));
            });
            jQuery(document).off('mouseup').on('mouseup', function () {
                acym_helperEditorWysid.clicking = false;
                jQuery(document).off('mousemove');
                acym_editorWysidRowSelector.setZoneAndBlockOverlays();
            });
        });
    },
    zoneDelete: function () {
        jQuery('.acym__wysid__row__toolbox__delete__row').off('click').on('click', function () {
            jQuery(this).closest('.acym__wysid__row__element').remove();
            acym_helperEditorWysid.addDefaultZone();
            acym_editorWysidVersioning.setUndoAndAutoSave();
        });
    },
    blockCopy: function () {
        jQuery('.acym__wysid__element__toolbox__copy').off('click').on('click', function () {
            let $elementToClone = jQuery(this).closest('.acym__wysid__column__element');
            let theClone = $elementToClone.clone();
            // Handle the duplication of DContents with unique IDs
            if (theClone.attr('data-dynamic') !== undefined) {
                theClone.attr('id', acym_editorWysidDynamic.getUniqueId());
            }
            theClone.insertAfter($elementToClone);
            acym_helperEditorWysid.setColumnRefreshUiWYSID();

            if (theClone.find('.acym__wysid__column__element__button').length > 0) {
                acym_editorWysidContextModal.setButtonOptions();
            } else if (theClone.find('.acy-editor__space').length > 0) {
                acym_editorWysidContextModal.setSpaceOptions();
            } else if (theClone.find('.acym__wysid__column__element__follow').length > 0) {
                acym_editorWysidContextModal.setFollowOptions();
            } else if (theClone.find('.acym__wysid__row__separator').length > 0) {
                acym_editorWysidContextModal.setSeparatorOptions();
            } else if (theClone.attr('data-dynamic')) {
                acym_editorWysidDynamic.setDContentActions();
            } else if (theClone.find('.acym__wysid__tinymce--text').length > 0 || theClone.find('.acym__wysid__tinymce--image').length > 0) {
                acym_editorWysidTinymce.addTinyMceWYSID();
            }
        });
    },
    blockMoveUp: function () {
        jQuery('.acym__wysid__element__toolbox__moveup').off('click').on('click', function (e) {
            e.stopPropagation();
            acym_helperEditorWysid.$focusElement = jQuery(this).closest('.acym__wysid__column__element');
            let previousRow = acym_helperEditorWysid.$focusElement.prev();
            if (previousRow.length > 0) {
                acym_helperEditorWysid.$focusElement.insertBefore(previousRow);
                acym_editorWysidVersioning.setUndoAndAutoSave();
            }
        });
    },
    blockMoveDown: function () {
        jQuery('.acym__wysid__element__toolbox__movedown').off('click').on('click', function (e) {
            e.stopPropagation();
            acym_helperEditorWysid.$focusElement = jQuery(this).closest('.acym__wysid__column__element');
            let nextRow = acym_helperEditorWysid.$focusElement.next();
            if (nextRow.length > 0) {
                acym_helperEditorWysid.$focusElement.insertAfter(nextRow);
                acym_editorWysidVersioning.setUndoAndAutoSave();
            }
        });
    },
    blockDelete: function () {
        jQuery('.acym__wysid__element__toolbox__delete').off('click').on('click', function () {
            let $elementToDelete = jQuery(this).closest('.acym__wysid__column__element');
            acym_editorWysidContextModal.hideBlockOptions(jQuery('.acym__wysid__context__modal'));
            $elementToDelete.remove();
            acym_helperEditorWysid.addDefaultBlock();
            acym_editorWysidRowSelector.resizeZoneOverlays();
            acym_editorWysidVersioning.setUndoAndAutoSave();
        });
    }
};
