jQuery(function ($) {
    function Init() {
        acym_helperMailer.domainSuggestion();
        addDomains();
    }

    Init();

    function addDomains() {
        $('#acym__walkthrough__acymailer__add_domain').off('click').on('click', function () {
            const $errorContainer = $('#acym__walkthrough__acymailer__add__error');
            $errorContainer.addClass('is-hidden');
            $('#acym__acymailer__unverifiedDomains').hide();

            const domainValue = $('#acymailer_domain').val().trim();
            if (acym_helper.empty(domainValue)) {
                return;
            }

            const $loader = $('#acym__walkthrough__acymailer__domain__spinner');
            $loader.removeClass('is-hidden');

            const data = {
                oneDomain: domainValue,
                ctrl: 'dynamics',
                task: 'trigger',
                plugin: 'plgAcymAcymailer',
                trigger: 'ajaxAddDomain'
            };

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                $loader.addClass('is-hidden');

                if (response.error) {
                    $('#acym__walkthrough__acymailer__add__error__message').text(response.message);
                    $errorContainer.removeClass('is-hidden');
                } else {
                    const $cnameTable = $('#acym__walkthrough__acymailer__domain__cname');
                    response.data.cnameRecords.forEach(cnameRecord => {
                        $cnameTable.append(`
                            <div class="cell grid-x grid-margin-x margin-left-0 acym__listing__row">
                                <div class="cell small-6 grid-x acym_text_ellipsis cname-name">
                                    ${cnameRecord.name}
                                </div>
                                <div class="cell small-6 grid-x acym_text_ellipsis cname-value">
                                    ${cnameRecord.value}
                                </div>
                            </div>`);
                    });

                    selectCnameValues();

                    $('#acym__walkthrough__acymailer__domain__container').addClass('is-hidden');
                    $('#acym__walkthrough_footer__domain__container').addClass('is-hidden');
                    $('#acym__walkthrough__acymailer__cname__container').removeClass('is-hidden');
                    $('#acym__walkthrough_footer__cname__container').removeClass('is-hidden');
                }
            });
        });
    }

    function selectCnameValues() {
        $('.cname-name, .cname-value').on('click', function () {
            let range = document.createRange();
            range.selectNode(this);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
        });
    }
});
