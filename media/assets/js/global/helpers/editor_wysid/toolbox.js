const acym_editorWysidToolbox = {
    setRefreshAfterToolbox: function () {
        jQuery('.acym__wysid__row__toolbox__copy').unbind('click').click(function () {
            jQuery(this).closest('.acym__wysid__row__element').clone().insertAfter(jQuery(this).closest('.acym__wysid__row__element'));
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidVersioning.setUndoAndAutoSave();
        });

        jQuery('.acym__wysid__row__toolbox__delete__row').off('click').on('click', function () {
            jQuery(this).closest('.acym__wysid__row__element').remove();
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidVersioning.setUndoAndAutoSave();
        });

        jQuery('.acym__wysid__element__toolbox__copy').off('click').on('click', function () {
            let $elementToClone = jQuery(this).closest('.acym__wysid__column__element');
            let theClone = $elementToClone.clone();
            // Handle the duplication of DContents with unique IDs
            if (theClone.attr('data-dynamic') !== undefined) {
                theClone.attr('id', acym_editorWysidDynammic.getUniqueId());
            }
            theClone.insertAfter($elementToClone);
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidVersioning.setUndoAndAutoSave();
        });

        jQuery('.acym__wysid__element__toolbox__delete').off('click').on('click', function () {
            let $elementToDelete = jQuery(this).closest('.acym__wysid__column__element');
            acym_editorWysidContextModal.hideContextModal(jQuery('.acym__wysid__context__modal'));
            $elementToDelete.remove();
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidVersioning.setUndoAndAutoSave();
        });

        acym_editorWysidToolbox.resizeRows();
        acym_editorWysidDragDrop.setRowElementSortableWYSID();
        acym_editorWysidDragDrop.setColumnElementDraggableWYSID();
    },
    resizeRows: function () {
        jQuery('.acym__wysid__row__toolbox__height').off('mousedown').on('mousedown', function () {
            acym_helperEditorWysid.clicking = true;
            acym_helperEditorWysid.$resizingElement = jQuery(this)
                .closest('table')
                .css({
                    'border-collapse': 'initial',
                    'border-spacing': ''
                });
            jQuery(document).on('mousemove', function (event) {
                if (acym_helperEditorWysid.clicking) {
                    let delta = acym_helperEditorWysid.$resizingElement.offset().top;
                    let height = event.pageY - (delta - 10);
                    acym_helperEditorWysid.$resizingElement.find('th:first').height(height).attr('height', height);
                    acym_helperEditorWysid.$resizingElement.find('.acym__wysid__row__selector')
                                          .css('height', acym_helperEditorWysid.$resizingElement.css('height'));
                }
            });
            jQuery(document).off('mouseup').on('mouseup', function () {
                acym_helperEditorWysid.clicking = false;
                jQuery(this).off('mousdown');
                acym_editorWysidRowSelector.setRowSelector();
            });
        });
    },
    setDeleteAlltoolbox: function () {
        jQuery('.acym__wysid__row__selector, .acym__wysid__element__toolbox').remove();
    }
};
