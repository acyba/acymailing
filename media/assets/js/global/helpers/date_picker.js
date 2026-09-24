const acym_helperDatePicker = {
    setDatePickerGlobal: function () {
        jQuery('.acy_date_picker').each(function () {
            if (this.tagName === 'INPUT' && this.type !== 'datetime-local') {
                this.type = 'datetime-local';
            }

            let value = jQuery(this).attr('value');
            if (value && value.indexOf(' ') !== -1) {
                jQuery(this).val(value.replace(' ', 'T').substring(0, 16));
            }

            jQuery(this).off('focus.acyDefaultDate').on('focus.acyDefaultDate', function () {
                if (this.value === '') {
                    this.value = acym_helperDatePicker.currentDateTime();
                }
            });
        });
    },
    currentDateTime: function () {
        if (typeof ACYM_DATE_NOW !== 'undefined' && ACYM_DATE_NOW) {
            return ACYM_DATE_NOW;
        }

        let now = new Date();
        let pad = value => (value < 10 ? '0' : '') + value;

        return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
    },
    setRelativeTime: function ($element) {
        let $parent = $element.closest('.reveal');
        let time = parseInt($parent.find('.relativenumber').val()) * parseInt($parent.find('.relativetype').val());

        jQuery('[data-rs="' + $parent.attr('id') + '"]').val('[time]' + ((time > 0) ? $parent.find('.relativewhen').val() + time : ''));
        jQuery('[data-open="' + $parent.attr('id') + '"]').val('[time]' + ((time > 0) ? $parent.find('.relativewhen').val() + time : '')).trigger('change');
    },
    setSpecificTime: function ($element) {
        let $parent = $element.closest('.reveal');
        let $intput = $parent.find('[name^="specific_"]');
        jQuery('[data-rs="' + $parent.attr('id') + '"]').val(moment($intput.val()).unix());
        jQuery('[data-open="' + $parent.attr('id') + '"]').val($intput.val().replace('T', ' ')).trigger('change');
    },
    setRSDateChoice: function () {
        jQuery('.acym__button__clear__time').on('click', function () {
            let identifier = jQuery(this).closest('.reveal').attr('id');
            jQuery('[data-rs="' + identifier + '"]').val('');
            jQuery('[data-open="' + identifier + '"]').val('').trigger('change');
        });

        jQuery('.acym__button__set__time').on('click', function () {
            if ('relative' === jQuery(this).closest('.reveal').find('.date_rs_selection.is-active').attr('data-type')) {
                acym_helperDatePicker.setRelativeTime(jQuery(this));
            } else {
                acym_helperDatePicker.setSpecificTime(jQuery(this));
            }
        });

        jQuery('.date_rs_selection').off('click').on('click', function (e) {
            e.preventDefault();
            let $parent = jQuery(this).closest('.date_rs_selection_popup');
            $parent.find('.date_rs_selection').removeClass('is-active');
            jQuery(this).addClass('is-active');
            $parent.find('.date_rs_selection_choice').hide();
            $parent.find('.date_rs_selection_' + jQuery(this).attr('data-type')).show();
        });
        acym_helperDatePicker.resetThePopup();
    },
    resetThePopup: function () {
        jQuery('.rs_date_field').on('click', function () {
            let $self = jQuery(this);
            let $input = jQuery(`[data-rs="${$self.attr('data-open')}"]`);
            let $modal = jQuery(`#${$self.attr('data-open')}`);

            if (undefined === $input.val() || '' === $input.val() || $input.val() === '[time]') {
                $modal.find('.relativetype').val('60').trigger('change');
                $modal.find('.relativenumber').val('0').trigger('change');
                return true;
            }

            if ($input.val().indexOf('[time]') !== -1) {
                $modal.find('[data-type="relative"]').trigger('click');
                let operator = $input.val().indexOf('-') !== -1 ? '-' : '+';
                let splitValue = $input.val().split(operator);

                let seconds = parseInt(splitValue[1]);

                $modal.find('.relativewhen').val(operator).trigger('change');

                let finalValuePopup;
                let timelapse;
                if (seconds >= 86400 && seconds % 86400 === 0) {
                    timelapse = 86400;
                } else if (seconds >= 3600 && seconds % 3600 === 0) {
                    timelapse = 3600;
                } else {
                    timelapse = 60;
                }
                finalValuePopup = seconds / timelapse;


                $modal.find('.relativetype').val(timelapse).trigger('change');
                $modal.find('.relativenumber').val(finalValuePopup).trigger('change');
            } else {
                $modal.find('[data-type="specific"]').trigger('click');
                //We convert the unix time to the format expected by the native datetime-local input
                $modal.find('.acy_date_picker').val(moment.unix($input.val()).format('YYYY-MM-DDTHH:mm'));
            }
        });
    }
};
