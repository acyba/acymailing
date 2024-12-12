const acym_editorWysidDragDrop = {
    currentTimeout: null,
    instance: null,
    autoScrollInterval: null,
    setNewZoneDraggable: function () {
        jQuery('.acym__wysid__zone__element--new').draggable({
            cursor: 'move', //Cursor appearance during drag
            helper: 'clone', //Clone the element that has been dragged instead of directly dragging it,
            cursorAt: {
                top: 20,
                left: 50
            }, //Cursor position when you start to drag an element
            connectToSortable104: '.acym__wysid__row', //Allows the drag element to be dropped onto this sortable
            revertDuration: 300,
            revert: function (isValidDrop) {
                //If you drop an element on an invalid position, it returns to its first place with an animation.
                if (!isValidDrop) {
                    acym_editorWysidRowSelector.showOverlays();
                    return true;
                }
            },
            start: function (event, ui) {
                jQuery(ui.helper)
                    .css({
                        'width': jQuery(this).width() + 'px',
                        'opacity': 1
                    });
                acym_editorWysidRowSelector.hideOverlays();
            }
        });

        // Handle custom zone deletion
        jQuery('.acym__wysid__zone__element--new .acymicon-delete').off('click').on('click', function () {
            if (!acym_helper.confirm(ACYM_JS_TXT.ACYM_CONFIRM_DELETION_ZONE)) return;

            let customZone = jQuery(this).closest('.acym__wysid__zone__element--new');
            const data = {
                ctrl: 'zones',
                task: 'delete',
                zoneId: customZone.attr('data-acym-zone-id')
            };
            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                if (response.error) {
                    acym_editorWysidNotifications.addEditorNotification({
                        'message': '<div class="cell auto acym__autosave__notification">' + response.message + '</div>',
                        'level': 'error'
                    }, 3000, true);
                } else {
                    customZone.remove();
                }
            });
        });
    },
    setZonesSortable: function () {
        jQuery('.acym__wysid__row').sortable({
            axis: 'y', //If defined, the items can be dragged only horizontally or vertically
            cursorAt: {top: 20}, //Cursor position when you start to drag an element
            scroll: true, //If set to true, the page scrolls when coming to an edge
            placeholder: 'acym__wysid__row__element--placeholder', //A class name that gets applied to the otherwise white space
            handle: '.acym__wysid__row__element__toolbox__move', //Restricts sort start click to this element
            forcePlaceholderSize: true, //Forces the placeholder to have a size
            tolerance: 'intersect', //Specifies which mode to use for testing whether the item being moved is hovering over another item
            start: function (event, ui) {
                ui.helper.first().addClass('acym__wysid__row__element--sortable');
                jQuery(ui.placeholder).css({'height': '75px'});
                acym_editorWysidRowSelector.hideOverlays();
            },
            stop: function (event, ui) {
                jQuery('.acym__wysid__row__element--sortable').removeClass('acym__wysid__row__element--sortable');

                // Replace the dropped item with the desired zone type
                let $item = ui.item;
                if ($item.hasClass('acym__wysid__zone__element--new--1')) {
                    acym_editorWysidNewRow.addRow1WYSID($item);
                } else if ($item.hasClass('acym__wysid__zone__element--new--2')) {
                    acym_editorWysidNewRow.addRow2WYSID($item);
                } else if ($item.hasClass('acym__wysid__zone__element--new--3')) {
                    acym_editorWysidNewRow.addRow3WYSID($item);
                } else if ($item.hasClass('acym__wysid__zone__element--new--4')) {
                    acym_editorWysidNewRow.addRow4WYSID($item);
                } else if ($item.hasClass('acym__wysid__zone__element--new--5')) {
                    acym_editorWysidNewRow.addRow5WYSID($item);
                } else if ($item.hasClass('acym__wysid__zone__element--new--6')) {
                    acym_editorWysidNewRow.addRow6WYSID($item);
                } else {
                    let zoneId = $item.attr('data-acym-zone-id');
                    if (zoneId && zoneId.length > 0) {
                        acym_editorWysidNewRow.addCustomRow($item);
                    }
                }
                acym_editorWysidRowSelector.setZoneAndBlockOverlays();
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
            },
            receive: function (event, ui) {
                ui.helper.remove();
            }
        });
    },
    setNewBlockDraggable: function () {
        jQuery('.acym__wysid__block__element--new').draggable({
            cursor: 'move', //Cursor appearance during drag
            helper: 'clone', //Clone the element that has been dragged instead of directly dragging it
            cursorAt: {
                top: 12,
                left: 40
            }, //Cursor position when you start to drag an element
            connectToSortable104: '.acym__wysid__column > tbody', //Allows the drag element to be dropped onto this sortable
            revertDuration: 300,
            revert: function (isValidDrop) {
                //If you drop an element on an invalid position, it returns to its first place with an animation.
                if (!isValidDrop) {
                    acym_editorWysidRowSelector.showOverlays();
                    return true;
                }
            },
            start: function (event, ui) {
                jQuery(ui.helper)
                    .css({
                        'width': jQuery(this).width() + 'px',
                        'opacity': 1
                    });
                jQuery('.acym__wysid__column').addClass('acym__wysid__column--drag-start');
                acym_editorWysidRowSelector.hideOverlays();

                acym_editorWysidDragDrop.showDropHereMessage();
            },
            stop: function () {
                jQuery('.acym__wysid__column').removeClass('acym__wysid__column--drag-start');

                acym_editorWysidDragDrop.hideDropHereMessage();
            }
        });
    },
    setBlocksSortable: function () {
        jQuery('.acym__wysid__column tbody').sortable({
            scroll: true, //If set to true, the page scrolls when coming to an edge
            handle: '.acym__wysid__column__element__toolbox__move', //Restricts sort start click to this element
            placeholder: 'acym__wysid__column__element--placeholder', //A class name that gets applied to the otherwise white space
            forcePlaceholderSize: true, //Forces the placeholder to have a size
            cursorAt: {
                top: 20,
                left: 50
            }, //Cursor position when you start to drag an element
            start: function () {
                jQuery('.acym__wysid__column__element--placeholder').html('');
                acym_editorWysidRowSelector.hideOverlays();
            },
            stop: function (event, ui) {
                let $defaultStart = jQuery('#acym__wysid__default');
                $defaultStart
                    .closest('.columns').height('auto')
                    .find('table').height('auto')
                    .find('tbody').height('auto');
                $defaultStart.remove();
                jQuery('.acym__wysid__column__first').removeClass('acym__wysid__column__first');

                // Replace dropped item by the correct block
                let $item = ui.item;
                let replacement = '';

                let plugin = $item.attr('data-plugin');
                if (plugin) {
                    acym_helperEditorWysid.$focusElement = jQuery($item);
                    acym_editorWysidDynamic.openDContentOptions(plugin, acym_helperEditorWysid.$focusElement.attr('data-dynamic'));
                } else if ($item.hasClass('acym__wysid__block__element--new--title')) {
                    replacement = acym_editorWysidNewContent.addTitleWYSID();
                } else if ($item.hasClass('acym__wysid__block__element--new--text')) {
                    replacement = acym_editorWysidNewContent.addTextWYSID();
                } else if ($item.hasClass('acym__wysid__block__element--new--button')) {
                    replacement = acym_editorWysidNewContent.addButtonWYSID();
                } else if ($item.hasClass('acym__wysid__block__element--new--space')) {
                    replacement = acym_editorWysidNewContent.addSpaceWYSID();
                } else if ($item.hasClass('acym__wysid__block__element--new--follow')) {
                    replacement = acym_editorWysidNewContent.addFollowWYSID();
                } else if ($item.hasClass('acym__wysid__block__element--new--separator')) {
                    replacement = acym_editorWysidNewContent.addSeparatorWysid();
                } else if ($item.hasClass('acym__wysid__block__element--new--picture')) {
                    acym_editorWysidNewContent.addMediaWysid($item);
                } else if ($item.hasClass('acym__wysid__block__element--new--video')) {
                    acym_editorWysidNewContent.addVideoWYSID($item);
                } else if ($item.hasClass('acym__wysid__block__element--new--gif')) {
                    acym_editorWysidNewContent.addGifWYSID($item);
                } else if ($item.hasClass('acym__wysid__block__element--new--unsplash')) {
                    acym_editorWysidNewContent.addUnsplashWYSID($item);
                } else {
                    // When we move a block that was already inside the email has been moved
                    replacement = 'existing';
                }

                if (replacement.length > 0) {
                    if (replacement !== 'existing') {
                        jQuery($item).replaceWith(replacement);
                        acym_helperEditorWysid.setColumnRefreshUiWYSID();
                        acym_editorWysidRowSelector.setZoneAndBlockOverlays();

                        if (replacement.indexOf('acym__wysid__column__element__button') !== -1) {
                            acym_editorWysidContextModal.setButtonOptions();
                        } else if (replacement.indexOf('acy-editor__space') !== -1) {
                            acym_editorWysidContextModal.setSpaceOptions();
                        } else if (replacement.indexOf('acym__wysid__column__element__follow') !== -1) {
                            acym_editorWysidContextModal.setFollowOptions();
                        } else if (replacement.indexOf('acym__wysid__row__separator') !== -1) {
                            acym_editorWysidContextModal.setSeparatorOptions();
                        } else if (replacement.indexOf('acym__wysid__tinymce--text') !== -1 || replacement.indexOf('acym__wysid__tinymce--image') !== -1) {
                            acym_editorWysidTinymce.addTinyMceWYSID();
                        }
                    } else {
                        acym_helperEditorWysid.setColumnRefreshUiWYSID();
                        acym_editorWysidRowSelector.setZoneAndBlockOverlays();
                        if (jQuery($item).find('.acym__wysid__column__element__button').length > 0) {
                            acym_editorWysidContextModal.setButtonOptions();
                        } else if (jQuery($item).find('.acy-editor__space').length > 0) {
                            acym_editorWysidContextModal.setSpaceOptions();
                        } else if (jQuery($item).find('.acym__wysid__column__element__follow').length > 0) {
                            acym_editorWysidContextModal.setFollowOptions();
                        } else if (jQuery($item).find('.acym__wysid__row__separator').length > 0) {
                            acym_editorWysidContextModal.setSeparatorOptions();
                        } else if (jQuery($item).find('.acym__wysid__tinymce--text').length
                                   > 0
                                   || jQuery($item).find('.acym__wysid__tinymce--image').length
                                   > 0) {
                            acym_editorWysidTinymce.addTinyMceWYSID();
                        }
                    }
                    acym_editorWysidFontStyle.applyCssOnAllElementTypesBasedOnSettings();
                }
            },
            receive: function (event, ui) {
                // When we move a block that was already in the email, a copy of the block is dropped
                if (!ui.item.hasClass('acym__wysid__block__element--new')) {
                    // We then remove the original block
                    ui.item.remove();
                    jQuery('.acym__wysid__column__element--helper').remove();
                    acym_editorWysidRowSelector.showOverlays();
                } else {
                    ui.helper.remove();
                }
            }
        });
    },
    showDropHereMessage: function () {
        // acym__wysid__default is the default zone with the "Your template is empty!" message
        if (!jQuery('#acym__wysid__default').length) return;

        jQuery('#acym__wysid__default__start').hide();
        let $startDragging = jQuery('#acym__wysid__default__dragging');
        let draggingMessageHeight = $startDragging.height();
        $startDragging.show();
        $startDragging.closest('#acym__wysid__default')
                      .attr('height', 'auto')
                      .closest('.columns')
                      .height(draggingMessageHeight)
                      .find('table')
                      .height(draggingMessageHeight)
                      .find('tbody')
                      .height(draggingMessageHeight);
    },
    hideDropHereMessage: function () {
        // acym__wysid__default is the default zone with the "Your template is empty!" message
        let $default = jQuery('#acym__wysid__default');
        if ($default.length === 0) return;

        $default.find('#acym__wysid__default__dragging').hide();
        $default.find('#acym__wysid__default__start').show();

        let $startDefault = jQuery('#acym__wysid__default__start');
        let startMessageHeight = $startDefault.height();
        $startDefault.closest('#acym__wysid__default')
                     .attr('height', 'auto')
                     .closest('.columns')
                     .height(startMessageHeight)
                     .find('table')
                     .height(startMessageHeight)
                     .find('tbody')
                     .height(startMessageHeight);
    },
    setBlocksDraggable: function () {
        jQuery('.acym__wysid__column__element').draggable({
            cursor: 'move', //Cursor appearance during drag*/
            helper: 'clone', //Clone the element that has been dragged instead of directly dragging it
            revert: 'invalid', //If you drop an element on an invalid position, it returns to its first place with an animation
            handle: '.acym__wysid__column__element__toolbox__move', //Restricts sort start click to this element
            cursorAt: {
                top: 20,
                left: 50
            }, //Cursor position when you start to drag an element
            connectToSortable104: '.acym__wysid__column tbody', //Allows the drag element to be dropped onto this sortables
            revertDuration: 300,
            start: function (event, ui) {
                ui.helper.first().addClass('acym__wysid__column__element--helper');
                jQuery(this).addClass('acym__wysid__column__element__original--helper');
                jQuery('.acym__wysid__column').addClass('acym__wysid__column--drag-start');
                acym_editorWysidRowSelector.hideOverlays();
            },
            stop: function () {
                jQuery('.acym__wysid__column__element--helper').removeClass('acym__wysid__column__element--helper');
                jQuery('.acym__wysid__column__element__original--helper').removeClass('acym__wysid__column__element__original--helper');
                jQuery('.acym__wysid__column').removeClass('acym__wysid__column--drag-start');
                jQuery('.acym__wysid__column__element').css({
                    'position': 'relative',
                    'top': 'inherit',
                    'left': 'inherit',
                    'right': 'inherit',
                    'bottom': 'inherit',
                    'height': 'auto'
                });
                acym_editorWysidRowSelector.showOverlays();
            }
        });
    },
    setFixJquerySortableWYSID: function () {
        let uiSortable;

        jQuery.ui.plugin.add('draggable', 'connectToSortable104', {
            start: function (event, ui, draggable) {
                acym_editorWysidDragDrop.instance = jQuery(this).data('ui-draggable');
                acym_editorWysidDragDrop.instance.sortables = [];

                jQuery(draggable.options.connectToSortable104).each(function () {
                    let sortable = jQuery(this).sortable('instance');
                    if (!sortable || sortable.options.disabled) return;

                    acym_editorWysidDragDrop.instance.sortables.push({
                        instance: sortable,
                        shouldRevert: sortable.options.revert
                    });
                    // Call the sortable's refreshPositions at drag start to refresh the containerCache since the sortable container cache is used in drag
                    // and needs to be up to date (this will ensure it's initialised as well as being kept in step with any changes that might have happened on the page).
                    sortable.refreshPositions();
                    sortable._trigger('activate', event, uiSortable);
                });

                acym_editorWysidDragDrop.refreshSortablesInstances(draggable, uiSortable);
            },
            drag: function (event, ui, draggable) {
                acym_editorWysidDragDrop.handleAutoScroll(event);

                let that = this;

                // instance.sortables = an array of the zones that currently exist in the email
                jQuery.each(acym_editorWysidDragDrop.instance.sortables, function () {
                    let innermostIntersecting = false;
                    let thisSortable = this;

                    //Copy over some variables to allow calling the sortable's native _intersectsWith
                    this.instance.positionAbs = acym_editorWysidDragDrop.instance.positionAbs;
                    this.instance.helperProportions = acym_editorWysidDragDrop.instance.helperProportions;
                    this.instance.offset.click = acym_editorWysidDragDrop.instance.offset.click;

                    if (this.instance._intersectsWith(this.instance.containerCache)) {
                        innermostIntersecting = true;
                        jQuery.each(acym_editorWysidDragDrop.instance.sortables, function () {
                            this.instance.positionAbs = acym_editorWysidDragDrop.instance.positionAbs;
                            this.instance.helperProportions = acym_editorWysidDragDrop.instance.helperProportions;
                            this.instance.offset.click = acym_editorWysidDragDrop.instance.offset.click;
                            if (this
                                !== thisSortable
                                && this.instance._intersectsWith(this.instance.containerCache)
                                && jQuery.contains(thisSortable.instance.element[0], this.instance.element[0])) {
                                innermostIntersecting = false;
                            }
                            return innermostIntersecting;
                        });
                    }

                    if (innermostIntersecting) {
                        //If it intersects, we use a little isOver variable and set it once, so our move-in stuff gets fired only once
                        if (!this.instance.isOver) {
                            this.instance.isOver = 1;
                            //Now we fake the start of dragging for the sortable instance,
                            //by cloning the list group item, appending it to the sortable and using it as inst.currentItem
                            //We can then fire the start event of the sortable with our passed browser event, and our own helper (so it doesn't create a new one)
                            this.instance.currentItem = jQuery(that).clone().removeAttr('id').appendTo(this.instance.element).data('ui-sortable-item', true);
                            //Store helper option to later restore it
                            this.instance.options._helper = this.instance.options.helper;

                            event.target = this.instance.currentItem[0];
                            this.instance._mouseCapture(event, true);
                            this.instance._mouseStart(event, true, true);

                            //Because the browser event is way off the new appended portlet, we modify a couple of variables to reflect the changes
                            this.instance.offset.click.top = acym_editorWysidDragDrop.instance.offset.click.top;
                            this.instance.offset.click.left = acym_editorWysidDragDrop.instance.offset.click.left;
                            this.instance.offset.parent.top -= acym_editorWysidDragDrop.instance.offset.parent.top - this.instance.offset.parent.top;
                            this.instance.offset.parent.left -= acym_editorWysidDragDrop.instance.offset.parent.left - this.instance.offset.parent.left;

                            acym_editorWysidDragDrop.instance._trigger('toSortable', event);
                            //draggable revert needs that
                            acym_editorWysidDragDrop.instance.dropped = this.instance.element;
                            //hack so receive/update callbacks work (mostly)
                            acym_editorWysidDragDrop.instance.currentItem = acym_editorWysidDragDrop.instance.element;
                            this.instance.fromOutside = acym_editorWysidDragDrop.instance;
                        }

                        //Provided we did all the previous steps, we can fire the drag event of the sortable on every draggable drag, when it intersects with the sortable
                        if (this.instance.currentItem) {
                            this.instance._mouseDrag(event);
                        }
                    } else {
                        //If it doesn't intersect with the sortable, and it intersected before,
                        //we fake the drag stop of the sortable, but make sure it doesn't remove the helper by using cancelHelperRemoval
                        if (!this.instance.isOver) return;

                        this.instance.isOver = 0;
                        this.instance.cancelHelperRemoval = true;

                        //Prevent reverting on this forced stop
                        this.instance.options.revert = false;

                        //The out event needs to be triggered independently
                        this.instance._trigger('out', event, this.instance._uiHash(this.instance));

                        this.instance._mouseStop(event, true);
                        this.instance.options.helper = this.instance.options._helper;

                        //Now we remove our currentItem, the list group clone again, and the placeholder, and animate the helper back to its original size
                        this.instance.currentItem.remove();
                        if (this.instance.placeholder) {
                            this.instance.placeholder.remove();
                        }

                        acym_editorWysidDragDrop.instance._trigger('fromSortable', event);
                        //draggable revert needs that
                        acym_editorWysidDragDrop.instance.dropped = false;
                    }
                });
            },
            stop: function (event, ui, draggable) {
                acym_editorWysidDragDrop.stopAutoScroll();

                //If we are still over the sortable, we fake the stop event of the sortable, but also remove helper
                jQuery.each(acym_editorWysidDragDrop.instance.sortables, function () {
                    if (this.instance.isOver) {
                        this.instance.isOver = 0;

                        // Don't remove the helper in the draggable instance
                        acym_editorWysidDragDrop.instance.cancelHelperRemoval = true;
                        // Remove it in the sortable instance (so sortable plugins like revert still work)
                        this.instance.cancelHelperRemoval = false;

                        // The sortable revert is supported, and we have to set a temporary dropped variable on the draggable to support revert: "valid/invalid"
                        if (this.shouldRevert) {
                            this.instance.options.revert = this.shouldRevert;
                        }

                        // Trigger the stop of the sortable
                        this.instance._mouseStop(event);

                        this.instance.options.helper = this.instance.options._helper;

                        // If the helper has been the original item, restore properties in the sortable
                        if (acym_editorWysidDragDrop.instance.options.helper === 'original') {
                            this.instance.currentItem.css({
                                top: 'auto',
                                left: 'auto'
                            });
                        }
                    } else {
                        // Remove the helper in the sortable instance
                        this.instance.cancelHelperRemoval = false;
                        this.instance._trigger('deactivate', event, uiSortable);
                    }
                });
            }
        });
    },
    handleAutoScroll: function (event) {
        let editorContainer = document.getElementById('acym__wysid__template');
        let containerPositionOnScreen = editorContainer.getBoundingClientRect();
        let editorTop = containerPositionOnScreen.top;
        let editorBottom = containerPositionOnScreen.bottom;

        let scrollTopLimit = ((editorBottom - editorTop) / 10) + editorTop;
        let scrollBottomLimit = ((editorBottom - editorTop) / 10) * 9 + editorTop;

        let mouseVerticalPositionOnScreen = event.clientY;
        if (mouseVerticalPositionOnScreen > editorTop && mouseVerticalPositionOnScreen < scrollTopLimit) {
            if (!acym_editorWysidDragDrop.autoScrollInterval) {
                acym_editorWysidDragDrop.autoScrollInterval = setInterval(function () {
                    if (editorContainer.scrollTop === 0) {
                        acym_editorWysidDragDrop.stopAutoScroll();
                        return;
                    }
                    editorContainer
                        .scrollBy({
                            left: 0,
                            top: -50,
                            behavior: 'smooth'
                        });
                }, 100);
            }
        } else if (mouseVerticalPositionOnScreen < editorBottom && mouseVerticalPositionOnScreen > scrollBottomLimit) {
            if (!acym_editorWysidDragDrop.autoScrollInterval) {
                acym_editorWysidDragDrop.autoScrollInterval = setInterval(function () {
                    if (editorContainer.offsetHeight + editorContainer.scrollTop >= editorContainer.scrollHeight) {
                        acym_editorWysidDragDrop.stopAutoScroll();
                        return;
                    }
                    editorContainer
                        .scrollBy({
                            left: 0,
                            top: 50,
                            behavior: 'smooth'
                        });
                }, 100);
            }
        } else {
            acym_editorWysidDragDrop.stopAutoScroll();
        }
    },
    stopAutoScroll: function () {
        clearInterval(acym_editorWysidDragDrop.autoScrollInterval);
        acym_editorWysidDragDrop.autoScrollInterval = null;
    },
    refreshSortablesInstances: function (draggable, uiSortable) {
        jQuery('#acym__wysid__template').off('scroll').on('scroll', () => {
            // We add a timeout to reload when the user finished scrolling
            clearTimeout(acym_editorWysidDragDrop.currentTimeout);
            acym_editorWysidDragDrop.currentTimeout = setTimeout(function () {
                jQuery(draggable.options.connectToSortable104).each(function () {
                    const sortable = jQuery(this).sortable('instance');
                    if (!sortable || sortable.options.disabled) return;

                    acym_editorWysidDragDrop.instance.sortables.push({
                        instance: sortable,
                        shouldRevert: sortable.options.revert
                    });
                    // Call the sortable's refreshPositions at drag start to refresh the containerCache since the sortable container cache is used in drag
                    // and needs to be up to date (this will ensure it's initialised as well as being kept in step with any changes that might have happened on the page).
                    sortable.refreshPositions();
                    sortable._trigger('activate', event, uiSortable);
                });
            }, 100);
        });
    }
};
