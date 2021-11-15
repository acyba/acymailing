jQuery(document).ready(function ($) {
    function Init() {
        initEmailsToggle();
    }

    Init();

    let ajaxCall = undefined;

    function initEmailsToggle() {
        $('.acym__followup__emails__toggle').off('click').on('click', function () {
            if (undefined !== ajaxCall && typeof ajaxCall.abort === 'function'){
                ajaxCall.abort();
                ajaxCall = undefined;
            }

            let $container = $(this).closest('.acym__listing__row');
            if ($(this).hasClass('acymicon-keyboard_arrow_down')) {
                $(this).removeClass('acymicon-keyboard_arrow_down').addClass('acymicon-keyboard_arrow_up');
                $container.addClass('emails_listing_opened');
                $container.find('.acym__followup__emails__listing__status')
                          .html('<i class="cell text-center acymicon-circle-o-notch acymicon-spin margin-top-1"></i>');

                ajaxCall = $.ajax({
                    type: 'POST',
                    url: ACYM_AJAX_URL + '&ctrl=followups&task=getEmailsListing&id=' + $(this).attr('acym-data-id'),
                    success: function (response) {
                        let stats = acym_helper.parseJson(response);
                        if (stats.error) {
                            acym_helperNotification.addNotification(stats.message, 'error');
                            return;
                        }

                        $container.find('.acym__followup__emails__listing__status').html('');

                        stats.data.forEach(function (oneStat) {
                            $container.find('.acym__followup__emails__listing__subject')
                                      .append('<div class="cell acym_text_ellipsis">' + oneStat.subject + '</div>');
                            $container.find('.acym__followup__emails__listing__status')
                                      .append('<div class="cell">'
                                              + '<div class="cell acym__campaign__status__status acym__background-color__green">'
                                              + ACYM_JS_TXT.ACYM_SENT
                                              + ' : '
                                              + (acym_helper.empty(oneStat.sent) ? 0 : oneStat.sent)
                                              + ' '
                                              + ACYM_JS_TXT.ACYM_RECIPIENTS.toLowerCase()
                                              + '</div>'
                                              + '</div>');
                            $container.find('.acym__followup__emails__listing__open').append('<div class="cell">' + oneStat.open + '</div>');
                            $container.find('.acym__followup__emails__listing__click').append('<div class="cell">' + oneStat.click + '</div>');
                            $container.find('.acym__followup__emails__listing__income').append('<div class="cell">' + oneStat.income + '</div>');
                        });
                    }
                });
            } else {
                $(this).removeClass('acymicon-keyboard_arrow_up').addClass('acymicon-keyboard_arrow_down');
                $(this).closest('.acym__listing__row').removeClass('emails_listing_opened');
                $container.find('div[class^="acym__followup__emails__listing__"],div[class*=" acym__followup__emails__listing__"]').empty();
            }
        });
    }
});
