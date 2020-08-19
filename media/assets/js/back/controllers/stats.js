jQuery(document).on('acy_preview_loaded', () => {
    let $clicksValue = jQuery('#acym__stats_click__map__all-links__click');
    if ($clicksValue.length < 1) return;
    let allLinksClick = JSON.parse($clicksValue.val());
    jQuery('#acym__wysid__preview__iframe__acym__wysid__email__preview')
        .contents()
        .find('body')
        .prepend(jQuery('#acym__stats__add_style_export__click-map').html());
    jQuery.each(allLinksClick, (index, value) => {
        if (index === 'allClick') return;
        let $link = jQuery('#acym__wysid__preview__iframe__acym__wysid__email__preview').contents().find('[href="' + index + '"]');
        $link.css({
            'position': 'relative',
            'overflow': 'initial',
            'display': 'inline-block'
        })
             .append('<span class="acym__stats__cliked__link__percentage overviewbubble" style="background-color: rgba('
                     + value.color
                     + ', 0.8);"><span class="acym__tooltip" style="display:flex;justify-content:center;align-items:center;"><span class="acym__tooltip__text"> <span class="acym__tooltip__title">'
                     + acym_helper.sprintf(ACYM_JS_TXT.ACYM_OF_CLICKS, '<span class="acym__color__light-blue">' + value.percentage + '%</span> ')
                     + '</span>'
                     + acym_helper.sprintf(ACYM_JS_TXT.ACYM_CLICKS_OUT_OF, '<b>' + value.numberClick + '</b>', ' <b> ' + allLinksClick.allClick + '</b>')
                     + '</span>'
                     + value.percentage
                     + '%</span></span>');
    });
    acym_helperTooltip.setTooltip();
});

jQuery(document).ready(function ($) {

    function stats() {
        setDropdownChooseCampaign();
        setStartEndDate();
        fixSelect2DetailedStatsSortBy();
        resetExportButton();
        setSelect2();
        setChartExport();
        setSelect2Language();
    }

    stats();

    function resetExportButton() {
        $('.acym__stats__export__button').on('click', function () {
            setTimeout(function () {
                $('#formSubmit')[0].disabled = false;
                $('[name="task"]').val('');
            }, 5);
        });
    }

    function setChartExport() {
        $('.acym__stats__export__global__charts').off('click').on('click', function () {
            let $button = $(this);
            let node = $('#acym__stats__export__global__charts__scope');
            $button.attr('disabled', 'true');
            downloadImage(node, $button, 'export_stats_global_');
        });
        $('.acym__stats__export__click-map__charts').on('click', function () {
            let $button = $(this);
            let node = $('#acym__wysid__preview__iframe__acym__wysid__email__preview').contents().find('body');
            $button.attr('disabled', 'true');
            downloadImage(node, $button, 'export_stats_click_map_');
        });
    }

    function downloadImage(node, $button, imageName) {
        html2canvas(node[0]).then(canvas => {
            let dataUrl = canvas.toDataURL('image/png');
            let link = document.createElement('a');
            let d = new Date;
            link.download = imageName + '' + d.getFullYear() + '_' + d.getMonth() + '_' + d.getDay() + '.jpeg';
            link.href = dataUrl;
            link.click();
            $button.removeAttr('disabled');
            $('[name="task"]').val('');
        });
    }

    function setSelect2() {
        $('[name="export_type"]')
            .select2({
                theme: 'sortBy',
                minimumResultsForSearch: Infinity
            })
            .on('change', function () {
                let $exportButton = $('[data-task="exportGlobal"]');
                $exportButton.off('click');

                if ($(this).val() === 'charts') {
                    $exportButton.removeClass('acy_button_submit')
                                 .addClass('acym__stats__export__global__charts');

                    setChartExport();
                } else {
                    $exportButton.addClass('acy_button_submit')
                                 .removeClass('acym__stats__export__global__charts');

                    acym_helper.setSubmitButtonGlobal();
                }

                resetExportButton();
            });
    }

    function setDropdownChooseCampaign() {
        $('#mail_id').off('change').on('change', function () {
            $('.acym__stats__select__language').val(0);
            $('#formSubmit').click();
        });
    }

    function setStartEndDate() {
        let $start = $('#chart__line__start');
        let $end = $('#chart__line__end');

        let startDate = $start.attr('data-start');
        let endDate = $end.attr('data-end');

        $start.val(startDate);
        $end.val(endDate);
        $('.acym__stats__chart__line__input__date').off('change').on('change', function () {
            let timestampStartDate = moment($start.val(), 'YYYY-MM-DD HH:mm').unix();
            let timestampEndDate = moment($end.val(), 'YYYY-MM-DD HH:mm').add(1, 'hours').unix();
            setAjaxCallForChartLine(timestampStartDate, timestampEndDate);
        });
    }

    function setAjaxCallForChartLine(start, end) {
        let url = ACYM_AJAX_URL + '&ctrl=stats&task=setDataForChartLine"&start=' + start + '&end=' + end + '&id=' + $('#mail_id').val();

        $.post(url, function (response) {
            if (response == 'error') {
                acym_helperNotification.addNotification(ACYM_JS_TXT.ACYM_STATS_START_DATE_LOWER, 'warning');
            } else {
                $('#acym__stats__chart__line__canvas').html(response);
            }
        });
    }

    function fixSelect2DetailedStatsSortBy() {
        $('.select2-container--sortBy')
            .css({
                'width': 'auto',
                'min-width': '140px'
            });
    }

    function setSelect2Language() {
        $('.acym__stats__select__language').off('change').on('change', function () {
            $('#formSubmit').click();
        });
    }
});
