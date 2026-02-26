const acym_editorWysidDynamic = {
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
    endDContentInsertion: function ($focusedElement, shortcode, previewContent, plugin, customView = false) {
        let uniqueId = this.getUniqueId($focusedElement);

        shortcode = shortcode.replace(/"/g, '\\"');
        let style = 'position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;';

        let previewTooltip = '<span class="acym__tooltip acym__dynamics__preview">' + ACYM_JS_TXT.ACYM_PREVIEW;
        previewTooltip += '<span class="acym__tooltip__text wysid_tooltip">' + ACYM_JS_TXT.ACYM_PREVIEW_DESC + '</span>';
        previewTooltip += '</span>';

        if (customView) {
            previewTooltip = `<span class="acym__tooltip acym__dynamics__preview">
                                  ${ACYM_JS_TXT.ACYM_PREVIEW_CUSTOM_VIEW}
                                  <span class="acym__tooltip__text wysid_tooltip">${ACYM_JS_TXT.ACYM_PREVIEW_DESC}<br>${ACYM_JS_TXT.ACYM_CUSTOM_VIEW_EDITOR_DESC}</span>
                               </span>`;
        }

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
        insertedContent += '<div class="plugin_loader" style="display: none;"><i class="acymicon-spin acymicon-circle-o-notch">&zwj;</i></div>';
        insertedContent += '<i style="display: none;">&zwj;</i></td></tr>';

        $focusedElement.replaceWith(insertedContent);

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
            acym_editorWysidDynamic.endDContentInsertion($focusedElement, shortcode, '', plugin);

            acym_helperEditorWysid.setColumnRefreshUiWYSID(true, initEdit);
            acym_editorWysidRowSelector.setZoneAndBlockOverlays();
            acym_helperTooltip.setTooltip();
            acym_editorWysidDynamic.setDContentActions();
            return;
        }

        acym_helperEditorWysid.dynamicPreviewIdentifier++;
        const currentPreviewIdentifier = acym_helperEditorWysid.dynamicPreviewIdentifier;

        $focusedElement.find('.plugin_loader').css('display', 'flex');
        const mailId = jQuery('input[name="editor_autoSave"]').val();
        const ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_dynamics&ctrl=' + acym_helper.ctrlDynamics + '&task=replaceDummy';
        const previewHtml = jQuery('#acym__wysid__template').html();

        let encodedPreview = new TextEncoder().encode(previewHtml).reduce((data, byte) => data + String.fromCharCode(byte), '');
        encodedPreview = btoa(encodedPreview);

        jQuery.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                mailId: mailId,
                code: shortcode,
                language: acym_editorWysidVersions.currentVersion,
                previewBody: encodedPreview
            }
        }).then(function (response) {
            // Another option has been changed, apply the newest only
            if (currentPreviewIdentifier !== acym_helperEditorWysid.dynamicPreviewIdentifier) {
                return;
            }

            let preview;
            let customView = false;
            if (response) {
                response = acym_helper.parseJson(response);
            }
            if (!response.data.content || 0 === response.data.content.length) {
                preview = '';
            } else {
                preview = response.data.content;
                customView = response.data.custom_view;
            }
            acym_editorWysidDynamic.endDContentInsertion($focusedElement, shortcode, preview, plugin, customView);

            if ('undefined' !== typeof $elementsToLoop && $elementsToLoop.length > 0) {
                acym_editorWysidDynamic.insertDContent('', $elementsToLoop);
            } else {
                acym_helperEditorWysid.setColumnRefreshUiWYSID(true, initEdit);
                acym_editorWysidRowSelector.setZoneAndBlockOverlays();
                acym_helperTooltip.setTooltip();
                acym_editorWysidFontStyle.applyCssOnAllElementTypesBasedOnSettings();
                acym_editorWysidDynamic.setDContentActions();
            }
        });
    },
    openDContentOptions: function (plugin, shortcode) {
        let $pluginsOptionsContainer = jQuery('#acym__wysid__context__plugins');
        $pluginsOptionsContainer.html('<i class="acymicon-circle-o-notch acymicon-spin centered_spinner text-center" style="margin-top: 2rem;"/>');
        acym_editorWysidContextModal.showBlockOptions($pluginsOptionsContainer);

        jQuery(window).on('mousedown', function (event) {
            if (acym_editorWysidContextModal.clickedOnRightToolbar(event)) return;
            let $target = jQuery(event.target);
            if ($target.closest('.c-scrim').length || $target.closest('.c-datepicker--open').length) return false;
            if ($target.closest('tr[data-plugin]').length) return false;

            jQuery(window).off('mousedown');
            acym_editorWysidContextModal.hideBlockOptions($pluginsOptionsContainer, $target);
        });

        if ('undefined' === typeof shortcode || !shortcode || !shortcode.length) {
            shortcode = '';
        }

        const followupTrigger = jQuery('#acym__followup__trigger').val();

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
        if (followupTrigger) {
            ajaxURL += '&followupTrigger=' + followupTrigger;
        }
        if (!acym_helper.empty(acym_editorWysidVersions)) {
            ajaxURL += '&language=' + acym_editorWysidVersions.selectedVersion;
        }

        let $campaignType = jQuery('[name="campaign_type"]');
        if ($campaignType.length > 0) {
            ajaxURL += '&campaign_type=' + $campaignType.val();
        }

        jQuery.ajax({
            url: ajaxURL,
            success: function (data) {
                // Add the currently edited plugin type to use it when inserting the preview content
                data += '<input type="hidden" id="currentPlugin" name="currentPlugin" value="' + plugin + '"/>';
                $pluginsOptionsContainer.html(data);

                // We just added the options in the container, activate the needed JS on it

                // Init the right toolbar sliding parts
                acym_editorWysidToolbar.setRightToolbarWYSID();
                // Init radio button options
                acym_helperRadio.setRadioIconsGlobal();
                // Init search and category fields
                acym_editorWysidDynamic.setPluginFilters();
                // Init infinite scroll on content insertion
                jQuery('#acym_pagination__ajax__load-more').val(1);
                acym_editorWysidDynamic.setPluginPagination();
                acym_editorWysidDynamic.setPluginTabs();
                // Init date fields for event plugins or auto-campaigns
                acym_helperDatePicker.setDatePickerGlobal();
                jQuery('.reveal-overlay').not('#acym_form .reveal-overlay').appendTo('#acym__wysid__context__plugins');
                acym_helperDatePicker.setRSDateChoice();
                // Init tooltips for auto-campaign and custom view options
                acym_helperTooltip.setTooltip();
                // Init the format buttons choices
                acym_helper.setButtonRadio();

                // Handle custom view with vuejs
                jQuery(document).trigger('acym_plugins_installed_loaded');
                acym_editorWysidDynamic.setRefreshCustomViewChanged();

                // We just dropped the block, display the default preview
                if (0 === shortcode.length) {
                    acym_editorWysidDynamic.insertDContent('');
                }
            }
        });
    },
    setPluginFilters: function () {
        jQuery('#plugin_listing_filters select').off('change').on('change', function () {
            acym_editorWysidDynamic.setAjaxDynamic();
        });
        jQuery('#plugin_listing_filters input[type="text"]').off('keyup').on('keyup', function () {
            jQuery('#plugin_listing').off('scroll');
            let search = jQuery(this).val();
            clearTimeout(acym_helperEditorWysid.typingTimer);
            if (search.length >= 2) {
                acym_helperEditorWysid.typingTimer = setTimeout(function () {
                    acym_editorWysidDynamic.setAjaxDynamic();
                }, 1000);
            } else if (search == '') {
                acym_editorWysidDynamic.setAjaxDynamic();
            }
        });

        if (ACYM_IS_ADMIN) {
            jQuery('#acym__wysid__context__plugins select')
                .select2({
                    theme: 'foundation',
                    width: '160px'
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


        // _selectedRows is modified by the add-ons directly
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
            acym_editorWysidDynamic.setPluginPagination();
        });
    },
    setPluginPagination: function () {
        const $pluginListing = jQuery('#plugin_listing');

        // If there are no more elements to show
        if ($pluginListing.find('.acym__listing__empty__load-more').length > 0 || $pluginListing.find('.acym__listing__empty__search__modal').length > 0) {
            return true;
        }

        $pluginListing.on('scroll', function () {
            //We subtract 80 this way the call trigger before the user touch the bottom and he have to wait less time
            const scrollToDo = jQuery(this)[0].scrollHeight - 80;
            const scrollDone = jQuery(this).height() + jQuery(this).scrollTop();

            //if we reach the end we load more entities
            if (scrollDone >= scrollToDo) {
                //once it's done we remove the event listener on the scroll to prevent calling X times the urls
                jQuery(this).off('scroll');

                //We add the spinner
                jQuery(this).append('<div class="cell text-center acym__loader"><i class="acymicon-spin acymicon-circle-o-notch"></i></div>');

                //We increment the pagination
                let $paginationInput = jQuery('#acym_pagination__ajax__load-more');
                let currentPage = parseInt($paginationInput.val());
                $paginationInput.val(currentPage + 1);

                //We make the ajax call
                acym_editorWysidDynamic.setAjaxDynamic(true);
            }
        });
    },
    setPluginTabs: function () {
        // Reload foundation for tabs in plugins popup
        jQuery(document).foundation();

        jQuery('.tabs').each(function () {
            let identifier = jQuery(this).attr('id');
            let selectedTab = localStorage.getItem('acy' + identifier);

            let $tabsContainer = jQuery('#' + identifier);
            let $lastSelected = $tabsContainer.find('a[data-tab-identifier="' + selectedTab + '"]');
            if ($lastSelected.length) {
                $lastSelected.trigger('click');
            } else {
                let $defaultTab = $tabsContainer.find('a[data-selected="true"]');
                if ($defaultTab.length) {
                    $defaultTab.trigger('click');
                } else {
                    $tabsContainer.find('.acym_tab:first').trigger('click');
                }
            }
        });
    },
    setDTextActions: function () {
        jQuery('.acym_dynamic').off('click').on('click', function (event) {
            let selection = window.getSelection();
            selection.removeAllRanges();

            let range = document.createRange();
            range.selectNode(this);
            selection.addRange(range);

            jQuery('#dtextcode').val('');
        });

        jQuery('.acym_remove_dynamic').off('click').on('click', function () {
            jQuery(this).closest('span').remove();
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
        });
    },
    setDContentActions: function () {
        jQuery('tr[data-dynamic]').off('click').on('click', function () {
            acym_helperEditorWysid.$focusElement = jQuery(this);
            acym_editorWysidDynamic.openDContentOptions(jQuery(this).attr('data-plugin'), jQuery(this).attr('data-dynamic'));
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
    },
    setDTexts: function () {
        // We hide the real tabs system and show another one above
        jQuery('#dtext_options').hide();

        jQuery('#dtextcode').on('click', function () {
            const $input = jQuery(this);
            const shortcode = $input.val();

            $input.trigger('select');

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(shortcode);
            }
        });
    }
};

function setTag(tagvalue, element) {
    const $allRows = jQuery('.acym__listing__row__popup');
    $allRows.removeClass('selected_row');
    element.addClass('selected_row');
    window.document.getElementById('dtextcode').value = tagvalue;
}
