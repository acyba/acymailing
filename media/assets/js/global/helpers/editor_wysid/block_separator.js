const acym_helperBlockSeparator = {
    $container: '',
    onePartContainer: '',
    rightBorder: '',
    leftBorder: '',
    xAxis: 10,
    maxXAxis: 10,
    newXAxis: '',
    distanceMax: 5,
    blockName: {
        0: 'A',
        1: 'B',
        2: 'C',
        3: 'D'
    },
    initBlockSeparator: function () {
        acym_helperBlockSeparator.$container = jQuery('.acym__wysid__context__modal__container__block-settings');
        if (acym_helperBlockSeparator.$container.length < 1) return true;
        acym_helperBlockSeparator.$container.html(acym_helperBlockSeparator.areaGrid());
        setTimeout(() => {
            acym_helperBlockSeparator.initRows();
            acym_helperBlockSeparator.onePartContainer = (acym_helperBlockSeparator.$container[0].offsetWidth / 12);
            acym_helperBlockSeparator.initMouseDown();
        }, 500);
    },
    initRows: function () {
        let $row = jQuery('.acym__wysid__row__element--focus');
        let $th = $row.find('.acym__wysid__row__element__th');
        if ($th.length <= 1) {
            jQuery('.acym__wysid__context__modal__container--structure').hide();
            return true;
        }
        acym_helperBlockSeparator.initPadding();
        jQuery('.acym__wysid__context__modal__container--structure').show();
        $th.each(function (index) {
            let classValue = acym_helperBlockSeparator.getLargeClass(jQuery(this));
            let needRight = $th[index + 1] !== undefined;
            let needLeft = $th[index - 1] !== undefined;
            acym_helperBlockSeparator.$container.append(acym_helperBlockSeparator.generateArea(classValue[1], needRight, needLeft, index));
        });
    },
    elasticSVG: function (event) {
        let $path = jQuery('.separator_grabbed').find('path');
        let posLeftPath = $path.offset().left;
        let grabbingRight = event.clientX > posLeftPath;
        let distance = grabbingRight ? event.clientX - posLeftPath : posLeftPath - event.clientX;
        let plusOnX = distance > acym_helperBlockSeparator.distanceMax ? acym_helperBlockSeparator.maxXAxis : ((distance * acym_helperBlockSeparator.maxXAxis)
                                                                                                               / acym_helperBlockSeparator.distanceMax);
        acym_helperBlockSeparator.newXAxis = grabbingRight ? acym_helperBlockSeparator.xAxis + plusOnX : acym_helperBlockSeparator.xAxis - plusOnX;
        $path.attr('d', 'M 10,10 C 10 10, ' + acym_helperBlockSeparator.newXAxis + ' 50, 10 90');
    },
    releaseSVG: function () {
        let beforeResetAxis = acym_helperBlockSeparator.newXAxis > acym_helperBlockSeparator.xAxis
                              ? acym_helperBlockSeparator.xAxis - 6
                              : acym_helperBlockSeparator.xAxis + 6;
        let $separatorGrabbed = jQuery('.separator_grabbed');
        $separatorGrabbed.find('path').attr('d', 'M 10,10 C 10 10, ' + beforeResetAxis + ' 50, 10 90');
        setTimeout(() => {
            $separatorGrabbed.find('path').attr('d', 'M 10,10 C 10 10, ' + acym_helperBlockSeparator.xAxis + ' 50, 10 90');
        }, 150);
    },
    initMouseDown: function () {
        jQuery('.separator_right, .separator_left').off('mousedown').on('mousedown', function (event) {
            jQuery('.acym__wysid__row__selector, .acym__wysid__element__toolbox').remove();
            let $separator = jQuery(this);
            $separator.addClass('separator_grabbed');
            acym_helperBlockSeparator.$container.find('.acym__wysid__context__modal__container__block-settings--grid').addClass('grid-visible');
            let $parent = jQuery(this).closest('.cell');
            let $parentSiblings = $parent.next();
            let $parentSiblingsBefore = $parent.prev('.acym__block__separator__area');
            acym_helperBlockSeparator.initMouseMove($parent, $parentSiblings, $parentSiblingsBefore, $separator.attr('class').indexOf('left') !== -1);
            acym_helperBlockSeparator.setMouseUp($separator);
        });
    },
    setMouseUp: function ($separator) {
        jQuery(document).off('mouseup').on('mouseup', function () {
            jQuery(document).off('mousemove');
            acym_helperBlockSeparator.releaseSVG();
            $separator.removeClass('separator_grabbed');
            acym_helperBlockSeparator.$container.find('.grid-visible').removeClass('grid-visible');
            acym_editorWysidRowSelector.setRowSelector();
        });
    },
    setRightBorder: function ($parent) {
        acym_helperBlockSeparator.rightBorder = parseInt($parent.offset().left) + parseInt($parent.width()) - 20;
    },
    setLeftBorder: function ($parent) {
        acym_helperBlockSeparator.leftBorder = parseInt($parent.offset().left);
    },
    changeLength: function ($el, number) {
        $el.find('.area__length').html(number);
    },
    getLargeClass: function ($el) {
        let className = $el.attr('class').match(/(large\-)([0-9]*)/);
        if (null !== className) {
            return [
                className[1],
                parseInt(className[2])
            ];
        }
        return false;
    },
    initMouseMove: function ($parent, $parentSiblings, $parentSiblingsBefore, isLeft) {
        acym_helperBlockSeparator.setRightBorder($parent);
        acym_helperBlockSeparator.setLeftBorder($parent);
        jQuery(document).off('mousemove').on('mousemove', function (event) {
            acym_helperBlockSeparator.elasticSVG(event);
            let cursor = event.clientX;
            if (cursor > acym_helperBlockSeparator.rightBorder) {
                if (!isLeft && (acym_helperBlockSeparator.rightBorder + acym_helperBlockSeparator.onePartContainer) < cursor) {
                    acym_helperBlockSeparator.up($parent, $parentSiblings, $parentSiblingsBefore, false);
                }
            } else if (cursor < acym_helperBlockSeparator.rightBorder) {
                if (!isLeft && (acym_helperBlockSeparator.rightBorder - acym_helperBlockSeparator.onePartContainer) > cursor) {
                    acym_helperBlockSeparator.down($parent, $parentSiblings, $parentSiblingsBefore, false);
                }
            }
            if (cursor > acym_helperBlockSeparator.leftBorder) {
                if (isLeft && (acym_helperBlockSeparator.leftBorder + acym_helperBlockSeparator.onePartContainer) < cursor) {
                    acym_helperBlockSeparator.down($parent, $parentSiblings, $parentSiblingsBefore, true);
                }
            } else if (cursor < acym_helperBlockSeparator.leftBorder) {
                if (isLeft && (acym_helperBlockSeparator.leftBorder - acym_helperBlockSeparator.onePartContainer) > cursor) {
                    acym_helperBlockSeparator.up($parent, $parentSiblings, $parentSiblingsBefore, true);
                }
            }
        });
    },
    up: function ($parent, $parentSib, $parentSiblingsBefore, isLeft) {
        //we get the div to change
        let $parentSibToChange = isLeft ? $parentSiblingsBefore : $parentSib;
        let parentClass = acym_helperBlockSeparator.getLargeClass($parent);
        let parentSibClass = acym_helperBlockSeparator.getLargeClass($parentSibToChange);

        //if we are at the minimum we stop
        if (parentSibClass[1] === 1) return true;

        //we change the class values
        parentClass[1]++;
        parentSibClass[1]--;
        let newParentClass = parentClass[0] + parentClass[1];
        let newParentSibClass = parentSibClass[0] + parentSibClass[1];
        $parent.attr('class', 'cell acym__block__separator__area ' + newParentClass);
        $parentSibToChange.attr('class', 'cell acym__block__separator__area ' + newParentSibClass);
        acym_helperBlockSeparator.changeLength($parent, parentClass[1]);
        acym_helperBlockSeparator.changeLength($parentSibToChange, parentSibClass[1]);

        acym_helperBlockSeparator.changeInTemplate();

        //we stop and call back the mousemove event
        acym_helperBlockSeparator.initMouseMove($parent, $parentSib, $parentSiblingsBefore, isLeft);
    },
    down: function ($parent, $parentSib, $parentSiblingsBefore, isLeft) {
        //we get the div to change
        let $parentSibToChange = isLeft ? $parentSiblingsBefore : $parentSib;
        let parentClass = acym_helperBlockSeparator.getLargeClass($parent);
        let parentSibClass = acym_helperBlockSeparator.getLargeClass($parentSibToChange);

        //if we are at the minimum we stop
        if (parentClass[1] === 1) return true;

        //we change the class values
        parentClass[1]--;
        parentSibClass[1]++;
        let newParentClass = parentClass[0] + parentClass[1];
        let newParentSibClass = parentSibClass[0] + parentSibClass[1];
        $parent.attr('class', 'cell acym__block__separator__area ' + newParentClass);
        $parentSibToChange.attr('class', 'cell  acym__block__separator__area ' + newParentSibClass);
        acym_helperBlockSeparator.changeLength($parent, parentClass[1]);
        acym_helperBlockSeparator.changeLength($parentSibToChange, parentSibClass[1]);

        acym_helperBlockSeparator.changeInTemplate();

        //we stop and call back the mousemove event
        acym_helperBlockSeparator.initMouseMove($parent, $parentSib, $parentSiblingsBefore, isLeft);
    },
    generateArea: function (number, needRight, needLeft, indexTh) {
        let svg = '<svg width="20" height="100" class="sidebar" viewBox="0 0 20 100"><path class="s-path" fill="none" d="M 10,10 C 10 10, 10 50, 10 90" stroke-linecap="round"/></svg>';
        let rightSeparator = needRight ? `<div class="separator_right">${svg}</div>` : '';
        let leftSeparator = needLeft ? `<div class="separator_left">${svg}</div>` : '';
        return `<div class="cell large-${number} acym__block__separator__area"><p class="area__name">${acym_helperBlockSeparator.blockName[indexTh]}</p><p class="area__length">${number}</p>${rightSeparator}${leftSeparator}</div>`;
    },
    changeInTemplate: function () {
        let $row = jQuery('.acym__wysid__row__element--focus');
        let $th = $row.find('.acym__wysid__row__element__th');
        let classes = {};
        acym_helperBlockSeparator.$container.find(' > .acym__block__separator__area').each(function (index) {
            let largeClass = acym_helperBlockSeparator.getLargeClass(jQuery(this));
            classes[index] = largeClass[0] + largeClass[1];
        });
        $th.each(function (index) {
            let largeClass = acym_helperBlockSeparator.getLargeClass(jQuery(this));
            jQuery(this).removeClass(largeClass[0] + largeClass[1]);
            jQuery(this).addClass(classes[index]);
        });
    },
    areaGrid: function () {
        let gridArea = '<div class="cell grid-x acym-grid-margin-x acym__wysid__context__modal__container__block-settings--grid">';
        for (let i = 0 ; i < 12 ; i++) {
            gridArea += '<div class="cell large-1"></div>';
        }
        gridArea += '</div>';
        return gridArea;
    },
    initPadding: function () {
        let $th = jQuery('.acym__wysid__row__element--focus .acym__wysid__row__element__th');
        let $paddingContainer = jQuery('.acym__wysid__context__modal__block-padding');
        let html = `<h6 class="cell margin-top-1">${ACYM_JS_TXT.ACYM_SPACE_BETWEEN_BLOCK}</h6>`;
        $paddingContainer.html(html);
        for (let i = 0 ; i < ($th.length - 1) ; i++) {
            //get the padding of the current and next element and calculate the global
            let paddingElementLeft = acym_helper.getIntValueWithoutPixel($th[i].style.paddingRight);
            let paddingElementRight = acym_helper.getIntValueWithoutPixel($th[(i + 1)].style.paddingLeft);
            let paddingGlobal = paddingElementRight + paddingElementLeft;

            //blocks
            let blockLeft = acym_helperBlockSeparator.blockName[i];
            let blockRight = acym_helperBlockSeparator.blockName[i + 1];

            //text
            let textToDisplay = acym_helper.sprintf(ACYM_JS_TXT.ACYM_X1_AND_X2, blockLeft, blockRight);

            //add the input to change this value
            let content = `<div class="cell grid-x acym_vcenter margin-bottom-1"><p class="cell shrink">${textToDisplay}</p>`;
            content += `<input type="number" min="0" id="acym__wysid__context__block__padding-${i}" value="${paddingGlobal}" class="cell margin-bottom-0 small-2 margin-left-1 acym__wysid__context__block__padding--input">`;
            content += '</div>';

            html += content;
        }
        $paddingContainer.html(html);
        acym_helperBlockSeparator.setPadding($th);
    },
    setPadding: function ($th) {
        jQuery('.acym__wysid__context__block__padding--input').off('change').on('change', function (e) {
            //get the input him self
            let $self = jQuery(this);
            let valueEntered = $self.val();

            //check the value
            if (valueEntered < 0) {
                $self.val(0);
            }

            //if value ok then set it
            let idSplit = $self.attr('id').split('-');
            if (undefined === idSplit[1]) return false;
            let newValue = Math.round(valueEntered / 2);
            idSplit[1] = parseInt(idSplit[1]);
            jQuery($th[idSplit[1]]).css('padding-right', newValue + 'px');
            jQuery($th[(idSplit[1] + 1)]).css('padding-left', newValue + 'px');
        });
    }

};
