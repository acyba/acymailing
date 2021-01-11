const acym_editorWysidDynammic = {
    inCall: false,
    getUniqueId: function ($focusedElement) {
        let uniqueId;
        if ($focusedElement === undefined || $focusedElement.attr('id') === undefined) {
            uniqueId = 'dynamicContent';
            let i = 0;
            while (jQuery('#' + uniqueId + i).length) {
                i++;
            }
            uniqueId += i;
        } else {
            uniqueId = $focusedElement.attr('id');
        }

        return uniqueId;
    },
    endDContentInsertion: function ($focusedElement, shortcode, previewContent, plugin, initEdit) {
        let uniqueId = this.getUniqueId($focusedElement);

        shortcode = shortcode.replace(/"/g, '\\"');
        let style = 'position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;';

        let previewTooltip = '<span class="acym__tooltip acym__dynamics__preview">' + ACYM_JS_TXT.ACYM_PREVIEW;
        previewTooltip += '<span class="acym__tooltip__text wysid_tooltip">' + ACYM_JS_TXT.ACYM_PREVIEW_DESC + '</span>';
        previewTooltip += '</span>';

        if (0 === previewContent.length) {
            previewContent = '<div class="acym_default_dcontent"><span class="acym_default_dcontent_text">'
                             + ACYM_JS_TXT.ACYM_NO_DCONTENT_TEXT
                             + '</span></div>';
        }

        let insertedContent = '<tr id="'
                              + uniqueId
                              + '" class="acym__wysid__column__element ui-draggable" data-dynamic="'
                              + shortcode
                              + '" style="'
                              + style
                              + '" data-plugin="'
                              + plugin
                              + '">';
        insertedContent += '<td class="large-12 acym__wysid__column__element__td">' + previewTooltip;
        insertedContent += previewContent;
        insertedContent += '<div class="plugin_loader" style="display: none;"><i class="fa acymicon-spin acymicon-circle-o-notch">&zwj;</i></div>';
        insertedContent += '<i style="display: none;">&zwj;</i></td></tr>';

        $focusedElement.replaceWith(insertedContent);

        acym_helperEditorWysid.setColumnRefreshUiWYSID();
        acym_editorWysidVersioning.setUndoAndAutoSave(initEdit);
        acym_helperTooltip.setTooltip();

        acym_helperEditorWysid.$focusElement = jQuery('#' + uniqueId);
        acym_helperEditorWysid.$focusElement.find('.plugin_loader').css('display', 'none');
    },
    insertDContent: function (shortcode, $elementsToLoop) {
        let $focusedElement;
        let plugin;
        let initEdit = 'undefined' !== typeof $elementsToLoop;

        if (initEdit) {
            $focusedElement = jQuery($elementsToLoop.get(0));
            $elementsToLoop.splice(0, 1);
            plugin = $focusedElement.attr('data-plugin');
            shortcode = $focusedElement.attr('data-dynamic');
        } else {
            $focusedElement = acym_helperEditorWysid.$focusElement;
            plugin = jQuery('#currentPlugin').val();
        }

        if (0 === shortcode.length) {
            acym_editorWysidDynammic.endDContentInsertion($focusedElement, shortcode, '', plugin, initEdit);
            return;
        }

        acym_helperEditorWysid.dynamicPreviewIdentifier++;
        let currentPreviewIdentifier = acym_helperEditorWysid.dynamicPreviewIdentifier;

        $focusedElement.find('.plugin_loader').css('display', 'flex');
        let mailId = jQuery('input[name="editor_autoSave"]').val();
        let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_dynamics&ctrl=' + acym_helper.ctrlDynamics + '&task=replaceDummy';
        jQuery.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                'mailId': mailId,
                'code': shortcode,
                'language': acym_editorWysidMultilingual.currentLanguage,
                'previewBody': jQuery('#acym__wysid__template').html()
            }
        }).then(function (response) {
            // Another option has been changed, apply the newest only
            if (currentPreviewIdentifier !== acym_helperEditorWysid.dynamicPreviewIdentifier) return;

            let preview;
            if (response) response = acym_helper.parseJson(response);
            if (!response.content || 0 === response.content.length) {
                preview = '';
            } else {
                preview = response.content;
            }
            acym_editorWysidDynammic.endDContentInsertion($focusedElement, shortcode, preview, plugin, initEdit);

            if ('undefined' !== typeof $elementsToLoop && $elementsToLoop.length > 0) acym_editorWysidDynammic.insertDContent('', $elementsToLoop);
        });
    },
    openDContentModal: function (plugin, shortcode) {
        let $pluginsContext = jQuery('#acym__wysid__context__plugins');
        $pluginsContext.html('<i class="acymicon-circle-o-notch acymicon-spin centered_spinner text-center" style="margin-top: 2rem;"/>');
        acym_editorWysidContextModal.showContextModal($pluginsContext);

        jQuery(window).on('mousedown', function (event) {
            if (acym_editorWysidContextModal.clickedOnScrollbar(event.clientX, $pluginsContext)) return;
            let $target = jQuery(event.target);
            if ($target.closest('.c-scrim').length || $target.closest('.c-datepicker--open').length) return false;
            if ($target.closest('tr[data-plugin]').length) return false;

            jQuery(this).off('mousedown');
            acym_editorWysidContextModal.hideContextModal($pluginsContext, $target);
            jQuery(window).unbind('click');
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidVersioning.setUndoAndAutoSave();
        });

        if ('undefined' === typeof shortcode || !shortcode || !shortcode.length) {
            shortcode = '';
        }

        let ajaxURL = ACYM_AJAX_URL;
        ajaxURL += '&page=acymailing_lists';
        ajaxURL += '&action=acymailing_router';
        ajaxURL += '&noheader=1';
        ajaxURL += '&ctrl=' + acym_helper.ctrlDynamics;
        ajaxURL += '&task=trigger';
        ajaxURL += '&trigger=insertionOptions';
        ajaxURL += '&plugin=' + plugin;
        ajaxURL += '&shortcode=' + encodeURIComponent(shortcode);
        ajaxURL += '&campaignId=' + jQuery('#acym__campaign__recipients__form__campaign').val();

        let $campaignType = jQuery('[name="campaign_type"]');
        if ($campaignType.length > 0) ajaxURL += '&campaign_type=' + $campaignType.val();


        jQuery.ajax({
            url: ajaxURL,
            success: function (data) {
                data += '<input type="hidden" id="currentPlugin" name="currentPlugin" value="' + plugin + '"/>';
                $pluginsContext.html(data);

                jQuery('#acym_pagination__ajax__load-more').val(1);
                if (ACYM_IS_ADMIN) acym_helperRadio.setRadioIconsGlobal();
                acym_editorWysidDynammic.setPluginFilters();
                acym_editorWysidDynammic.setPluginPagination();
                // Reload foundation for tabs in plugins popup
                jQuery(document).foundation();
                jQuery('.reveal-overlay').not('#acym_form .reveal-overlay').appendTo('#acym__wysid__context__plugins');
                acym_editorWysidDynammic.selectFirstTab();
                acym_helperDatePicker.setDatePickerGlobal();
                acym_helperTooltip.setTooltip();
                acym_helperDatePicker.setRSDateChoice();
                acym_editorWysidToolbar.setRightToolbarWYSID();
                if (0 === shortcode.length) acym_editorWysidDynammic.insertDContent('');
                jQuery(document).trigger('acym_plugins_installed_loaded');
                acym_editorWysidDynammic.setRefreshCustomViewChanged();
            }
        });
    },
    setPluginFilters: function () {
        jQuery('#plugin_listing_filters select').off('change').on('change', function () {
            acym_editorWysidDynammic.setAjaxDynamic();
        });
        jQuery('#plugin_listing_filters input[type="text"]').off('keyup').on('keyup', function () {
            jQuery('#plugin_listing').off('scroll');
            let search = jQuery(this).val();
            clearTimeout(acym_helperEditorWysid.typingTimer);
            if (search.length >= 2) {
                acym_helperEditorWysid.typingTimer = setTimeout(function () {
                    acym_editorWysidDynammic.setAjaxDynamic();
                }, 1000);
            } else if (search == '') {
                acym_editorWysidDynammic.setAjaxDynamic();
            }
        });

        if (ACYM_IS_ADMIN) {
            jQuery('#acym__wysid__context__plugins select')
                .select2({
                    theme: 'foundation',
                    width: '115px'
                });
        }
    },
    setAjaxDynamic: function (loadMore = false) {
        if (this.inCall) return true;
        this.inCall = true;
        let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_dynamics&ctrl=' + acym_helper.ctrlDynamics + '&task=trigger&trigger=displayListing';

        jQuery('#plugin_listing_filters input, #plugin_listing_filters select').each(function () {
            ajaxUrl += '&' + jQuery(this).attr('name') + '=' + jQuery(this).val();
        });
        let $paginationInput = jQuery('#acym_pagination__ajax__load-more');
        if (loadMore) {
            ajaxUrl += '&loadMore=1';
        } else {
            $paginationInput.val(1);
        }
        ajaxUrl += '&pagination_page_ajax=' + $paginationInput.val();
        ajaxUrl += '&plugin=' + jQuery('input[name="plugin"]').val();


        if (typeof _selectedRows !== 'undefined') {
            let _ids = [];
            for (let key in _selectedRows) {
                if (!_selectedRows.hasOwnProperty(key)) continue;
                _ids.push(key);
            }

            ajaxUrl += '&selected=' + _ids.join(',');
        }

        jQuery.post(ajaxUrl, (response) => {
            const $pluginListing = jQuery('#plugin_listing');
            if (!loadMore) {
                $pluginListing.replaceWith(response);
            } else {
                $pluginListing.find('.acym__loader').remove();
                $pluginListing.append(response);
            }
            this.inCall = false;
            acym_editorWysidDynammic.setPluginPagination();
        });
    },
    setPluginPagination: function () {
        const $pluginListing = jQuery('#plugin_listing');

        //If there is no more elements to show
        if ($pluginListing.find('.acym__listing__empty__load-more').length > 0 || $pluginListing.find('.acym__listing__empty__search__modal').length > 0) {
            return true;
        }

        $pluginListing.on('scroll', function () {
            //We subtract 80 this way the call trigger before the user touch the bottom and he have to wait less time
            const scrollToDo = jQuery(this)[0].scrollHeight - 80;
            const scrollDone = jQuery(this).height() + jQuery(this).scrollTop();

            //if we reach the end we load more entities
            if (scrollDone >= scrollToDo) {
                //once it's done we remove the event listiner on the scroll to prevent calling X times the urls
                jQuery(this).off('scroll');

                //We add the spinner
                jQuery(this).append('<div class="cell text-center acym__loader"><i class="acymicon-spin acymicon-circle-o-notch"></i></div>');

                //We increment the pagination
                let $paginationInput = jQuery('#acym_pagination__ajax__load-more');
                let currentPage = parseInt($paginationInput.val());
                $paginationInput.val(currentPage + 1);

                //We make the ajax call
                acym_editorWysidDynammic.setAjaxDynamic(true);
            }
        });
    },
    selectFirstTab: function () {
        jQuery('.tabs').each(function () {
            let identifier = jQuery(this).attr('id');
            let selectedTab = localStorage.getItem('acy' + identifier);

            let $lastSelected = jQuery('#' + identifier).find('a[data-tab-identifier="' + selectedTab + '"]');
            if ($lastSelected.length) {
                $lastSelected.click();
            } else {
                let $defaultTab = jQuery('#' + identifier).find('a[data-selected="true"]');
                if ($defaultTab.length) {
                    $defaultTab.click();
                } else {
                    jQuery('#' + identifier + ' .acym_tab:first').click();
                }
            }
        });
    },
    setDynamicsActions: function () {
        jQuery('.acym_dynamic').off('click').on('click', function () {
            let selection = window.getSelection();
            let range = document.createRange();
            range.selectNode(this);
            selection.removeAllRanges();
            selection.addRange(range);

            jQuery('#acym__wysid__modal__dynamic-text__ui__iframe').contents().find('input[name="dtextcode"]').val('');
            jQuery('#acym__wysid__modal__dynamic-text').show();
        });

        jQuery('.acym_remove_dynamic').off('click').on('click', function () {
            jQuery(this).closest('span').remove();
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidVersioning.setUndoAndAutoSave();
        });

        jQuery('tr[data-dynamic]').off('click').on('click', function () {
            acym_helperEditorWysid.$focusElement = jQuery(this);
            acym_editorWysidDynammic.openDContentModal(jQuery(this).attr('data-plugin'), jQuery(this).attr('data-dynamic'));
        });
    },
    setTagPWordBreak: function () {
        jQuery('#acym__wysid__template').find('p').css('word-break', 'break-word');
    },
    setTagPreInserted: function () {
        let userAgent = navigator.userAgent;
        if (userAgent.toLowerCase().indexOf('chrome') > -1 || userAgent.toLowerCase().indexOf('firefox') > -1) {
            jQuery('p').find('code').css('white-space', 'pre-wrap');
            jQuery('pre').css('white-space', 'pre-wrap');
            jQuery('code').css('white-space', 'pre-wrap');
        } else if (userAgent.indexOf('MSIE ') > -1 || userAgent.indexOf('Trident/') > -1) {
            jQuery('pre').css('word-wrap', 'break-word');
            jQuery('code').css('word-wrap', 'break-word');
        } else if (userAgent.toLowerCase().indexOf('opera') > -1) {
            jQuery('pre').css('white-space', '-o-pre-wrap');
            jQuery('code').css('white-space', '-o-pre-wrap');
        }
    },
    setRefreshCustomViewChanged: function () {
        jQuery(document).off('acym_custom_view_modal_closed').on('acym_custom_view_modal_closed', function () {
            let functionName = document.getElementById('acym__dynamic__update__function').value;
            if (typeof window[functionName] === 'function') window[functionName]();
        });
    }
};
