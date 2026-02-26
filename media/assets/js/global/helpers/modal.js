const acym_helperModal = {
    isMultilingualEdition: jQuery('#acym__wysid__edit__versions').length > 0,
    initModal: function () {
        //global
        acym_helperModal.setPopupIframeToggleGlobal();

        //Lists
        acym_helperModal.setShowSelectedOrShowAllListsModalPaginationLists();
        acym_helperModal.setButtonModalPaginationLists();
        acym_helperModal.setSearchListModalPaginationLists();
        acym_helperModal.getContentAjaxModalPaginationLists();

        //Users
        acym_helperModal.setButtonModalPaginationUsers();
        acym_helperModal.setShowSelectedOrShowAllUsersModalPaginationUsers();
        acym_helperModal.setSearchUserModalPaginationUsers();
        acym_helperModal.setButtonConfirmModalPaginationUsers();
        acym_helperModal.initOverlay();
    },
    initOverlay: function () {
        /**
         * Move foundation's modal inside our AcyMailing wrapper (for foundation css)
         */
        if (jQuery('#acym_form').length && !jQuery('#acym__editor__content').length && !jQuery('.campaigns_edit_email').length && !jQuery(
            '.frontcampaigns_edit_email').length) {
            jQuery('.reveal-overlay').appendTo('#acym_form');
        } else {
            jQuery('.reveal-overlay').appendTo('#acym_wrapper');
        }
    },
    setResetMail: function () {
        jQuery('.acym__automation__action__reset__mail').off('click').on('click', function () {
            if (acym_helper.confirm(ACYM_JS_TXT.ACYM_ARE_YOU_SURE)) {
                let $input = jQuery(this).closest('.acym__automation__inserted__action').find('[name$="[mail_id]"]');
                let $html = jQuery(this).parent();
                let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_mails&ctrl=' + acym_helper.ctrlMails + '&task=deleteMailAutomation&id=' + $input.val();
                jQuery.post(ajaxUrl, function (res) {
                    $input.val('').prev().html(ACYM_JS_TXT.ACYM_CREATE_MAIL);
                    $html.html('');
                    acym_helperModal.setAjaxCallStartFrom();
                });
            }
        });
    },
    setAjaxCallStartFrom: function () {
        let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_mails&ctrl=' + acym_helper.ctrlMails + '&task=getTemplateAjax';

        let $returnInput = jQuery('input[name="return"]');
        let automation = ($returnInput.length > 0 && $returnInput.val().indexOf('automation') !== -1) || jQuery('#acym__automation__actions__json').length > 0
                         ? '1'
                         : '0';
        let mailId = jQuery('input[name="id"]').val();
        let $followupinput = jQuery('input[name="followup[id]"]');
        if ($followupinput.length > 0) {
            ajaxUrl += '&followup_id=' + $followupinput.val();
        }

        if (this.isMultilingualEdition) {
            ajaxUrl += '&is_multilingual_edition=1';
        }

        ajaxUrl += '&search=' + jQuery('#acym_search_template_choose__ajax').val();
        ajaxUrl += '&tag=' + jQuery('#acym_tag_template_choose__ajax').val();
        ajaxUrl += '&type=' + jQuery('#acym__mail__type').val();
        ajaxUrl += '&pagination_page_ajax=' + jQuery('#acym_pagination__ajax').val();
        ajaxUrl += '&editor=' + jQuery('#acym__mail__edit__editor').val();
        ajaxUrl += '&automation=' + automation;
        ajaxUrl += '&inmail=' + (jQuery('#editor_autoSave').length > 0 ? '1' : '0');
        ajaxUrl += '&id=' + (undefined === mailId ? 0 : mailId);
        ajaxUrl += '&acym_pagination_element_per_page=' + jQuery('[name="acym_pagination_element_per_page"]').val();
        if ($returnInput.length >= 1) ajaxUrl += '&return=' + encodeURIComponent($returnInput.val());
        if (jQuery('#acym__mail__list-id').length > 0) {
            ajaxUrl += '&list_id=' + jQuery('#acym__mail__list-id').val();
        }

        jQuery.post(ajaxUrl, function (response) {
            jQuery('.acym__template__choose__ajax').html(response);
            acym_helperModal.setSearchAjaxModalChooseTemplateStartFrom();
            acym_helperModal.setPaginationAjaxStartFrom();
            acym_helperModal.setStartFromHtmlEditor();
            if (acym_helperModal.isMultilingualEdition) acym_editorWysidVersions.setClickStartFromTemplate();
            if (jQuery('#acym__automation__actions').length > 0) acym_helperModal.chooseOneTemplate();
        });
    },
    setPaginationAjaxStartFrom: function () {
        jQuery('.acym__template__choose__ajax .acym__pagination__page__ajax').off('click').on('click', function (e) {
            e.preventDefault();
            jQuery('#acym_pagination__ajax').attr('value', jQuery(this).attr('page'));
            acym_helperModal.setAjaxCallStartFrom();
        });

        if (ACYM_IS_ADMIN) {
            jQuery('[name="acym_pagination_element_per_page"]')
                .select2({
                    theme: 'foundation',
                    width: '100%'
                });
        }

        jQuery('#acym_pagination__ajax, [name="acym_pagination_element_per_page"]').on('change', function () {
            acym_helperModal.setAjaxCallStartFrom();
        });
    },
    setSearchAjaxModalChooseTemplateStartFrom: function () {
        acym_helperModal.setSearchValueStartFrom();
        acym_helperModal.setSelectTagStartFrom();
        acym_helperModal.setChoosenTypeStartFrom();
    },
    setStartFromHtmlEditor: function () {
        if (jQuery('#acym__mail__edit__editor').val() !== 'acyEditor' && jQuery('#acym__automation__actions').length < 1) {
            jQuery('.reveal .acym__templates__oneTpl').on('click', function () {
                window.location.replace(jQuery(this).find('a').attr('href'));
            });
            jQuery('.acym__templates__choose__ribbon').on('click', function () {
                window.location.replace(jQuery(this).closest('.acym__templates__pic').find('a').attr('href'));
            });
        }
    },
    chooseOneTemplate: function () {
        jQuery('.acym__templates__oneTpl').off('click').on('click', function () {
            let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_mails&ctrl=' + acym_helper.ctrlMails + '&task=duplicateMailAutomation&id=' + jQuery(this)
                .attr('id');

            let $modal = jQuery('#acym__template__choose__modal');
            let $actionContainer = jQuery('[data-modal-name="' + $modal.attr('data-button') + '"]').closest('.acym__automation__inserted__action');

            ajaxUrl += '&previousId=' + $actionContainer.find('[name$="[mail_id]"]').val();

            jQuery.post(ajaxUrl, function (res) {
                res = acym_helper.parseJson(res, {
                    'error': true,
                    'message': acym_helper.sprintf(ACYM_JS_TXT.ACYM_NOT_FOUND, ACYM_JS_TXT.ACYM_EMAIL)
                });
                if (res.error) {
                    alert(res.message);
                    return false;
                }

                $actionContainer.find('.acym__automation__action__mail__name')
                                .html(res.data.newMail.name
                                      + '<i class="cursor-pointer acymicon-close acym__color__red acym__automation__action__reset__mail margin-left-1"></i>');
                $actionContainer.find('[name$="[mail_id]"]').val(res.data.newMail.id);
                $actionContainer.find('[data-task="createMail"]').html(ACYM_JS_TXT.ACYM_EDIT_MAIL);
                jQuery('.reveal').foundation('close');
                acym_helperModal.setResetMail();
            });
        });
    },
    setSearchValueStartFrom: function () {
        let typingTimer = null;
        jQuery('input[name="mailchoose_search__ajax"]').off('keydown').on('keydown', function (event) {
            let searchList = jQuery(this);
            let searchValue = searchList.val();
            if ((searchValue || event.key === 'Backspace') && !acym_helper.empty(searchValue) && searchValue.length >= 2) {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(function () {
                    jQuery('#acym_search_template_choose__ajax').attr('value', searchValue);
                    acym_helperModal.setAjaxAndResetPaginationStartFrom();
                }, 1500);
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(typingTimer);
                jQuery('#acym_search_template_choose__ajax').attr('value', searchValue);
                acym_helperModal.setAjaxAndResetPaginationStartFrom();
                return false;
            }
            if (event.key === 'Backspace' && searchList.val() == '') {
                clearTimeout(typingTimer);
                jQuery('#acym_search_template_choose__ajax').attr('value', '');
                acym_helperModal.setAjaxAndResetPaginationStartFrom();
            }
        });

        jQuery('#acym__template__choose__modal .acym__search__button').off('click').on('click', function (e) {
            e.preventDefault();
            clearTimeout(typingTimer);
            jQuery('#acym_search_template_choose__ajax').attr('value', jQuery('input[name="mailchoose_search__ajax"]').attr('value'));
            acym_helperModal.setAjaxAndResetPaginationStartFrom();
        });
    },
    setAjaxAndResetPaginationStartFrom: function () {
        jQuery('#acym_pagination__ajax').attr('value', 1);
        acym_helperModal.setAjaxCallStartFrom();
    },
    setSelectTagStartFrom: function () {
        jQuery('#mailchoose_tag__ajax').on('change', function (e) {
            jQuery('#acym_tag_template_choose__ajax').attr('value', jQuery(this).val());
            acym_helperModal.setAjaxAndResetPaginationStartFrom();
        });
    },
    setChoosenTypeStartFrom: function () {
        jQuery('#acym__template__choose__modal .acym__type__choosen').off('click').on('click', function (e) {
            jQuery('[id^="acym__type-template"]').val(jQuery(this).attr('data-type'));
            jQuery('.acym__type__choosen').removeClass('is-active');
            jQuery(this).addClass('is-active').blur();
            acym_helperModal.setAjaxAndResetPaginationStartFrom();
        });
    },
    setTemplateModal: function (inAutomation) {
        inAutomation = inAutomation !== undefined;
        acym_helperModal.setAjaxAndResetPaginationStartFrom();
        acym_helperModal.setPaginationAjaxStartFrom();
        if (acym_helperModal.isMultilingualEdition) acym_editorWysidVersions.setClickStartFromTemplate();
        acym_helperModal.setClearButtonStartFrom();
        if (inAutomation) {
            jQuery('[data-open="acym__template__choose__modal"]').on('click', function () {
                jQuery('#acym__template__choose__modal').attr('data-button', jQuery(this).attr('data-modal-name'));
            });
        }
    },
    setClearButtonStartFrom: function () {
        jQuery('.modal__pagination__search .acym__search-clear').off('click').on('click', function () {
            jQuery('input[name="mailchoose_search__ajax"]').val('');
            jQuery('#acym_search_template_choose__ajax').val('');
            acym_helperModal.setAjaxAndResetPaginationStartFrom();
        });
    },
    setPopupIframeToggleGlobal: function () {
        jQuery('[data-iframe]').on('click', function () {
            let $popup = jQuery('#' + jQuery(this).attr('data-open'));
            let $reload = jQuery(this).attr('data-reload');
            let ajax = jQuery(this).attr('data-ajax');
            let classIframe = jQuery(this).attr('data-iframe-class');
            let urlIframe = ajax === 'true' ? ACYM_AJAX_URL + jQuery(this).attr('data-iframe') : jQuery(this).attr('data-iframe');
            $popup.prepend('<iframe src="' + urlIframe + '"></iframe>');
            if (classIframe !== undefined) {
                $popup.find('iframe').addClass(classIframe);
            }
            $popup.find('iframe').on('load', function () {
                jQuery(this).contents().find('#wpadminbar').remove();
            });
            $popup.find('.close-button').on('click', function () {
                jQuery(this).siblings('iframe').remove();
                if ($reload) {
                    location.reload();
                }
            });

            jQuery('.reveal-overlay').on('click', function () {
                $popup.find('iframe').remove();
                if ($reload) {
                    location.reload();
                }
            });
        });
    },
    setShowSelectedOrShowAllListsModalPaginationLists: function () {

        let buttonShowSelected = jQuery('.modal__pagination__show-selected');
        let buttonShowAll = jQuery('.modal__pagination__show-all');

        if (buttonShowSelected.hasClass('selected')) {
            buttonShowAll.hide();
            buttonShowSelected.show();
        } else {
            buttonShowAll.show();
            buttonShowSelected.hide();
        }

        jQuery('.modal__pagination__show-button').off('click').on('click', function () {

            if (buttonShowSelected.hasClass('selected')) {
                buttonShowAll.show();
                buttonShowSelected.hide().removeClass('selected');
                jQuery('#modal__pagination__show-information').attr('value', 'true');
                jQuery('#acym_pagination__ajax').attr('value', 1);
            } else {
                buttonShowAll.hide();
                buttonShowSelected.show().addClass('selected');
                jQuery('#modal__pagination__show-information').attr('value', 'false');
                jQuery('#acym_pagination__ajax').attr('value', 1);
            }

            acym_helperModal.getContentAjaxModalPaginationLists();
        });
    },
    getContentAjaxModalPaginationLists: function () {
        const container = jQuery('.modal__pagination__listing__lists__in-form');
        if (container.length === 0) {
            return;
        }

        let ajaxURL = ACYM_AJAX_URL
                      + '&page=acymailing_lists&ctrl='
                      + acym_helper.ctrlLists
                      + '&action=acymailing_router&noheader=1&task=setAjaxListing&listsPerPage=10';

        ajaxURL += '&pagination_page_ajax=' + jQuery('#acym_pagination__ajax').val();
        ajaxURL += '&selectedLists=' + jQuery('#acym__modal__lists-selected').val();
        ajaxURL += '&alreadyLists=' + jQuery('#acym__user__lists_already_add').val();
        ajaxURL += '&show_selected=' + jQuery('#modal__pagination__show-information').val();
        ajaxURL += '&search_lists=' + jQuery('#modal__pagination__search__lists').val();
        ajaxURL += jQuery('#modal__pagination__need__display__sub').length > 0 ? '&needDisplaySub=1&nonActive=1' : '&needDisplaySub=0&nonActive=0';

        jQuery.get(ajaxURL, function (response) {
            response = acym_helper.parseJson(response);
            jQuery('#modal__pagination__search__spinner').hide();

            if (response.error) {
                return;
            }

            container.html(response.data.paginationListing);
            acym_helperModal.setAjaxPaginationModalPagination();
            acym_helperModal.setListingAjaxUserModalPaginationLists();
            acym_helperModal.setShowSelectedOrShowAllListsModalPaginationLists();
            acym_helperModal.setSearchListModalPaginationLists();
        });
    },
    setAjaxPaginationModalPagination: function () {
        jQuery('.acym__pagination__page__ajax').off('click').on('click', function () {
            jQuery('#acym_pagination__ajax').val(jQuery(this).attr('page'));
            acym_helperModal.getContentAjaxModalPaginationLists();
        });
    },
    setListingAjaxUserModalPaginationLists: function () {

        jQuery('.modal__pagination__listing__lists__list--checkbox').off('change').on('change', function () {
            let spanName = jQuery(this).next().children('label .modal__pagination__listing__lists__list-name ');
            let inputListsSelected = jQuery('#acym__modal__lists-selected');
            let listsSelectedToInject = inputListsSelected.val();
            let listsSelected = [];
            listsSelectedToInject = listsSelectedToInject === '' ? [] : acym_helper.parseJson(listsSelectedToInject);
            if (jQuery(this).is(':checked')) {
                spanName.addClass('acym__color__blue');
            } else {
                spanName.removeClass('acym__color__blue');
                listsSelectedToInject.splice(listsSelectedToInject.indexOf(parseInt(jQuery(this).val())), 1);
            }

            jQuery('.modal__pagination__listing__lists__list--checkbox:checked').each(function () {
                listsSelected.push(parseInt(jQuery(this).val()));
            });

            jQuery.each(listsSelected, function (index) {
                if (jQuery.inArray(listsSelected[index], listsSelectedToInject) === -1) {
                    listsSelectedToInject.push(listsSelected[index]);
                }
            });
            inputListsSelected.val(JSON.stringify(listsSelectedToInject));

            jQuery('#acym__campaigns__recipients__event_on_change_count_recipients').trigger('change');
            jQuery('#acym__popup__subscription__change').trigger('change');
        });
    },
    setSearchListModalPaginationLists: function () {
        let typingTimer = null;
        let $inputSearch = jQuery('input[name="modal_search_lists"]');
        let $modalShowSelected = jQuery('.modal__pagination__show');
        let $listsSearchInput = jQuery('#modal__pagination__search__lists');

        $inputSearch.off('keydown').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                acym_helperModal.setSearchLists(typingTimer);
            }
        });

        $inputSearch.off('keyup').on('keyup', function (event) {
            let searchList = jQuery(this);
            let searchValue = searchList.val();
            if ((searchValue || event.key === 'Backspace') && searchValue != '' && searchValue.length >= 2) {
                jQuery('#modal__pagination__search__spinner').show();
                $modalShowSelected.hide();
                clearTimeout(typingTimer);
                typingTimer = setTimeout(function () {
                    $listsSearchInput.attr('value', searchValue);
                    jQuery('#acym_pagination__ajax').attr('value', 1);
                    acym_helperModal.getContentAjaxModalPaginationLists();
                }, 1000);

            } else {
                clearTimeout(typingTimer);
            }

            if (searchList.val() == '') {
                clearTimeout(typingTimer);
                $modalShowSelected.show();
                $listsSearchInput.attr('value', '');
                jQuery('#acym_pagination__ajax').attr('value', 1);
                acym_helperModal.getContentAjaxModalPaginationLists();
            }
        });

        jQuery('.modal__pagination__search .acym__search__button').off('click').on('click', function (e) {
            e.preventDefault();
            acym_helperModal.setSearchLists(typingTimer);
        });

        jQuery('.modal__pagination__search .acym__search-clear').off('click').on('click', function (e) {
            e.preventDefault();
            clearTimeout(typingTimer);
            $listsSearchInput.attr('value', '');
            $inputSearch.attr('value', '');
            $modalShowSelected.show();
            acym_helperModal.getContentAjaxModalPaginationLists();
        });
    },
    setShowSelectedOrShowAllUsersModalPaginationUsers: function () {
        let buttonShowSelected = jQuery('.modal__pagination__users__show-selected');
        let buttonShowAll = jQuery('.modal__pagination__users__show-all');

        if (buttonShowSelected.hasClass('selected')) {
            buttonShowAll.hide();
            buttonShowSelected.show();
        } else {
            buttonShowAll.show();
            buttonShowSelected.hide();
        }

        jQuery('.modal__pagination__users__show-button').off('click').on('click', function () {

            if (buttonShowSelected.hasClass('selected')) {
                buttonShowAll.show();
                buttonShowSelected.hide().removeClass('selected');
                jQuery('#modal__pagination__users__show-information').attr('value', 'true');
                jQuery('#acym_pagination__ajax').attr('value', 1);
            } else {
                buttonShowAll.hide();
                buttonShowSelected.show().addClass('selected');
                jQuery('#modal__pagination__users__show-information').attr('value', 'false');
                jQuery('#acym_pagination__ajax').attr('value', 1);
            }
        });
    },
    setSearchUserModalPaginationUsers: function () {
        let typingTimer = null;
        let $inputSearch = jQuery('input[name="modal_search_users"]');
        let $modalShowSelected = jQuery('.modal__pagination__show');
        let $userSearchInput = jQuery('#modal__pagination__users__search__input');

        $inputSearch.off('keydown').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                acym_helperModal.setSearchUsers(typingTimer);
            }
        });

        $inputSearch.off('keyup').on('keyup', function (event) {
            let searchValue = jQuery(this).val();
            if ((searchValue || event.key === 'Backspace') && searchValue != '' && searchValue.length >= 2) {
                jQuery('#modal__pagination__users__search__spinner').show();
                $modalShowSelected.hide();
                clearTimeout(typingTimer);
                typingTimer = setTimeout(function () {
                    $userSearchInput.attr('value', searchValue);
                    jQuery('#acym_pagination__ajax').attr('value', 1);
                }, 1000);

            } else {
                clearTimeout(typingTimer);
            }

            if (searchValue == '') {
                clearTimeout(typingTimer);
                $modalShowSelected.show();
                $userSearchInput.attr('value', '');
                jQuery('#acym_pagination__ajax').attr('value', 1);
            }
        });

        jQuery('.modal__pagination__users__search .acym__search__button').off('click').on('click', function (e) {
            e.preventDefault();
            acym_helperModal.setSearchUsers(typingTimer);
        });

        jQuery('.modal__pagination__users__search .acym__search-clear').off('click').on('click', function (e) {
            e.preventDefault();
            clearTimeout(typingTimer);
            $userSearchInput.attr('value', '');
            $inputSearch.attr('value', '');
            $modalShowSelected.show();
        });
    },
    setSearchUsers: function (typingTimer) {
        clearTimeout(typingTimer);
        jQuery('.modal__pagination__show').hide();
        jQuery('#acym_pagination__ajax').attr('value', 1);
        jQuery('#modal__pagination__users__search__input').attr('value', jQuery('input[name="modal_search_users"]').attr('value'));
    },
    setSearchLists: function (typingTimer) {
        clearTimeout(typingTimer);
        jQuery('.modal__pagination__show').hide();
        jQuery('#acym_pagination__ajax').attr('value', 1);
        jQuery('#modal__pagination__search__lists').attr('value', jQuery('input[name="modal_search_lists"]').attr('value'));
        acym_helperModal.getContentAjaxModalPaginationLists();
    },
    setButtonModalPaginationLists: function () {
        jQuery('.modal__pagination__button-open').off('click').on('click', function () {
            acym_helperModal.getContentAjaxModalPaginationLists();
        });
    },
    setButtonModalPaginationUsers: function () {
        jQuery('.modal__pagination__users__button-open').off('click').on('click', function () {
            jQuery('#acym__modal__users-selected').val('');
        });
    },
    setButtonConfirmModalPaginationUsers: function () {
        jQuery('#modal__pagination__users__confirm').off('click').on('click', function () {
            let $task = jQuery('#acym__modal__users__form-task').val();
            jQuery('input[name="task"]').val($task);
            jQuery('#acym_form').submit();
        });
    }
};
