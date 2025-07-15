const acym_helperJoomla = {
    setWidthJoomlaContent: function () {
        let $leftMenu = jQuery('#acym__joomla__left-menu');
        if (!$leftMenu.length) return;

        let menuWidth = $leftMenu.width();
        if (jQuery(window).width() < 640) {
            menuWidth = 0;
            $leftMenu.css({'display': 'none'});
        } else {
            $leftMenu.css({'display': 'block'});
        }

        jQuery('#acym_content, .acym_no_foundation').css({
            'width': 'calc(100% - ' + menuWidth + 'px)',
            'margin-left': menuWidth + 'px'
        });
        jQuery('#system-message-container').css({
            'margin-left': (menuWidth + 15) + 'px'
        });
    },
    setJoomlaLeftMenu: function () {
        let $leftMenu = jQuery('#acym__joomla__left-menu');
        let $buttonToggleLeftMenu = jQuery('#acym__joomla__left-menu--toggle');
        let $textLeftMenu = jQuery('#acym__joomla__left-menu a span');
        let $iTagforCollapse = jQuery('#acym__joomla__left-menu--toggle i');
        jQuery('.btn-subhead').hide();

        if ($buttonToggleLeftMenu.css('display') != 'none') {
            if ($leftMenu.hasClass('collapsed')) {
                $textLeftMenu.hide();
                $leftMenu.css({'width': '34px'});
                $iTagforCollapse.removeClass('acymicon-keyboard-arrow-left').addClass('acymicon-keyboard-arrow-right');
            } else {
                $textLeftMenu.show();
                $leftMenu.css({'width': '210px'});
                $iTagforCollapse.removeClass('acymicon-keyboard-arrow-right').addClass('acymicon-keyboard-arrow-left');
            }

            acym_helperJoomla.setWidthJoomlaContent();

            $buttonToggleLeftMenu.off('click').on('click', function () {
                if ($leftMenu.hasClass('collapsed')) {
                    $leftMenu.removeClass('collapsed');
                    $textLeftMenu.show();
                    $leftMenu.css({'width': '210px'});
                    $iTagforCollapse.removeClass('acymicon-keyboard-arrow-right').addClass('acymicon-keyboard-arrow-left');
                    acym_helper.setCookie('menuJoomla', '', 365);
                } else {
                    $leftMenu.addClass('collapsed');
                    $textLeftMenu.hide();
                    $leftMenu.css({'width': '34px'});
                    $iTagforCollapse.removeClass('acymicon-keyboard-arrow-left').addClass('acymicon-keyboard-arrow-right');
                    acym_helper.setCookie('menuJoomla', 'collapsed', 365);
                }
                acym_helperJoomla.setWidthJoomlaContent();
            });
        }

        jQuery('.btn-navbar').off('click').on('click', function () {
            $leftMenu.hide();
        });

        jQuery('#acym__joomla__left-menu--show').off('click').on('click', function () {
            let $navButton = jQuery('.btn-navbar');
            if (!$navButton.hasClass('collapsed')) {
                jQuery('.nav-collapse').css('height', '0px').removeClass('in');
                $navButton.removeClass('collapsed');
            }
            $leftMenu.toggle();
        });

        jQuery(window).on('resize', function () {
            if (window.innerWidth < 950 && !$leftMenu.hasClass('collapsed')) {
                $buttonToggleLeftMenu.trigger('click');
            }
            acym_helperJoomla.setWidthJoomlaContent();
        });
    },
    adjustContainerMainWidth: function () {
        let $leftMenu = jQuery('#acym__joomla__left-menu');
        if ($leftMenu.length) return;

        console.log('coucou');

        const $sidebar = jQuery('#sidebar-wrapper');
        const $container = jQuery('.container-fluid.container-main');

        if ($sidebar.length === 0 || $container.length === 0) return;

        const sidebarWidth = $sidebar.outerWidth() || 0;

        $container.css({
            'max-width': `calc(100vw - ${sidebarWidth}px - 15px)`
        });
    }
};
