const acym_helperInput = {
    setInputFile: function () {
        jQuery('.acym__button__file').off('click').on('click', function () {
            let $button = jQuery(this);
            let $inputFile = $button.prev();
            $inputFile.click();
            $inputFile.off('change').on('change', function () {
                $button.next().html(jQuery(this).val().split('\\').pop());
            });
        });
    }
};
