const acym_helperInput = {
    setInputFile: function () {
        jQuery('.acym__button__file').off('click').on('click', function () {
            let $button = jQuery(this);
            let $inputFile = $button.prev();
            $inputFile.trigger('click');
            $inputFile.off('change').on('change', function () {
                $button.next().html(jQuery(this).val().split('\\').pop());
            });
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
