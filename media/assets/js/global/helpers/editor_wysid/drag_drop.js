const acym_editorWysidDragDrop = {
    setNewRowElementDraggableWYSID: function () {
        jQuery('.acym__wysid__row__element--new').draggable({
            cursor: 'move', //Cursor appearance during drag
            helper: 'clone', //Clone the element that has been dragged instead of directly dragging it
            cursorAt: {
                top: 12,
                left: 40
            }, //Cursor position when you start to drag an element
            connectToSortable104: '.acym__wysid__row', //Allows the drag element to be dropped onto this sortables

            revert: function (isValidDrop) {
                //If you drop an element on an invalid position, it returns to its first place with an animation.
                if (!isValidDrop) {
                    acym_editorWysidRowSelector.setRowSelector();
                    return true;
                }
            },

            revertDuration: 300,

            start: function (event, ui) {
                jQuery(ui.helper)
                    .css({
                        'width': jQuery(this).width() + 'px',
                        'opacity': 1
                    });
                jQuery('.acym__wysid__column__element').off('mouseenter mouseleave');
                jQuery('.acym__wysid__row__element').off('mouseenter mouseleave');
            }
        });
    },
    setRowElementSortableWYSID: function () {
        jQuery('.acym__wysid__row').sortable({
            axis: 'y', //If defined, the items can be dragged only horizontally or vertically
            cursorAt: {top: 20}, //Cursor position when you start to drag an element
            scroll: false, //If set to true, the page scrolls when coming to an edge
            placeholder: 'acym__wysid__row__element--placeholder', //A class name that gets applied to the otherwise white space
            handle: '.acym__wysid__row__element__toolbox__move', //Restricts sort start click to this element
            forcePlaceholderSize: true, //Forces the placeholder to have a size
            tolerance: 'intersect', //Specifies which mode to use for testing whether the item being moved is hovering over another item

            start: function (event, ui) {
                jQuery('.acym__wysid__row__selector, .acym__wysid__element__toolbox').remove();
                ui.helper.first().addClass('acym__wysid__row__element--sortable');
                jQuery(ui.placeholder).css({'height': '75px'});
                jQuery('.acym__wysid__column__element').off('mouseenter mouseleave');
                jQuery('.acym__wysid__row__element').off('mouseenter mouseleave');
            },

            stop: function (event, ui) {
                jQuery('.acym__wysid__row__element--sortable').removeClass('acym__wysid__row__element--sortable');

                //Test the item that was dropped for replace it with the desired content
                let $item = ui.item;
                $item.hasClass('acym__wysid__row__element--new--1') ? acym_editorWysidNewRow.addRow1WYSID($item) : $item.hasClass(
                    'acym__wysid__row__element--new--2') ? acym_editorWysidNewRow.addRow2WYSID($item) : $item.hasClass('acym__wysid__row__element--new--3')
                                                                                                        ? acym_editorWysidNewRow.addRow3WYSID($item)
                                                                                                        : $item.hasClass('acym__wysid__row__element--new--4')
                                                                                                          ? acym_editorWysidNewRow.addRow4WYSID($item)
                                                                                                          : $item.hasClass('acym__wysid__row__element--new--5')
                                                                                                            ? acym_editorWysidNewRow.addRow5WYSID($item)
                                                                                                            : $item.hasClass('acym__wysid__row__element--new--6')
                                                                                                              ? acym_editorWysidNewRow.addRow6WYSID($item)
                                                                                                              : true;

                acym_editorWysidDragDrop.setColumnSortableWYSID();
                acym_helperEditorWysid.checkForEmptyTbodyWYSID();
                acym_editorWysidRowSelector.setRowSelector();
            }
        });
    },
    setColumnElementDraggableWYSID: function () {
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
                acym_editorWysidRowSelector.setRowSelector();
            }
        });
    },
    setColumnSortableWYSID: function () {
        jQuery('.acym__wysid__column tbody').sortable({
            scroll: false, //If set to true, the page scrolls when coming to an edge
            handle: '.acym__wysid__column__element__toolbox__move', //Restricts sort start click to this element
            placeholder: 'acym__wysid__column__element--placeholder', //A class name that gets applied to the otherwise white space
            forcePlaceholderSize: true, //Forces the placeholder to have a size
            cursorAt: {
                top: 20,
                left: 50
            }, //Cursor position when you start to drag an element

            stop: function (event, ui) {
                let $defaultStart = jQuery('#acym__wysid__default');
                $defaultStart.closest('.columns').height('auto').find('table').height('auto').find('tbody').height('auto');
                $defaultStart.remove();
                jQuery('.acym__wysid__column__first').removeClass('acym__wysid__column__first');

                //Check the item that was dropped
                let $item = ui.item;

                let plugin = $item.attr('data-plugin');
                if (plugin) {
                    acym_helperEditorWysid.$focusElement = jQuery($item);
                    acym_editorWysidDynamic.openDContentModal(plugin, acym_helperEditorWysid.$focusElement.attr('data-dynamic'));
                } else {
                    if ($item.hasClass('acym__wysid__column__element--new--title')) {
                        acym_editorWysidNewContent.addTitleWYSID($item);
                    } else if ($item.hasClass('acym__wysid__column__element--new--text')) {
                        acym_editorWysidNewContent.addTextWYSID($item);
                    } else if ($item.hasClass('acym__wysid__column__element--new--picture')) {
                        acym_editorWysidNewContent.addMediaWysid($item);
                    } else if ($item.hasClass('acym__wysid__column__element--new--button')) {
                        acym_editorWysidNewContent.addButtonWYSID($item);
                    } else if ($item.hasClass('acym__wysid__column__element--new--follow')) {
                        acym_editorWysidNewContent.addFollowWYSID($item);
                    } else if ($item.hasClass('acym__wysid__column__element--new--share')) {
                        acym_editorWysidNewContent.addShareWYSID($item);
                    } else if ($item.hasClass('acym__wysid__column__element--new--space')) {
                        acym_editorWysidNewContent.addSpaceWYSID($item);
                    } else if ($item.hasClass('acym__wysid__column__element--new--video')) {
                        acym_editorWysidNewContent.addVideoWYSID($item);
                    } else if ($item.hasClass('acym__wysid__column__element--new--separator')) {
                        acym_editorWysidNewContent.addSeparatorWysid($item);
                    } else if ($item.hasClass('acym__wysid__column__element--new--giphy')) {
                        acym_editorWysidNewContent.addGiphyWYSID($item);
                    }
                }
            },

            change: function () {
                acym_helperEditorWysid.checkForEmptyTbodyWYSID();
            },

            out: function () {
                setTimeout(function () {
                    acym_helperEditorWysid.checkForEmptyTbodyWYSID();
                }, 1);
            },

            receive: function (event, ui) {
                if (!ui.item.hasClass('acym__wysid__column__element--new')) {
                    //remove original item
                    ui.item.remove();

                    acym_helperEditorWysid.setColumnRefreshUiWYSID();
                    acym_editorWysidVersioning.setUndoAndAutoSave();
                }
            },
            start: function () {
                jQuery('.acym__wysid__column__element--placeholder').html('');
                jQuery('.acym__wysid__row__selector, .acym__wysid__element__toolbox').remove();
            }
        });
    },
    setNewColumnElementDraggableWYSID: function () {
        let startHeight = 0;
        jQuery('.acym__wysid__column__element--new').draggable({
            cursor: 'move', //Cursor appearance during drag
            helper: 'clone', //Clone the element that has been dragged instead of directly dragging it
            cursorAt: {
                top: 12,
                left: 40
            }, //Cursor position when you start to drag an element
            //appendTo: 'body', //Which element the draggable helper should be appended to while dragging
            connectToSortable104: '.acym__wysid__column > tbody', //Allows the drag element to be dropped onto this sortable

            revert: function (isValidDrop) {
                //If you drop an element on an invalid position, it returns to its first place with an animation.
                if (!isValidDrop) {
                    acym_editorWysidRowSelector.setRowSelector();
                    return true;
                }
            },

            revertDuration: 300,

            start: function (event, ui) {
                jQuery(ui.helper)
                    .css({
                        'width': jQuery(this).width() + 'px',
                        'opacity': 1
                    });
                jQuery('.acym__wysid__column').addClass('acym__wysid__column--drag-start');
                jQuery('.acym__wysid__column__element').off('mouseenter mouseleave');
                jQuery('.acym__wysid__row__element').off('mouseenter mouseleave');

                if (jQuery('#acym__wysid__default').length) {
                    jQuery('#acym__wysid__default__start').hide();
                    let $startDragging = jQuery('#acym__wysid__default__dragging');
                    startHeight = $startDragging.closest('.columns').height();
                    $startDragging.show();
                    $startDragging.closest('#acym__wysid__default')
                                  .attr('height', 'auto')
                                  .closest('.columns')
                                  .height($startDragging.height())
                                  .find('table')
                                  .height($startDragging.height())
                                  .find('tbody')
                                  .height($startDragging.height());
                }
            },

            stop: function () {
                jQuery('.acym__wysid__column').removeClass('acym__wysid__column--drag-start');

                let $default = jQuery('#acym__wysid__default');

                if ($default.length > 0) {
                    $default.find('#acym__wysid__default__start').show();
                    $default.find('#acym__wysid__default__dragging').hide();

                    let $startDefault = jQuery('#acym__wysid__default__start');
                    $startDefault.closest('#acym__wysid__default')
                                 .attr('height', 'auto')
                                 .closest('.columns')
                                 .height($startDefault.height())
                                 .find('table')
                                 .height($startDefault.height())
                                 .find('tbody')
                                 .height($startDefault.height());
                }
            }
        });
    },
    setFixJquerySortableWYSID: function () {
        jQuery.ui.plugin.add('draggable', 'connectToSortable104', {
            start: function (event, ui, draggable) {

                let inst = jQuery(this).data('ui-draggable'), o = inst.options, uiSortable = jQuery.extend({}, ui, {
                    item: inst.element
                });
                inst.sortables = [];
                jQuery(draggable.options.connectToSortable104).each(function () {
                    let sortable = jQuery(this).sortable('instance');
                    if (sortable && !sortable.options.disabled) {
                        inst.sortables.push({
                            instance: sortable,
                            shouldRevert: sortable.options.revert
                        });
                        // Call the sortable's refreshPositions at drag start to refresh the containerCache since the sortable container cache is used in drag
                        // and needs to be up to date (this will ensure it's initialised as well as being kept in step with any changes that might have happened on the page).
                        sortable.refreshPositions();
                        sortable._trigger('activate', event, uiSortable);
                    }
                });

            },
            stop: function (event, ui, draggable) {

                //If we are still over the sortable, we fake the stop event of the sortable, but also remove helper
                let inst = jQuery(this).data('ui-draggable'), uiSortable = jQuery.extend({}, ui, {
                    item: inst.element
                });

                jQuery.each(inst.sortables, function () {
                    if (this.instance.isOver) {

                        this.instance.isOver = 0;

                        // Don't remove the helper in the draggable instance
                        inst.cancelHelperRemoval = true;
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
                        if (inst.options.helper === 'original') {
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

            },
            drag: function (event, ui, draggable) {

                let inst = jQuery(this).data('ui-draggable'), that = this;

                jQuery.each(inst.sortables, function () {
                    let innermostIntersecting = false, thisSortable = this;

                    //Copy over some variables to allow calling the sortable's native _intersectsWith
                    this.instance.positionAbs = inst.positionAbs;
                    this.instance.helperProportions = inst.helperProportions;
                    this.instance.offset.click = inst.offset.click;

                    if (this.instance._intersectsWith(this.instance.containerCache)) {
                        innermostIntersecting = true;
                        jQuery.each(inst.sortables, function () {
                            this.instance.positionAbs = inst.positionAbs;
                            this.instance.helperProportions = inst.helperProportions;
                            this.instance.offset.click = inst.offset.click;
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
                            this.instance.options.helper = function () {
                                return ui.helper[0];
                            };

                            event.target = this.instance.currentItem[0];
                            this.instance._mouseCapture(event, true);
                            this.instance._mouseStart(event, true, true);

                            //Because the browser event is way off the new appended portlet, we modify a couple of variables to reflect the changes
                            this.instance.offset.click.top = inst.offset.click.top;
                            this.instance.offset.click.left = inst.offset.click.left;
                            this.instance.offset.parent.left -= inst.offset.parent.left - this.instance.offset.parent.left;
                            this.instance.offset.parent.top -= inst.offset.parent.top - this.instance.offset.parent.top;

                            inst._trigger('toSortable', event);
                            //draggable revert needs that
                            inst.dropped = this.instance.element;
                            //hack so receive/update callbacks work (mostly)
                            inst.currentItem = inst.element;
                            this.instance.fromOutside = inst;

                        }

                        //Provided we did all the previous steps, we can fire the drag event of the sortable on every draggable drag, when it intersects with the sortable
                        if (this.instance.currentItem) {
                            this.instance._mouseDrag(event);
                        }

                    } else {

                        //If it doesn't intersect with the sortable, and it intersected before,
                        //we fake the drag stop of the sortable, but make sure it doesn't remove the helper by using cancelHelperRemoval
                        if (this.instance.isOver) {

                            this.instance.isOver = 0;
                            this.instance.cancelHelperRemoval = true;

                            //Prevent reverting on this forced stop
                            this.instance.options.revert = false;

                            //The out event needs to be triggered independently
                            this.instance._trigger('out', event, this.instance._uiHash(this.instance));

                            this.instance._mouseStop(event, true);
                            this.instance.options.helper = this.instance.options._helper;

                            //Now we remove our currentItem, the list group clone again, and the placeholder, and animate the helper back to it's original size
                            this.instance.currentItem.remove();
                            if (this.instance.placeholder) {
                                this.instance.placeholder.remove();
                            }

                            inst._trigger('fromSortable', event);
                            //draggable revert needs that
                            inst.dropped = false;
                        }
                    }
                });
            }
        });
    }
};
