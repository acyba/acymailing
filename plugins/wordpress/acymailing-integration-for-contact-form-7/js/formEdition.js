jQuery(function ($) {
    $(document).on('change', 'select.acym-cf7', function () {
        const $select = $(this);
        const selected = $select.val();
        const combined = Array.isArray(selected) ? selected.join('-') : (
            selected || ''
        );

        const $hidden = $select.siblings('input[data-tag-option]');
        $hidden.val(combined);
        $hidden.get(0).dispatchEvent(new Event('change', {bubbles: true}));
    });
});
