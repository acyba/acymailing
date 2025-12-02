const acym_helperUser = {
    setSubscribeUnsubscribeUser: function () {
        let form = jQuery('#acym_form');

        jQuery('.acym__user__action--subscribe').off('click').on('click', function () {
            jQuery('[name="acym__entity_select__selected"]').val(`[${jQuery(this).attr('acym-data-id')}]`);
            form.find('[name="task"]').attr('value', 'subscribeUser');
            form.trigger('submit');
        });

        jQuery('.acym__user__action--unsubscribe').off('click').on('click', function () {
            jQuery('[name="acym__entity_select__selected"]').val(`[${jQuery(this).attr('acym-data-id')}]`);
            form.find('[name="task"]').attr('value', 'unsubscribeUser');
            form.trigger('submit');
        });

        jQuery('.acym__user__action--reset').off('click').on('click', function () {
            jQuery('[name="acym__entity_select__selected"]').val(`[${jQuery(this).attr('acym-data-id')}]`);
            form.find('[name="task"]').attr('value', 'resetSubscription');
            form.trigger('submit');
        });

        jQuery('.acym__user__action--unsubscribeall').off('click').on('click', function () {
            form.find('[name="task"]').attr('value', 'unsubscribeUserFromAll');
            form.trigger('submit');
        });

        jQuery('.acym__user__action--resubscribeall').off('click').on('click', function () {
            form.find('[name="task"]').attr('value', 'resubscribeUserToAll');
            form.trigger('submit');
        });
    }
};
