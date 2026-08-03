document.addEventListener('wpcf7submit', function (event) {
    const formcontainer = jQuery(event.target);
    if (formcontainer.hasClass('invalid')) {
        return;
    }

    const allAcyFields = formcontainer.find('[name^="acymhiddenlists_"]');

    // There may be multiple AcyMailing blocks in a single form
    for (let i = 0 ; i < allAcyFields.length ; i++) {
        const currentField = jQuery(allAcyFields[i]);
        const identifier = currentField.data('acymfield');

        if (!identifier || identifier.length === 0) {
            continue;
        }

        const urlAction = jQuery('[name="acymaction_' + identifier + '"]').val();
        const emailField = currentField.data('acymmail');
        const nameField = currentField.data('acymname');

        if (!emailField || emailField.length === 0 || urlAction.length === 0) {
            continue;
        }

        const emailAddress = formcontainer.find(`[name="${emailField}"]`).val();
        if (!emailAddress || emailAddress.length === 0) {
            console.error('The specified email field is empty or does not exist: ' + emailField);
            continue;
        }

        let name = '';
        if (undefined !== nameField && nameField.length !== 0) {
            const nameFieldElement = formcontainer.find('[name="' + nameField + '"]');
            if (nameFieldElement) {
                name = nameFieldElement.val();
            }
        }

        const hiddenLists = currentField.val();
        const allListsField = formcontainer.find('[name="' + identifier + '[]"]');
        const allLists = [];
        for (let j = 0 ; j < allListsField.length ; j++) {
            if (allListsField[j].checked) {
                allLists.push(allListsField[j].value);
            }
        }

        if (allLists.length === 0 && hiddenLists.length === 0) {
            continue;
        }

        const data = {
            'user[email]': emailAddress,
            'user[name]': name,
            'hiddenlists': hiddenLists,
            'subscription': allLists,
            'acy_source': 'Contact Form 7'
        };

        const customFields = currentField.data('acymcf');
        if (customFields && typeof customFields === 'object') {
            for (const id in customFields) {
                const fieldName = customFields[id]['matchingFieldName'];
                let $fieldElement = formcontainer.find(`[name="${fieldName}"]`);
                if ($fieldElement.length === 0) {
                    $fieldElement = formcontainer.find(`[name="${fieldName}[]"]`);
                    if ($fieldElement.length === 0) {
                        continue;
                    }
                }

                const fieldType = customFields[id]['type'];

                if ([
                    'text',
                    'textarea',
                    'date'
                ].includes(fieldType)) {
                    data[`customField[${id}]`] = $fieldElement.val();
                } else if ([
                    'radio',
                    'checkbox'
                ].includes(fieldType)) {
                    const $selectedElements = $fieldElement.filter(':checked');
                    const selectedValues = [];
                    $selectedElements.each(function () {
                        const currentValue = jQuery(this).val();
                        const matchedOption = customFields[id]['values'].find(allowedValue => {
                            return [
                                allowedValue.value,
                                allowedValue.title
                            ].includes(currentValue);
                        });

                        if (!matchedOption) {
                            console.warn(`The value "${currentValue}" is not allowed for field "${fieldName}". Skipping this value.`);
                            return;
                        }

                        selectedValues.push(matchedOption.value);
                    });
                    data[`customField[${id}]`] = selectedValues.join(',');
                } else if ([
                    'single_dropdown',
                    'multiple_dropdown'
                ].includes(fieldType)) {
                    const currentValue = $fieldElement.val();
                    const matchedOption = customFields[id]['values'].find(allowedValue => {
                        return [
                            allowedValue.value,
                            allowedValue.title
                        ].includes(currentValue);
                    });

                    if (!matchedOption) {
                        console.warn(`The value "${currentValue}" is not allowed for field "${fieldName}". Skipping this value.`);
                        return;
                    }

                    data[`customField[${id}]`] = matchedOption.value;
                } else if (fieldType === 'phone') {
                    const currentValue = $fieldElement.val();
                    if (!currentValue || currentValue.length === 0) {
                        continue;
                    }

                    const parsed = parsePhoneNumber(currentValue);
                    if (parsed.code) {
                        data[`customField[${id}]`] = parsed.code + ',' + parsed.phone;
                    } else {
                        data[`customField[${id}]`] = ',' + parsed.phone;
                    }
                }
            }
        }

        jQuery.ajax({
            type: 'POST',
            url: urlAction,
            data: data,
            timeout: 5000,
            error: function (request, status, error) {
                console.log('Error subscribing user: '.request.responseText);
            }
        });
    }
}, false);

const parsePhoneNumber = (input, fallbackCode = getLikelyCountryCode()) => {
    // Strip everything except digits and leading +
    const cleaned = input.trim().replace(/[\s\-().]/g, '');

    // Case 1: Starts with + => international format
    if (cleaned.startsWith('+')) {
        const digits = cleaned.slice(1);

        // Try to match known country code lengths (1, 2, or 3 digits)
        const countryCodes3 = [
            '213',
            '216',
            '218',
            '220',
            '221',
            '222',
            '223',
            '224',
            '225',
            '226',
            '227',
            '228',
            '229',
            '230',
            '231',
            '232',
            '233',
            '234',
            '235',
            '236',
            '237',
            '238',
            '239',
            '240',
            '241',
            '242',
            '243',
            '244',
            '245',
            '246',
            '247',
            '248',
            '249',
            '250',
            '251',
            '252',
            '253',
            '254',
            '255',
            '256',
            '257',
            '258',
            '260',
            '261',
            '262',
            '263',
            '264',
            '265',
            '266',
            '267',
            '268',
            '269',
            '297',
            '298',
            '299',
            '350',
            '351',
            '352',
            '353',
            '354',
            '355',
            '356',
            '357',
            '358',
            '359',
            '370',
            '371',
            '372',
            '373',
            '374',
            '375',
            '376',
            '377',
            '378',
            '380',
            '381',
            '382',
            '385',
            '386',
            '387',
            '389',
            '420',
            '421',
            '423',
            '500',
            '501',
            '502',
            '503',
            '504',
            '505',
            '506',
            '507',
            '508',
            '509',
            '590',
            '591',
            '592',
            '593',
            '594',
            '595',
            '596',
            '597',
            '598',
            '599',
            '670',
            '672',
            '673',
            '674',
            '675',
            '676',
            '677',
            '678',
            '679',
            '680',
            '681',
            '682',
            '683',
            '685',
            '686',
            '687',
            '688',
            '689',
            '690',
            '691',
            '692',
            '850',
            '852',
            '853',
            '855',
            '856',
            '880',
            '886',
            '960',
            '961',
            '962',
            '963',
            '964',
            '965',
            '966',
            '967',
            '968',
            '970',
            '971',
            '972',
            '973',
            '974',
            '975',
            '976',
            '977',
            '992',
            '993',
            '994',
            '995',
            '996',
            '998'
        ];
        const countryCodes2 = [
            '20',
            '27',
            '30',
            '31',
            '32',
            '33',
            '34',
            '36',
            '39',
            '40',
            '41',
            '43',
            '44',
            '45',
            '46',
            '47',
            '48',
            '49',
            '51',
            '52',
            '53',
            '54',
            '55',
            '56',
            '57',
            '58',
            '60',
            '61',
            '62',
            '63',
            '64',
            '65',
            '66',
            '81',
            '82',
            '84',
            '86',
            '90',
            '91',
            '92',
            '93',
            '94',
            '95',
            '98'
        ];
        const countryCodes1 = ['1'];

        if (countryCodes3.includes(digits.slice(0, 3))) {
            return {
                code: digits.slice(0, 3),
                phone: digits.slice(3)
            };
        } else if (countryCodes2.includes(digits.slice(0, 2))) {
            return {
                code: digits.slice(0, 2),
                phone: digits.slice(2)
            };
        } else if (countryCodes1.includes(digits.slice(0, 1))) {
            return {
                code: digits.slice(0, 1),
                phone: digits.slice(1)
            };
        }

        // Fallback: unknown + number, return as-is with no code
        return {
            code: null,
            phone: digits
        };
    }

    // Case 2: Starts with 00 => international prefix without +
    if (cleaned.startsWith('00')) {
        const withPlus = '+' + cleaned.slice(2);

        return parsePhoneNumber(withPlus);
    }

    // Case 3: Starts with 0  =>  local number with trunk prefix
    if (cleaned.startsWith('0')) {
        return {
            code: fallbackCode,
            phone: fallbackCode ? cleaned.slice(1) : cleaned
        };
    }

    // Case 4: Plain digits, no prefix at all
    return {
        code: fallbackCode,
        phone: cleaned
    };
};

const getLikelyCountryCode = () => {
    const locale = navigator.languages?.[0] || navigator.language || '';
    const region = locale.split('-')[1];

    if (!region) {
        return null;
    }

    const regionToPhoneCode = {
        AC: '247',
        AD: '376',
        AE: '971',
        AF: '93',
        AG: '1',
        AI: '1',
        AL: '355',
        AM: '374',
        AO: '244',
        AR: '54',
        AS: '1',
        AT: '43',
        AU: '61',
        AW: '297',
        AX: '358',
        AZ: '994',
        BA: '387',
        BB: '1',
        BD: '880',
        BE: '32',
        BF: '226',
        BG: '359',
        BH: '973',
        BI: '257',
        BJ: '229',
        BL: '590',
        BM: '1',
        BN: '673',
        BO: '591',
        BR: '55',
        BS: '1',
        BT: '975',
        BW: '267',
        BY: '375',
        BZ: '501',
        CA: '1',
        CC: '61',
        CD: '243',
        CF: '236',
        CG: '242',
        CH: '41',
        CI: '225',
        CK: '682',
        CL: '56',
        CM: '237',
        CN: '86',
        CO: '57',
        CR: '506',
        CU: '53',
        CV: '238',
        CW: '599',
        CX: '61',
        CY: '357',
        CZ: '420',
        DE: '49',
        DJ: '253',
        DK: '45',
        DM: '1',
        DO: '1',
        DZ: '213',
        EC: '593',
        EE: '372',
        EG: '20',
        ER: '291',
        ES: '34',
        ET: '251',
        FI: '358',
        FJ: '679',
        FK: '500',
        FM: '691',
        FO: '298',
        FR: '33',
        GA: '241',
        GB: '44',
        GD: '1',
        GE: '995',
        GF: '594',
        GG: '44',
        GH: '233',
        GI: '350',
        GL: '299',
        GM: '220',
        GN: '224',
        GP: '590',
        GQ: '240',
        GR: '30',
        GT: '502',
        GU: '1',
        GW: '245',
        GY: '592',
        HK: '852',
        HN: '504',
        HR: '385',
        HT: '509',
        HU: '36',
        ID: '62',
        IE: '353',
        IL: '972',
        IM: '44',
        IN: '91',
        IO: '246',
        IQ: '964',
        IR: '98',
        IS: '354',
        IT: '39',
        JE: '44',
        JM: '1',
        JO: '962',
        JP: '81',
        KE: '254',
        KG: '996',
        KH: '855',
        KI: '686',
        KM: '269',
        KN: '1',
        KP: '850',
        KR: '82',
        KW: '965',
        KY: '1',
        KZ: '7',
        LA: '856',
        LB: '961',
        LC: '1',
        LI: '423',
        LK: '94',
        LR: '231',
        LS: '266',
        LT: '370',
        LU: '352',
        LV: '371',
        LY: '218',
        MA: '212',
        MC: '377',
        MD: '373',
        ME: '382',
        MF: '590',
        MG: '261',
        MH: '692',
        MK: '389',
        ML: '223',
        MM: '95',
        MN: '976',
        MO: '853',
        MP: '1',
        MQ: '596',
        MR: '222',
        MS: '1',
        MT: '356',
        MU: '230',
        MV: '960',
        MW: '265',
        MX: '52',
        MY: '60',
        MZ: '258',
        NA: '264',
        NC: '687',
        NE: '227',
        NF: '672',
        NG: '234',
        NI: '505',
        NL: '31',
        NO: '47',
        NP: '977',
        NR: '674',
        NU: '683',
        NZ: '64',
        OM: '968',
        PA: '507',
        PE: '51',
        PF: '689',
        PG: '675',
        PH: '63',
        PK: '92',
        PL: '48',
        PM: '508',
        PR: '1',
        PS: '970',
        PT: '351',
        PW: '680',
        PY: '595',
        QA: '974',
        RE: '262',
        RO: '40',
        RS: '381',
        RU: '7',
        RW: '250',
        SA: '966',
        SB: '677',
        SC: '248',
        SD: '249',
        SE: '46',
        SG: '65',
        SH: '290',
        SI: '386',
        SJ: '47',
        SK: '421',
        SL: '232',
        SM: '378',
        SN: '221',
        SO: '252',
        SR: '597',
        SS: '211',
        ST: '239',
        SV: '503',
        SX: '1',
        SY: '963',
        SZ: '268',
        TC: '1',
        TD: '235',
        TG: '228',
        TH: '66',
        TJ: '992',
        TK: '690',
        TL: '670',
        TM: '993',
        TN: '216',
        TO: '676',
        TR: '90',
        TT: '1',
        TV: '688',
        TW: '886',
        TZ: '255',
        UA: '380',
        UG: '256',
        US: '1',
        UY: '598',
        UZ: '998',
        VA: '39',
        VC: '1',
        VE: '58',
        VG: '1',
        VI: '1',
        VN: '84',
        VU: '678',
        WF: '681',
        WS: '685',
        YE: '967',
        YT: '262',
        ZA: '27',
        ZM: '260',
        ZW: '263'
    };

    return regionToPhoneCode[region] ?? null;
};
