jQuery(function ($) {

    function Language() {
        setCopyTranslation();
        setLoadLatest();
    }

    Language();

    function setCopyTranslation() {
        $('#copy_translations').off('click').on('click', function (e) {
            e.preventDefault();

            if (acym_helper.confirm(ACYM_JS_TXT.ACYM_ARE_YOU_SURE + '\n' + ACYM_JS_TXT.ACYM_COPY_DEFAULT_TRANSLATIONS_CONFIRM)) {
                let $defaultTextarea = $('#translation');
                let $customTextarea = $('textarea[name="customcontent"]');

                let $customTranslations = $customTextarea.val().split('\n');
                if ($customTranslations.length === 0) {
                    // There weren't any custom translations, we can just copy
                    $customTextarea.text($defaultTextarea.text());
                    return;
                }

                let customs = {};
                $customTranslations.forEach(function (row) {
                    if (row.indexOf('=') !== -1) {
                        let parts = row.split('=');
                        customs[parts[0]] = parts[1];
                    }
                });

                let result = [];
                let $defaultTranslations = $defaultTextarea.text().split('\n');
                $defaultTranslations.forEach(function (row) {
                    if (row.indexOf('=') === -1) {
                        result.push(row);
                    } else {
                        let parts = row.split('=');

                        if (customs.hasOwnProperty(parts[0])) {
                            delete customs[parts[0]];
                        }

                        result.push(row);
                    }
                });

                if (Object.keys(customs).length > 0) {
                    result.unshift('\n');
                    for (let key in customs) {
                        if (customs.hasOwnProperty(key)) {
                            result.unshift(key + '=' + customs[key]);
                        }
                    }
                }

                $customTextarea.val(result.join('\n'));
                $customTextarea.html(result.join('\n'));
            }
        });
    }

    function setLoadLatest() {
        jQuery('#load_latest_translation').off('click').on('click', function (e) {
            e.preventDefault();

            const $translationContainer = jQuery('#translation');
            const $spinner = jQuery('#load_latest_spinner');
            const data = {
                ctrl: 'language',
                task: 'getLatestTranslationAjax',
                code: $(this).attr('data-acym-code')
            };

            $translationContainer.hide();
            $spinner.show();

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                $translationContainer.show();
                $spinner.hide();

                if (response.error) {
                    alert(response.message);
                } else {
                    $translationContainer.text(response.data.translations);
                }
            });
        });
    }
});
