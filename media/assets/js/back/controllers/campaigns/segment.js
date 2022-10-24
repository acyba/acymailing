jQuery(function($) {
    function Init() {
        acym_helperSegment.reloadGlobalCounter($('.acym__segments__select__classic__filter').closest('.acym__segments__group__filter'));
        acym_helperSegment.refreshFilterProcess();
        acym_helperSegment.rebuildFilters();
        setActionOnSelectSegment();
        setButtonToggleSaveSegment();
        setButtonSaveSegment();
        acym_helperFilter.setAutomationReload();
    }

    function setActionOnSelectSegment() {
        $('[name="segment_selected"]').on('change', function (e) {
            if ($(this).val() !== '' && $('[name^="acym_action"]').length > 0) {
                if (acym_helper.confirm(ACYM_JS_TXT.ACYM_IF_YOU_SELECT_SEGMENT_FILTERS_ERASE)) {
                    $('.acym__segments__one__filter__classic').each((index, element) => {
                        if (index === 0) {
                            $(element).find('.acym__segments__inserted__filter').remove();
                            $(element).find('[name="filters_name"]').val(0);
                        } else {
                            $(element).remove();
                        }
                    });
                } else {
                    $(this).val('');
                }
            }

            let $counterInput = jQuery('#acym__campaigns__segment__edit-user-count');
            $counterInput.html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=segments&task=countGlobalBySegmentId&id=' + $(this).val() + '&lists=' + $('[name="list_selected"]').val();

            $.get(ajaxUrl, function (res) {
                res = acym_helper.parseJson(res);
                if (res.error) {
                    acym_helperNotification.addNotification(res.message, 'error');
                    $counterInput.html('?');
                } else {
                    $counterInput.html(res.data.count);
                }
            });
        });
    }

    function setButtonToggleSaveSegment() {
        $('.acym__campaigns__segment__edit__filters__save-icon').off('click').on('click', function () {
            $(this).hide();
            $('.acym__campaigns__segment__edit__filters__save-action').css('display', 'flex');
        });

        $('.acym__campaigns__segment__edit__filters__save-action .acym__button__cancel').off('click').on('click', function () {
            $('.acym__campaigns__segment__edit__filters__save-icon').css('display', 'flex');
            $('.acym__campaigns__segment__edit__filters__save-action').hide();
        });
    }

    function setButtonSaveSegment() {
        $('.acym__campaigns__segment__edit__filters__save-action-save').off('click').on('click', function () {
            let $input = $('#acym__campaigns__segment__edit__filters__save-segment-name');
            let $message = $('#acym__campaigns__segment__edit__filters__save-well');
            if ($input.val() === '' || $('[name^="acym_action"]').length === 0) {
                alert(ACYM_JS_TXT.ACYM_PLEASE_FILL_A_NAME_FOR_YOUR_SEGMENT);
                return false;
            }

            $('.acym__campaigns__segment__edit__filters__save-loading').css('display', 'flex');
            $('.acym__campaigns__segment__edit__filters__save-icon').hide();
            $('.acym__campaigns__segment__edit__filters__save-action').hide();

            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=segments&task=saveFromCampaign';

            $.post(ajaxUrl, jQuery('#acym_form').serialize() + '&ctrl=segments&task=saveFromCampaign').done(function (result) {
                result = acym_helper.parseJson(result);
                if (result.error) {
                    acym_helperNotification.addNotification(result.message, 'error', true);
                    return false;
                }
                acym_helperNotification.addNotification(result.message, 'info', true);
                $message.css('display', 'flex').html(result.message);
                $('[name="saved_segment_id"]').val(result.data.segment_id);
            }).error(function () {
                acym_helperNotification.addNotification(ACYM_JS_TXT.ACYM_COULD_NOT_SAVE_SEGMENT, 'error', true);
            }).always(function () {
                $('.acym__campaigns__segment__edit__filters__save-loading').hide();
                $('.acym__campaigns__segment__edit__filters__save-icon').css('display', 'flex');
                $input.val('');
                setTimeout(() => {
                    $message.hide().html('');
                }, 3000);
            });
        });
    }

    Init();
});
