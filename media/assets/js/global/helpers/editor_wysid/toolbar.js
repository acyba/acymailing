const acym_editorWysidToolbar = {
    setRightToolbarWYSID: function () {
        jQuery('.acym__wysid__right__toolbar__tabs').off('click').on('click', function () {
            acym_editorWysidToolbar.setSlideRightToolbarWYSID(jQuery(this));
        });

        jQuery('.acym__wysid__right__toolbar--menu .acym__wysid__right__toolbar__p').off('click').on('click', function () {
            jQuery(this).next().slideToggle(200);

            if (jQuery(this).hasClass('acym__wysid__right__toolbar__p__open')) {
                jQuery(this).removeClass('acym__wysid__right__toolbar__p__open');
                jQuery(this).find('i').removeClass('acymicon-keyboard_arrow_up').addClass('acymicon-keyboard_arrow_down');
            } else {
                jQuery(this).addClass('acym__wysid__right__toolbar__p__open');
                jQuery(this).find('i').removeClass('acymicon-keyboard_arrow_down').addClass('acymicon-keyboard_arrow_up');
            }

            jQuery(this).toggleClass('acym__wysid__right__toolbar__last--text');
        });
    },
    setSlideRightToolbarWYSID: function ($clickedTab) {
        let $elementToHide = jQuery('#' + jQuery('.acym__wysid__right__toolbar__selected').attr('data-attr-show'));
        let $elementToShow = jQuery('#' + $clickedTab.attr('data-attr-show'));
        if ($elementToHide.attr('id') === $elementToShow.attr('id')) return;
        let $tabs = jQuery('.acym__wysid__right__toolbar__tabs');
        $tabs.removeClass('acym__wysid__right__toolbar__selected');
        let direction = {
            hide: '',
            show: ''
        };
        $tabs.each(function () {
            if (jQuery(this).attr('data-attr-show') === $elementToHide.attr('id')) {
                direction.hide = 'left';
                direction.show = 'right';
                return false;
            }
            if (jQuery(this).attr('data-attr-show') === $elementToShow.attr('id')) {
                direction.hide = 'right';
                direction.show = 'left';
                return false;
            }
        });
        $clickedTab.addClass('acym__wysid__right__toolbar__selected');
        $elementToHide.hide('slide', {direction: direction.hide}, 75, function () {
            $elementToShow.show('slide', {direction: direction.show}, 75, function () {
                acym_editorWysidToolbar.setRightToolbarWYSID();
            });
        });
    }
};
