const acym_helperInput = {
    setInputFile: function () {
        jQuery('.acym__button__file').off('click').on('click', function () {
            let $button = jQuery(this);
            let $inputFile = $button.prev();
            $inputFile.trigger('click');
            $inputFile.off('change').on('change', function () {
                const fileName = jQuery(this).val().split('\\').pop();
                const $container = $button.closest('.acym__input__file__container');
                const $nameSpan = $container.find('.acym__input__file__name');
                const $downloadIcon = $container.find('.acym__input__file__download');
                const $deleteIcon = $container.find('.acym__input__file__delete');
                $nameSpan.html(fileName);
                if (fileName && this.files && this.files[0]) {
                    const oldBlobUrl = $downloadIcon.data('download-url');
                    if (oldBlobUrl && oldBlobUrl.indexOf('blob:') === 0) {
                        URL.revokeObjectURL(oldBlobUrl);
                    }
                    $downloadIcon.data('download-url', URL.createObjectURL(this.files[0]));
                    $downloadIcon.show();
                    $deleteIcon.show();
                }
            });
        });

        jQuery('.acym__input__file__delete').off('click').on('click', function () {
            const $container = jQuery(this).closest('.acym__input__file__container');
            const $inputFile = $container.find('input[type="file"]');
            const $nameSpan = $container.find('.acym__input__file__name');
            const $downloadIcon = $container.find('.acym__input__file__download');

            const oldBlobUrl = $downloadIcon.data('download-url');
            if (oldBlobUrl && oldBlobUrl.indexOf('blob:') === 0) {
                URL.revokeObjectURL(oldBlobUrl);
            }

            $inputFile.val('');
            $container.find('input[type="hidden"]').val('');
            $downloadIcon.removeData('download-url').hide();

            $nameSpan.html($nameSpan.data('no-file'));

            $container.find('.acym__input__file__delete').hide();
        });

        jQuery('.acym__input__file__download').off('click').on('click', function () {
            const downloadUrl = jQuery(this).data('download-url');
            if (downloadUrl) {
                Object.assign(document.createElement('a'), {
                    href: downloadUrl,
                    download: ''
                }).click();
            }
        });
    },
    setMulticouple: function () {
        jQuery('.multikeyvalue_container_new').off('click').on('click', function (event) {
            event.preventDefault();

            jQuery(this)
                .before('<div class="multikeyvalue_container_separator cell small-6"></div>'
                        + '<input type="text" class="cell" placeholder="'
                        + ACYM_JS_TXT.ACYM_DKIM_KEY
                        + '" value=""/>'
                        + '<input type="text" class="cell" placeholder="'
                        + ACYM_JS_TXT.ACYM_VALUE
                        + '" value="" />');
            acym_helperInput.setNewMulticouple();
        });

        acym_helperInput.setNewMulticouple();
    },
    setNewMulticouple: function () {
        jQuery('.multikeyvalue_container input[type="text"]').off('change').on('change', function () {
            let values = {};
            let inputs = jQuery('.multikeyvalue_container input[type="text"]');
            inputs.each(function (index) {
                if (index % 2 === 1) return;

                let key = jQuery(this).val();
                let value = inputs[index + 1].value;

                if (key.length !== 0 && value.length !== 0) {
                    values[key] = value;
                }
            });

            jQuery('.multikeyvalue_container input[type="hidden"]').val(JSON.stringify(values));
        });
    }
};
