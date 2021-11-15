const acym_helperCampaigns = {
    attachmentNb: 1,
    initCampaigns: function () {
        acym_helperCampaigns.setChooseConfirmReplaceTemplate();
        acym_helperCampaigns.initAttachmentCampaigns();
        acym_helperCampaigns.setStopScheduled();
        acym_helperCampaigns.setStopSending();
        acym_helperCampaigns.setDeactivateAutomatic();
        acym_helperCampaigns.setSummaryScroll();

        if (jQuery('#acym__wysid').length < 1) {
            acym_helperCampaigns.setSendSettingsButtons();
        }
    },
    setSummaryScroll: function () {
        let $workflow = jQuery('#workflow');
        let currentStepHref = $workflow.find('a').attr('href');

        if (undefined !== currentStepHref && (currentStepHref.indexOf('summary') || currentStepHref.indexOf('test'))) {
            jQuery(document).ready(function () {
                $workflow.scrollLeft($workflow.outerWidth());
            });
        }
    },
    setChooseConfirmReplaceTemplate: function () {
        let campaignId = jQuery('#acym__campaign__choose__campaign').val();
        jQuery('#acym__templates__choose__create__empty').off('click').on('click', function (e) {
            if (campaignId && 0 != campaignId && !confirm(ACYM_JS_TXT.ACYM_REPLACE_CONFIRM)) {
                e.preventDefault();
                return false;
            }
        });
        jQuery('.acym__templates__oneTpl').off('click').on('click', function (e) {
            if (!campaignId || 0 == campaignId || confirm(ACYM_JS_TXT.ACYM_REPLACE_CONFIRM)) {
                window.location.href = jQuery(this).find('.acym__templates__oneTpl__choose').val();
            }
        });
    },
    setAutoOpenEditor: function () {
        if (!jQuery('#acym__campaign__edit_email').length) return;

        if ('0' === jQuery('#acym__campaign__recipients__form__campaign').val()) {
            jQuery('#acym__wysid__edit__button').click();
        }
    },
    initAttachmentCampaigns: function () {
        acym_helperCampaigns.setAddAttachmentButton();
        acym_helperCampaigns.setRemoveAttachment();
        acym_helperCampaigns.setDeleteAttachment();
    },
    setAddAttachmentButton: function () {
        jQuery('#acym__campaigns__attach__add').off('click').on('click', function () {
            if (acym_helperCampaigns.attachmentNb > 9) return;
            jQuery('#acym__campaigns__attach__' + acym_helperCampaigns.attachmentNb).css('display', '');
            acym_helperCampaigns.attachmentNb++;
        });
    },
    setRemoveAttachment: function () {
        jQuery('.acym__campaigns__attach__remove').off('click').on('click', function (evt) {
            let idRemove = evt.currentTarget.getAttribute('data-id');
            jQuery('#attachments' + idRemove + 'selection').html('');
            jQuery('#attachments' + idRemove + 'suppr').css('display', 'none');
            jQuery('#attachments' + idRemove).val('');
        });
    },
    setDeleteAttachment: function () {
        jQuery('.acym__campaigns__attach__delete').off('click').on('click', function (evt) {
            let allAttachment = jQuery('.acym__campaigns__attach__delete');
            let current = jQuery(this);
            let idRemove = '';
            let idDivToRemove = '';
            allAttachment.each(function (index) {
                if (jQuery(this).attr('data-id') == current.attr('data-id')) {
                    idRemove = index;
                    idDivToRemove = current.attr('data-id');
                }
            });
            let mailid = current.attr('data-mail');
            jQuery.post(ACYM_AJAX_URL + '&ctrl=' + acym_helper.ctrlCampaigns + '&task=deleteAttach&id=' + idRemove + '&mail=' + mailid, function (response) {
                response = acym_helper.parseJson(response);
                if (response.error) {
                    acym_helperCampaigns.setDisplayNotif(response.message, 'error');
                } else {
                    acym_helperCampaigns.setDisplayNotif(response.message, 'info');
                    jQuery('#acym__campaigns__attach__del' + idDivToRemove).remove();
                }
            }).fail(function (e) {
                console.log(e);
            });
        });
    },
    setDisplayNotif: function (message, type) {
        if (acym_helper.ctrlCampaigns.substr(0, 5) !== 'front') {
            acym_helperNotification.addNotification(message, type);
        } else {
            console.log(message);
        }
    },
    setSendSettingsButtons: function () {
        jQuery('.acym__campaign__sendsettings__buttons-type').off('click').on('click', function () {
            if (jQuery(this).hasClass('disabled')) return true;

            jQuery('.acym__campaign__sendsettings__buttons-type').addClass('button-radio-unselected').removeClass('button-radio-selected');
            jQuery(this).removeClass('button-radio-unselected').addClass('button-radio-selected');
            jQuery('.acym__campaign__sendsettings__params').hide();
            jQuery('[data-show="' + jQuery(this).attr('id') + '"]').show();
            jQuery('[name="sending_type"]').val(jQuery(this).attr('data-sending-type'));
            if (jQuery(this).attr('id') !== 'acym__campaign__sendsettings__scheduled') {
                jQuery('#acym__campaign__sendsettings__send-type-scheduled__date').removeAttr('required');
            } else {
                jQuery('#acym__campaign__sendsettings__send-type-scheduled__date').attr('required', 'required');
            }
        });
    },
    setStopScheduled: function () {
        jQuery('.acym__campaign__listing__scheduled__stop').off('click').on('click', function () {
            let $form = jQuery('#acym_form');
            let $campaignId = jQuery(this).attr('data-campaignid');
            $form.append('<input type="hidden" name="stopScheduledCampaignId" value="' + $campaignId + '">');
            $form.find('[name="task"]').val('stopScheduled');
            $form.submit();
        });
    },
    setStopSending: function () {
        jQuery('.acym__campaign__listing__sending__stop').off('click').on('click', function () {
            let $form = jQuery('#acym_form');
            let $campaignId = jQuery(this).attr('data-campaignid');
            $form.append('<input type="hidden" name="stopSendingCampaignId" value="' + $campaignId + '">');
            $form.find('[name="task"]').val('stopSending');
            $form.submit();
        });
    },
    setDeactivateAutomatic: function () {
        jQuery('.acym__campaign__listing__automatic__deactivate').off('click').on('click', function () {
            let $form = jQuery('#acym_form');
            let $campaignId = jQuery(this).attr('data-campaignid');
            $form.append('<input type="hidden" name="id" value="' + $campaignId + '">');
            $form.find('[name="task"]').val('toggleActivateColumnCampaign');
            $form.submit();
        });
    },
    setSelectionCreateNewMail: function () {
        const allCards = jQuery('.acym__email__new__card:not(.acym__email__new__card__disabled)');
        allCards.off('click').on('click', function () {
            if (jQuery(this).hasClass('acym__email__new__card-selected')) return;
            allCards.removeClass('acym__email__new__card-selected');
            jQuery(this).addClass('acym__email__new__card-selected');

            if ((jQuery(this).hasClass('acym__email__new__card-list') && jQuery(this).find('.acym__email__new__card__select').val() !== '') || !jQuery(this)
                .hasClass('acym__email__new__card-list')) {
                jQuery('#acym__email__new__button-create').removeAttr('disabled');
            } else {
                jQuery('#acym__email__new__button-create').attr('disabled', 'true');
            }
        });
    },
    setSelectCreateNewMail: function () {
        jQuery('.acym__email__new__card__select').on('change', function () {
            if (jQuery(this).closest('.acym__email__new__card').hasClass('acym__email__new__card-selected')) {
                if (jQuery(this).val() !== '') {
                    jQuery('#acym__email__new__button-create').removeAttr('disabled');
                } else {
                    jQuery('#acym__email__new__button-create').attr('disabled', 'true');
                }
            }
        });
    },
    setButtonCreateNewEmail: function () {
        jQuery('#acym__email__new__button-create').off('click').on('click', function () {
            const $cardSelected = jQuery('.acym__email__new__card-selected');
            let link = $cardSelected.attr('acym-data-link');
            if (link.indexOf('{listid}') !== -1) {
                const listId = $cardSelected.find('.acym__email__new__card__select').val();
                link = link.replace('{listid}', listId);
            }
            window.location.href = link;
        });
    },
    setClickFlagsSummary: function () {
        jQuery('.acym__campaign__summary__preview__languages-one')
            .not('.acym__campaign__summary__preview__languages-one__empty')
            .off('click')
            .on('click', function () {
                if (jQuery(this).hasClass('language__selected')) return;

                jQuery('.language__selected').removeClass('language__selected');
                jQuery(this).addClass('language__selected');

                let idBody = '#acym__summary-body-' + jQuery(this).attr('data-acym-lang');
                let idSubject = '#acym__summary-subject-' + jQuery(this).attr('data-acym-lang');
                let idPreview = '#acym__summary-preview-' + jQuery(this).attr('data-acym-lang');

                jQuery('.acym__hidden__mail__content').val(jQuery(idBody).val());
                acym_helperPreview.loadIframe('acym__wysid__preview__iframe__acym__wysid__email__preview', false);

                jQuery('.acym__campaign__summary__email__information-subject').html(jQuery(idSubject).val());
                jQuery('.acym__campaign__summary__generated__mail__one__preview').html(jQuery(idPreview).val());
            });
    }
};
