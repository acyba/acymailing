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
        let $modalContainerStructure = jQuery('.acym__wysid__context__modal__container--structure');
        if ($th.length <= 1) {
            $modalContainerStructure.hide();
            return true;
        }

        acym_helperBlockSeparator.initPadding();
        acym_helperBlockSeparator.initBackgroundColor();

        $modalContainerStructure.show();
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
            acym_editorWysidRowSelector.setZoneAndBlockOverlays();
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
        let html = `<h6 class="cell margin-top-1 acym__wysid__context__block__padding__title">${ACYM_JS_TXT.ACYM_SPACE_BETWEEN_BLOCK}</h6>`;
        $paddingContainer.html(html);

        let horizontalPadding = '';
        let verticalPadding = '';
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
            content += `<input type="number" min="0" id="acym__wysid__context__block__padding-${i}" value="${paddingGlobal}" class="cell margin-bottom-0 small-4 margin-left-1 acym__wysid__context__block__padding--input">`;
            content += '</div>';

            horizontalPadding += content;


            //add the input to change this value
            let contentVertical = `<div class="cell grid-x acym_vcenter margin-bottom-1"><p class="cell shrink">${textToDisplay}</p>`;
            contentVertical += `<input type="number" min="0" id="acym__wysid__context__block__vertical__padding-${i}" value="0" class="cell margin-bottom-0 small-4 margin-left-1 acym__wysid__context__block__vertical__padding--input">`;
            contentVertical += '</div>';
            verticalPadding += contentVertical;
        }
        html += `<div class="cell grid-x small-6 acym__wysid__context__block__padding__horizontal">`;
        html += `<p class="cell margin-bottom-1">${ACYM_JS_TXT.ACYM_HORIZONTAL_PADDING} ${acym_helperTooltip.addInfo(ACYM_JS_TXT.ACYM_HORIZONTAL_PADDING_DESC)}</p>`;
        html += horizontalPadding;
        html += `</div>`;


        html += `<div class="cell grid-x small-6">`;
        html += `<p class="cell margin-bottom-1">${ACYM_JS_TXT.ACYM_VERTICAL_PADDING} ${acym_helperTooltip.addInfo(ACYM_JS_TXT.ACYM_VERTICAL_PADDING_DESC)}</p>`;
        html += verticalPadding;
        html += `</div>`;
        $paddingContainer.html(html);
        acym_helperTooltip.setTooltip();
        acym_helperBlockSeparator.setPadding($th);
        acym_helperBlockSeparator.setVerticalPadding($th);
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
    },
    setVerticalPadding: function ($th) {
        let $parentTable = jQuery($th[0]).closest('.acym__wysid__row__element');
        if ($parentTable.attr('id') === '' || $parentTable.attr('id') === undefined) {
            $parentTable.attr('id', this.generatedNewIdParentTable());
        }

        let $styleTag = jQuery(`[data-vertical-padding="${$parentTable.attr('id')}"]`);
        for (let i = 0 ; i < $th.length ; i++) {
            if (jQuery($th[i]).attr('class').indexOf('acym__wysid__row__element__th__vertical__padding') === -1) {
                jQuery($th[i]).addClass('acym__wysid__row__element__th__vertical__padding-' + i);
            } else if ($styleTag.length > 0) {
                let styleTagText = $styleTag.html();
                let rulePaddingTop = `acym__wysid__row__element__th__vertical__padding-${i}{padding-top: ([0-9]*)px`;
                let rulePaddingBottom = `acym__wysid__row__element__th__vertical__padding-${i}{padding-bottom: ([0-9]*)px`;

                let topRegex = new RegExp(rulePaddingTop, 'g');
                let bottomRegex = new RegExp(rulePaddingBottom, 'g');
                let topPadding = topRegex.exec(styleTagText);
                let bottomPadding = bottomRegex.exec(styleTagText);

                if (topPadding !== null) {
                    let $previousInput = jQuery(`#acym__wysid__context__block__vertical__padding-${i - 1}`);
                    $previousInput.val(parseInt($previousInput.val()) + parseInt(topPadding[1]));
                }
                if (bottomPadding !== null) {
                    let $currentInput = jQuery(`#acym__wysid__context__block__vertical__padding-${i}`);
                    $currentInput.val(parseInt($currentInput.val()) + parseInt(bottomPadding[1]));
                }
            }
        }


        jQuery('.acym__wysid__context__block__vertical__padding--input').off('change').on('change', function () {
            let $self = jQuery(this);
            let valueEntered = $self.val();
            if (valueEntered < 0) {
                $self.val(0);
                return;
            }

            $styleTag = jQuery(`[data-vertical-padding="${$parentTable.attr('id')}"]`);

            if ($styleTag.length > 0) $styleTag.remove();

            let style = '';
            jQuery('.acym__wysid__context__block__vertical__padding--input').each((index, $input) => {
                $input = jQuery($input);
                let indexUp = index + 1;
                let inputValue = Math.round($input.val() / 2);
                style += `#${$parentTable.attr('id')} .acym__wysid__row__element__th__vertical__padding-${index}{padding-bottom: ${inputValue}px !important}`;
                style += `#${$parentTable.attr('id')} .acym__wysid__row__element__th__vertical__padding-${indexUp}{padding-top: ${inputValue}px !important}`;
            });

            $parentTable.prepend(`<style data-vertical-padding="${$parentTable.attr('id')}">
                                            @media screen and (max-width: 480px){
                                                ${style}
                                            }
                                         </style>`);
        });
    },
    generatedNewIdParentTable: function () {
        let id = 'acym__wysid__row__element' + Math.floor(Math.random() * Math.floor(9999999));

        if (jQuery(`#${id}`).length > 0) {
            return this.generatedNewIdParentTable();
        } else {
            return id;
        }
    },
    changeIdOnduplicate: function ($element) {
        let newId = this.generatedNewIdParentTable();
        let formerId = $element.attr('id');
        if (formerId === undefined) return $element;
        $element.attr('id', newId);

        let $styleTag = $element.find('[data-vertical-padding]');
        if ($styleTag.length === 0) return $element;

        let styleTagHtml = $styleTag.html();
        $styleTag.attr('data-vertical-padding', newId);
        let formerIdRegex = new RegExp(formerId, 'g');
        $styleTag.html(styleTagHtml.replace(formerIdRegex, newId));

        return $element;
    },
    initBackgroundColor: function () {
        let $th = jQuery('.acym__wysid__row__element--focus .acym__wysid__row__element__th');
        let $paddingContainer = jQuery('.acym__wysid__context__modal__block-background');
        let html = `<h6 class="cell shrink acym__wysid__context__block__background__title margin-right-1">
                        ${ACYM_JS_TXT.ACYM_BACKGROUND_COLOR}${acym_helperTooltip.addInfo(ACYM_JS_TXT.ACYM_BACKGROUND_COLOR_DESC)}:
                    </h6>`;

        for (let i = 0 ; i < $th.length ; i++) {
            html += `<div class="cell large-2 grid-x acym_vcenter">
                        <span class="cell shrink">${this.blockName[i]}:</span>
                        <input class="cell shrink acym__wysid__context__block__background__color-picker" data-acym-block="${i}">
                      </div>`;
        }
        $paddingContainer.html(html);
        acym_helperTooltip.setTooltip();

        this.setBackgroundColor($th);
    },
    setBackgroundColor: function ($th) {
        let $inputsColor = jQuery('.acym__wysid__context__block__background__color-picker');

        $inputsColor.each(function () {
            let $table = jQuery($th[jQuery(this).attr('data-acym-block')]);
            acym_editorWysidColorPicker.setColorPickerForContextModal(jQuery(this), 'background', $table, $table, 'background', true);
        });
    }
};
