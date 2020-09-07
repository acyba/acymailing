const acym_helperTooltip = {
    setTooltip: function () {
        jQuery('.acym__tooltip, [data-acym-tooltip]').off('mouseenter').on('mouseenter', function () {
            if (undefined !== jQuery(this).attr('data-acym-tooltip')) {
                jQuery(this).addClass('acym__tooltip');
                let position = jQuery(this).attr('data-acym-tooltip-position') !== undefined ? 'acym__tooltip__text__' + jQuery(this)
                    .attr('data-acym-tooltip-position') : '';
                if (jQuery(this).find('.acym__tooltip__text').length == 0) {
                    jQuery(this)
                        .append('<span class="acym__tooltip__text ' + position + '">' + jQuery(this).attr('data-acym-tooltip') + '</span>');
                }
            }
            acym_helperTooltip.setPositionTooltip(jQuery(this).find('.acym__tooltip__text'), jQuery(this));
        });
    },
    setPositionTooltip: function ($tooltipText, $parent) {
        if ($tooltipText.hasClass('wysid_tooltip')) {
            $tooltipText.css({
                position: 'absolute',
                top: ($parent.outerHeight() + 14) + 'px',
                left: '-' + ((250 - $parent.outerWidth()) / 2) + 'px',
                width: '250px'
            });
            return;
        }

        let pos = $parent.offset();
        let scrollTop = jQuery(window).scrollTop();
        let top = pos.top - scrollTop;
        let newTop, newLeft;

        if ($tooltipText.hasClass('acym__tooltip__text__right')) {
            newTop = (top - $tooltipText.height()) < 0 ? 0 : top - $tooltipText.height();
            newLeft = (pos.left + $parent.width() + 10) < 0 ? 0 : pos.left + $parent.width() + 10;
        } else if ($tooltipText.hasClass('acym__tooltip__text__left')) {
            newTop = (top - $tooltipText.height()) < 0 ? 0 : top - $tooltipText.height();
            newLeft = (pos.left - $tooltipText.width() - $parent.width() - 10) < 0 ? 0 : pos.left - $tooltipText.width() - $parent.width() - 10;
        } else {
            newTop = Math.max(0, top - $tooltipText.height() - 15);
            newLeft = Math.max(0, pos.left - $tooltipText.width() / 2 + $parent.width() / 2);
        }

        $tooltipText.css({
            top: newTop + 'px',
            left: newLeft + 'px'
        });
    },
    addTooltip: function (hoveredText, textShownInTooltip, classContainer, classText) {
        return `<span class="acym__tooltip ${classContainer}"><span class="acym__tooltip__text ${classText}">${textShownInTooltip}</span>${hoveredText}</span>`;
    },
    addInfo: function (tooltipText, classText) {
        classText = classText === undefined ? '' : classText;
        return this.addTooltip(
            `<span class="acym__tooltip__info__container"><i class="acym__tooltip__info__icon acymicon-info-circle"></i></span>`,
            tooltipText,
            'acym__tooltip__info',
            classText
        );
    }
};
