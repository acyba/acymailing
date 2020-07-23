jQuery(document).ready(function ($) {
    let batchToDo = 0;//total calls to do
    let batchWhereWeAre = 0;//At which batch we are
    let totalCallsToDo = 0;//At which call we are on total
    let totalCallWhereWeAre = 0;//At which call we are on total
    let insertPerCall = 500; //We insert 20 element per call
    let callsPerBatch = 10;//we do 10 calls at times
    let numberOfElementToMigrate = 0;
    let percentageOfElement = 0;
    let widthProgressBar = 0;

    function Init() {
        setOnChangeCheckboxesMigration();
        setDisableCheckboxesMigration();
        setOnClickMigrateButton();
        setOnClickMigrateFromErrorButton();
        //randomGif(); //We keep for after
    }

    Init();

    function setOnChangeCheckboxesMigration() {
        $('.acym__migrate__option').off('change').on('change', function () {
            setDisableCheckboxesMigration();
            $('.acym__migrate__option:disabled').prop('checked', false);
        });
    }

    function setDisableCheckboxesMigration() {
        let $isCheckedMails = $('#acym__migrate__mails').is(':checked');

        if (!$isCheckedMails) {
            $('#acym__migrate__input__global_stats').hide();
        } else {
            $('#acym__migrate__input__global_stats').show();
        }

        $('#acym__migrate__mailstats').prop('disabled', $isCheckedMails === false);

        $('#acym__migrate__button').prop('disabled', $('input:checked').length === 0);
    }

    function setOnClickMigrateFromErrorButton() {
        $('#acym__migrate__restart_from_error__button').off('click').on('click', function () {
            window.scrollTo(0, 0);
            $('#acym__migrate__result__error').hide();
            $('#acym__migrate__result__error__message').html('');

            doMigration();
        });
    }

    function setOnClickMigrateButton() {
        $('#acym__migrate__button').off('click').on('click', function () {
            doMigration();
        });
    }

    function doMigration() {
        $('#acym__migrate__no__button').prop('disabled', true);
        $('#acym__migrate__button').prop('disabled', true);
        $('.acym__migrate__option').prop('disabled', true);

        let elementsToMigrate = {
            'config': $('#acym__migrate__config').is(':checked') && !$('#acym__migrate__config').hasClass('acym__migrate__migrate_with_success_element'),
            'bounce': $('#acym__migrate__bounce').is(':checked') && !$('#acym__migrate__bounce').hasClass('acym__migrate__migrate_with_success_element'),
            'lists': $('#acym__migrate__lists').is(':checked') && !$('#acym__migrate__lists').hasClass('acym__migrate__migrate_with_success_element'),
            'mails': $('#acym__migrate__mails').is(':checked') && !$('#acym__migrate__mails').hasClass('acym__migrate__migrate_with_success_element'),
            'templates': $('#acym__migrate__templates').is(':checked') && !$('#acym__migrate__templates').hasClass('acym__migrate__migrate_with_success_element'),
            'fields': $('#acym__migrate__fields').is(':checked') && !$('#acym__migrate__fields').hasClass('acym__migrate__migrate_with_success_element'),
            'users': $('#acym__migrate__users').is(':checked') && !$('#acym__migrate__users').hasClass('acym__migrate__migrate_with_success_element'),
        };


        elementsToMigrate['subscriptions'] = elementsToMigrate['users'] && elementsToMigrate['lists'];
        elementsToMigrate['users_fields'] = elementsToMigrate['users'] && elementsToMigrate['fields'];
        elementsToMigrate['mailstats'] = $('#acym__migrate__mailstats').is(':checked') && !$('#acym__migrate__mailstats').hasClass('acym__migrate__migrate_with_success_element');
        elementsToMigrate['mailhaslists'] = elementsToMigrate['mails'] && elementsToMigrate['lists'];
        elementsToMigrate['welcomeunsub'] = elementsToMigrate['mails'] && elementsToMigrate['lists'];

        let ajaxUrls = [];
        let elements = [];

        let params = {
            'migrateMails': elementsToMigrate['mails'] ? 1 : 0,
            'migrateMailStats': $('#acym__migrate__mailstats').is(':checked') && !$('#acym__migrate__mailstats').hasClass('acym__migrate__migrate_with_success_element') ? 1 : 0,
            'migrateMailHasLists': elementsToMigrate['mails'] && elementsToMigrate['lists'] ? 1 : 0,
            'migrateLists': elementsToMigrate['lists'] ? 1 : 0,
        };


        $.each(elementsToMigrate, function (key, val) {
            if (val === true) {
                let ajaxUrl = ACYM_AJAX_URL + '&ctrl=dashboard&task=preMigration&element=' + key;
                ajaxUrls.push(ajaxUrl);
                elements.push(key);
            }
        });

        $('#acym__migrate__all_checked_elements').val(JSON.stringify(elements));
        numberOfElementToMigrate = elements.length;
        percentageOfElement = 100 / numberOfElementToMigrate;
        $('.acym__migration__need__display').show();
        callAjax(ajaxUrls, elements);
    }

    function doOneBatchAjaxCalls(element, ajaxUrls, elements) {
        let error = false;
        let ajaxCalls = [];
        let asyncClear = '';
        for (let i = 0 ; i < callsPerBatch ; i++) {
            if (totalCallWhereWeAre > totalCallsToDo) break;//If we did all the element
            let currentElement = totalCallWhereWeAre * insertPerCall;
            let ajax = $.post(ACYM_AJAX_URL + '&ctrl=dashboard&task=migrate&element=' + element + '&currentElement=' + currentElement + '&insertPerCalls=' + insertPerCall);
            ajaxCalls.push(ajax);
            totalCallWhereWeAre++;
        }
        $.when.apply($, ajaxCalls).then(function () {
            batchWhereWeAre++;
            widthProgressBar = (percentageOfElement / batchToDo) + widthProgressBar;
            $('#acym__migration__progress__bar__inner').animate({width: Math.round(widthProgressBar) + '%'});
            $('#acym__migration__percentage').html(Math.round(widthProgressBar) + '%');
            $.each(arguments, function (index, argument) {
                if (argument[0].indexOf('ERROR') !== -1) {
                    error = true;
                    asyncClear = argument[0];//We get the response
                    afterAjaxCall(error, element, ajaxUrls, elements, asyncClear);
                }
            });

            if (batchWhereWeAre === batchToDo) {
                afterAjaxCall(error, element, ajaxUrls, elements, asyncClear);
            } else {
                doOneBatchAjaxCalls(element, ajaxUrls, elements);
            }
        });
        return true;
    }

    function callAjax(ajaxUrls, elements) {
        if (ajaxUrls.length === 0) {
            $('#acym__migration__progress__bar__inner').animate({width: '100%'});
            $('#acym__migration__percentage').html('100%');
            $('#acym__migrate__result__ok').show();
            return;
        }

        let element = elements.shift().toLowerCase();
        $('#acym__migrate__result__' + element + '__check').html('<i class="acymicon-circle-o-notch acymicon-spin acym__color__blue"></i>');

        let ajaxUrl = ajaxUrls.shift();
        //We call the url which empty the tables and return ne total number values to migrate
        $.post(ajaxUrl, function (response) {
            if ('0' == response) {
                widthProgressBar += percentageOfElement;
                $('#acym__migration__progress__bar__inner').animate({width: Math.round(widthProgressBar) + '%'});
                $('#acym__migration__percentage').html(Math.round(widthProgressBar) + '%');
                afterAjaxCall(false, element, ajaxUrls, elements, response);
            } else {
                if (response.indexOf('ERROR') !== -1) {
                    afterAjaxCall(true, element, ajaxUrls, elements, response);
                } else {
                    batchWhereWeAre = 0;
                    totalCallWhereWeAre = 0;
                    batchToDo = Math.ceil(response / (insertPerCall * callsPerBatch));
                    totalCallsToDo = Math.ceil(response / insertPerCall);
                    let error = false;
                    doOneBatchAjaxCalls(element, ajaxUrls, elements);
                }
            }
        }).fail(function (response) {
            $('#acym__migrate__result').append('<br>' + ACYM_JS_TXT.ACYM_LOADING_ERROR);
        });
    }

    function afterAjaxCall(error, element, ajaxUrls, elements, asyncClear) {
        if (!error) {
            $('#acym__migrate__result__' + element + '__check').html('<i class="acymicon-check-circle acym__color__green"></i>');

            //little special case because we migrate mails stats and mails at the same time
            if (element === 'mails' && $('#acym__migrate__mailstats').is(':checked')) {
                $('#acym__migrate__result__mailstats__check').html('<i class="acymicon-check-circle acym__color__green"></i>');
            }

            $('#acym__migrate__' + element).addClass('acym__migrate__migrate_with_success_element');
            callAjax(ajaxUrls, elements);
        } else {
            $('#acym__migrate__error_element').val(element);
            $('#acym__migrate__result__' + element + '__check').html('<i class="acymicon-exclamation-circle acym__color__red"></i>');

            //little special case because we migrate mails stats and mails at the same time
            if (element === 'mails' && $('#acym__migrate__mailstats').is(':checked')) {
                $('#acym__migrate__result__mailstats__check').html('<i class="acymicon-exclamation-circle acym__color__red"></i>');
            }

            $('#acym__migrate__result__error__message').html(asyncClear);
            $('#acym__migrate__result__error').show();
        }
    }

    function randomGif() {
        const giphy = {
            baseURL: 'https://api.giphy.com/v1/gifs/',
            key: 'rLgg3ulcXQFtjFxwMqgP85ZNXXMpsK3D',
            tag: 'fail',
            type: 'random',
        };
        let xhr = $.get(giphy.baseURL + giphy.type + '?api_key=' + giphy.key + '&tag=' + giphy.tag);
        xhr.done(function (data) {
            $('#acym__migration__gif').attr('src', data.data.image_original_url);
            setTimeout(function () {
                randomGif();
            }, 5000);
        });
    }
});
