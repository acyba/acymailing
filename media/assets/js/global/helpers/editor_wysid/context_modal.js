const acym_editorWysidContextModal = {
    clickedOnRightToolbar: function (event) {
        let $rightMenu = jQuery('#acym__wysid__right-toolbar');
        let offset = $rightMenu.offset();
        let left = Math.round(offset.left);

        let clickedBetweenLeftAndRightBorders = offset.left <= event.clientX && event.clientX <= (left + $rightMenu.width());
        if (!clickedBetweenLeftAndRightBorders) return false;

        let tabIds = [
            'acym__wysid__right__toolbar__design__tab',
            'acym__wysid__right__toolbar__settings__tab'
        ];
        let clickedElement = jQuery(event.target);
        let clickedElementId = clickedElement.attr('id');
        if (clickedElementId) {
            // If the clicked element is one of the main tabs, return false
            return tabIds.indexOf(clickedElementId) === -1;
        }

        // If we clicked on an icon of the main tabs, return false
        if (clickedElement.closest('#acym__wysid__right__toolbar__design__tab').length === 1) return false;
        if (clickedElement.closest('#acym__wysid__right__toolbar__settings__tab').length === 1) return false;

        // We clicked between the left and right borders of the toolbar, and we didn't click on the design or settings tabs
        return true;
    },
    hideBlockOptions: function ($contextModal, $element) {
        $contextModal.hide();
        jQuery('.acym__wysid__right__toolbar__current-block__empty').show();
        if ($element === undefined || !acym_editorWysidContextModal.isZoneOrBlock($element)) {
            jQuery('#acym__wysid__right__toolbar__design__tab').trigger('click');
        }
    },
    isZoneOrBlock: function ($element) {
        let isOpening = false;
        let classesOpeningContext = [
            'acymailing_content',
            'acym__wysid__tinymce--image',
            'acym__wysid__column__element__separator',
            'acym__wysid__column__element__share',
            'acym__wysid__column__element__follow',
            'acy-editor__space',
            'acym__wysid__column__element__button',
            'acym__wysid__row__selector',
            'acym__wysid__right__toolbar__tabs',
            'acym__wysid__tinymce--text'
        ];
        jQuery.each(classesOpeningContext, function (index, value) {
            if ($element.closest('.' + value).length > 0) isOpening = true;
        });

        return isOpening;
    },
    showBlockOptions: function ($blockOptions) {
        jQuery('.acym__wysid__context__modal').hide();
        jQuery('#acym__wysid__right__toolbar__block__tab').trigger('click');
        $blockOptions.show();
        jQuery('.acym__wysid__right__toolbar__current-block__empty').hide();
        jQuery('#acym__wysid__right__toolbar__current-block').off('mousedown').on('mousedown', function (event) {
            event.stopPropagation();
        });
    },
    setZoneOptions: function () {
        jQuery('.acym__wysid__row__selector').off('click').on('click', function (e) {
            e.stopPropagation();
            e.preventDefault();

            let previousColor = '#ffffff';

            jQuery('.acym__wysid__row__selector--focus').removeClass('acym__wysid__row__selector--focus');
            jQuery('.acym__wysid__row__element--focus').removeClass('acym__wysid__row__element--focus');
            let $table = jQuery(this).closest('.acym__wysid__row__element');
            let $selector = jQuery(this);
            $selector.addClass('acym__wysid__row__selector--focus');
            $selector.closest('.acym__wysid__row__element').addClass('acym__wysid__row__element--focus');

            acym_editorWysidColorPicker.setColorPickerForContextModal(jQuery('#acym__wysid__context__block__background-color'),
                'background-color',
                $table,
                $table,
                'background-color'
            );
            acym_editorWysidColorPicker.setColorPickerForContextModal(jQuery('#acym__wysid__context__block__border__color'),
                'border-color',
                $table,
                $table,
                'border-color'
            );
            acym_editorWysidColorPicker.setRowColorPickerWYSID($table);

            acym_editorWysidImage.addBackgroundImgToRows($table);

            if ($table.css('background-color') === 'transparent') {
                jQuery('.acym__wysid__context__block__transparent__bg').trigger('click');
            }

            jQuery('[name="transparent_background"]').next().off('change').on('change', function () {
                if (jQuery(this).is(':checked')) {
                    previousColor = $table.css('background-color');
                    $table.css('background-color', 'transparent').attr('bgcolor', 'transparent');
                } else {
                    $table.css('background-color', previousColor).attr('bgcolor', previousColor);
                }
            });

            acym_helperBlockSeparator.initBlockSeparator();

            let $idInput = jQuery('#acym__wysid__context__block__custom_id');

            $idInput.val($table.attr('id'));

            $idInput.off('keyup').on('keyup', function (event) {
                if (event.key === ' ') event.preventDefault();
                $table.attr('id', jQuery(this).val());
            });

            jQuery('[data-block-padding]').each(function () {
                jQuery(this).val($table.css('padding-' + jQuery(this).attr('data-block-padding')).replace(/[^-\d\.]/g, ''));
                jQuery(this).off('change').on('change', function (event) {
                    $table.css('padding-' + jQuery(this).attr('data-block-padding'), jQuery(this).val() + 'px');
                    if (jQuery(this).attr('data-block-padding') === 'top' || jQuery(this).attr('data-block-padding') === 'bottom') {
                        $selector.css('height', $table.height()
                                                + parseInt($table.css('padding-top').replace(/[^-\d\.]/g, ''))
                                                + parseInt($table.css('padding-bottom')
                                                                 .replace(/[^-\d\.]/g, ''))
                                                + 'px');
                    }
                });
            });

            jQuery('.acym__wysid__context__block__border__actions').each(function () {
                jQuery(this).val($table.css(jQuery(this).attr('data-css')).replace(/[^-\d\.]/g, ''));
                jQuery(this).off('change').on('change', function (event) {
                    if (jQuery(this).val() > parseInt(jQuery(this).attr('max'))) {
                        jQuery(this).val(parseInt(jQuery(this).attr('max')));
                        event.stopPropagation();
                    }
                    $table.css(jQuery(this).attr('data-css'), jQuery(this).val() + 'px');
                    if (jQuery(this).attr('data-css') === 'border-width') {
                        $table.css('border-style', jQuery(this).val() === 0 ? 'none' : 'solid');
                        let offsetTemplate = jQuery('.acym__wysid__template__content').offset();
                        let offsetTable = $selector.closest('.acym__wysid__row__element').offset();
                        let heightSelector = $table.height() + parseInt($table.css('padding-top').replace(/[^-\d\.]/g, '')) + parseInt($table.css(
                            'padding-bottom').replace(/[^-\d\.]/g, '')) + (parseInt($table.css('border-width').replace(/[^-\d\.]/g, '')) * 2);
                        $selector.css({
                            'left': '-' + (offsetTable.left - offsetTemplate.left + parseInt($selector.closest('.acym__wysid__row__element')
                                                                                                      .css('border-width')
                                                                                                      .replace(/[^-\d\.]/g, ''))) + 'px',
                            'height': heightSelector,
                            'top': (0 - parseInt($table.css('border-width').replace(/[^-\d\.]/g, ''))) + 'px'
                        });
                    }
                });
            });

            jQuery('#acym__wysid__context__block__edit-html').off('click').on('click', function () {
                let event = new Event('editor_change');
                let contentInput = document.getElementById('acym__wysid__block__html__content');
                let $tbody = $table.find('> tbody');
                $tbody.find('.acym__wysid__element__toolbox').remove();
                contentInput.value = $tbody.html();
                contentInput.dispatchEvent(event);
                jQuery('#acym__wysid__editor__source, #acym__wysid__right-toolbar__overlay').addClass('acym__wysid__visible');
            });

            let $contextBlock = jQuery('#acym__wysid__context__block');
            acym_editorWysidContextModal.showBlockOptions($contextBlock);

            $contextBlock.off('mousedown').on('mousedown', function (event) {
                jQuery('.sp-container').addClass('sp-hidden').attr('style', '');
                event.stopPropagation();
            });

            jQuery(window).off('mousedown').on('mousedown', function (event) {
                if (acym_editorWysidContextModal.clickedOnRightToolbar(event)) return true;
                let target = jQuery(event.target);

                if (target.hasClass('acym__wysid__row__selector')) return true;
                if (jQuery('.sp-container').is(':visible')) return true;
                if (target.closest('#acym__wysid__editor__source').length > 0) return true;
                if (target.closest('#acym__wysid__modal').length > 0) return true;

                jQuery('#acym__wysid__editor__source, #acym__wysid__right-toolbar__overlay').removeClass('acym__wysid__visible');
                jQuery('.acym__wysid__row__selector--focus').removeClass('acym__wysid__row__selector--focus');
                jQuery('.acym__wysid__row__element--focus').removeClass('acym__wysid__row__element--focus');
                previousColor = '#ffffff';
                jQuery(this).off('mousedown');
                acym_editorWysidContextModal.hideBlockOptions($contextBlock, target);
                jQuery('.acym__wysid__context__modal__container--structure').hide();
                if (!target.parent().hasClass('acym__wysid__tinymce--text')) {
                    acym_helperEditorWysid.setColumnRefreshUiWYSID(false);
                }
                acym_editorWysidVersioning.setUndoAndAutoSave();
            });
        });
    },
    showImageOptions: function ($img) {
        let insertedImage = jQuery('.acym__wysid__media__inserted--selected');
        insertedImage.removeClass('acym__wysid__media__inserted--selected');
        $img.addClass('acym__wysid__media__inserted--selected');
        let $contextImage = jQuery('#acym__wysid__context__image');
        let $alignments = jQuery('.acym__wysid__context__image__align');
        let $linkInput = jQuery('#acym__wysid__context__image__link');
        let $urlInput = jQuery('#acym__wysid__context__image__url');
        let $altInput = jQuery('#acym__wysid__context__image__alt');
        let $titleInput = jQuery('#acym__wysid__context__image__title');
        let $captionInput = jQuery('#acym__wysid__context__image__caption');
        let $aTag = $img.closest('.acym__wysid__link__image');
        let $widthInput = jQuery('#acym__wysid__context__image__width');
        let $heightInput = jQuery('#acym__wysid__context__image__height');

        // Initialize input value
        let $imgSelected = jQuery('.acym__wysid__media__inserted--selected');
        $widthInput.val(Math.trunc($imgSelected.width()));
        $heightInput.val(Math.trunc($imgSelected.height()));

        $alignments.css('background-color', 'inherit');
        jQuery('[data-float="' + insertedImage.css('float') + '"]').css('background-color', '');

        $alignments.each(function () {
            jQuery(this).off('click').on('click', function () {
                let $imgSelected = jQuery('.acym__wysid__media__inserted--selected');
                let cssToApply = acym_helper.parseJson(jQuery(this).attr('data-css'));
                let alignmentPosition = jQuery(this).attr('data-float') === 'none' ? 'center' : jQuery(this).attr('data-float');
                $imgSelected.css(cssToApply);
                $imgSelected.closest('div').find('.acym__wysid__media_caption').css(cssToApply);
                $imgSelected.closest('div').css('text-align', alignmentPosition);
                jQuery('.acym__wysid__context__image__align').css('background-color', 'inherit');
                jQuery(this).css('background-color', '');
            });
        });

        $linkInput.val($aTag.length > 0 ? $aTag.attr('href') : '');
        $urlInput.val($img.attr('src'));

        jQuery('#acym__wysid__context__image__change').off('click').on('click', function () {
            let $selectedImg = jQuery('.acym__wysid__media__inserted--selected');
            if ($selectedImg.hasClass('acym__wysid__media__giphy')) {
                acym_editorWysidNewContent.addGiphyWYSID($selectedImg.closest('.acym__wysid__column__element'));
            } else {
                acym_editorWysidImage.openMediaManager($selectedImg.closest('.acym__wysid__column__element'));
            }
        });

        $urlInput.off('keyup').on('keyup', function () {
            jQuery('.acym__wysid__media__inserted--selected').attr('src', this.value);
        });

        $altInput.val($img.attr('alt'));
        $altInput.off('keyup').on('keyup', function () {
            jQuery('.acym__wysid__media__inserted--selected').attr('alt', this.value);
        });

        $titleInput.val($img.attr('title'));
        $titleInput.off('keyup').on('keyup', function () {
            jQuery('.acym__wysid__media__inserted--selected').attr('title', this.value);
        });

        let $captionElement = $img.closest('div').find('.acym__wysid__media_caption');
        if ($captionElement.length === 1) {
            $captionInput.val($captionElement.text());
        } else {
            $captionInput.val('');
        }
        $captionInput.off('keyup').on('keyup', function () {
            let $selectedImg = jQuery('.acym__wysid__media__inserted--selected');
            let $captionElement = $selectedImg.closest('div').find('.acym__wysid__media_caption');
            if (this.value.length > 0) {
                if ($captionElement.length === 1) {
                    $captionElement.text(this.value);
                } else {
                    $selectedImg.closest('div').append(acym_editorWysidContextModal.getImageCaptionDiv(this.value));
                }
            } else if ($captionElement.length === 1) {
                $captionElement.remove();
            }
        });

        $linkInput.off('keyup').on('keyup', function () {
            let $selectedImg = jQuery('.acym__wysid__media__inserted--selected');
            let $link = $selectedImg.closest('.acym__wysid__link__image');
            if (this.value.length > 0) {
                if ($link.length === 1) {
                    $link.attr('href', this.value);
                } else {
                    $selectedImg.replaceWith('<a href="'
                                             + this.value
                                             + '" target="_blank" class="acym__wysid__link__image">'
                                             + $selectedImg.prop('outerHTML')
                                             + '</a>');
                }
            } else if ($link.length === 1) {
                $link.replaceWith($selectedImg.prop('outerHTML'));
            }
        });

        acym_editorWysidContextModal.showBlockOptions($contextImage);

        $contextImage.off('mousedown').on('mousedown', function (event) {
            event.stopPropagation();
        });

        jQuery('.acym_context_image_size_input').off('keydown').on('keydown', function (event) {
            if (event.key === ',' || event.key === '.') {
                event.preventDefault();
            }
        }).off('input').on('input', function (event) {
            jQuery('.mce-resizehandle').css('display', 'none');
            let $imgSelected = jQuery('.acym__wysid__media__inserted--selected');

            if (jQuery(event.target).is('#acym__wysid__context__image__height')) {
                $imgSelected.css('height', $heightInput.val());
            } else {
                $imgSelected.css('width', $widthInput.val());
                $heightInput.val(Math.trunc($imgSelected.height()));
            }
        });

        jQuery(window).off('mousedown').on('mousedown', function (event) {
            if (acym_editorWysidContextModal.clickedOnRightToolbar(event)) return true;
            if (jQuery(event.target).hasClass('acym__wysid__media__inserted')) return true;
            if (jQuery(event.target).closest('.media-modal').length > 0) return true;

            jQuery('.acym__wysid__media__inserted--selected').removeClass('acym__wysid__media__inserted--selected');
            jQuery(this).off('mousedown');
            let time = new Date().getTime();
            if (time < acym_helperEditorWysid.timeClickImage + 100) acym_editorWysidImage.doubleClickImage($img);
            acym_editorWysidContextModal.hideBlockOptions($contextImage, jQuery(event.target));
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidRowSelector.setZoneAndBlockOverlays();
        });

        jQuery(window).off('mouseup').on('mouseup', function (event) {
            if (!event.target.classList.contains('acym_context_image_size_input')) {
                let $imgSelected = jQuery('.acym__wysid__media__inserted--selected');
                $widthInput.val((Math.trunc($imgSelected.width())));
                $heightInput.val(Math.trunc($imgSelected.height()));
            }
        });
    },
    getImageCaptionDiv: function (valueCaption) {
        return '<div class="acym__wysid__media_caption" '
               + 'style="width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display:block;">'
               + acym_helper.escape(valueCaption)
               + '</div>';
    },
    showTextOptions: function () {
        let $contextText = jQuery('#acym__wysid__context__text');

        // The context zone is already open
        if ($contextText.is(':visible')) {
            return true;
        }

        acym_editorWysidContextModal.showBlockOptions($contextText);

        jQuery(window).off('mousedown').on('mousedown', function (event) {
            if (acym_editorWysidContextModal.clickedOnRightToolbar(event)) return true;
            if (jQuery(event.target).closest('.acym__wysid__tinymce--text').length > 0) return true;
            if (jQuery(event.target).closest('#acym__wysid__text__tinymce__editor').length > 0) return true;
            if (jQuery(event.target).closest('.mce-floatpanel').length > 0) return true;

            // We clicked outside the text / editor options / dtext options so let's hide the dtext zone
            jQuery(this).off('mousedown');
            acym_editorWysidContextModal.hideBlockOptions($contextText, jQuery(event.target));
            jQuery(window).off('click');
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
        });
    },
    setButtonOptions: function () {
        jQuery('.acym__wysid__column__element__button').off('click').on('click', function (e) {
            e.stopPropagation();
            e.preventDefault();

            jQuery('.acym__context__color__picker').remove();

            jQuery('.acym__wysid__column__element__button--focus').removeClass('acym__wysid__column__element__button--focus');
            jQuery(this).addClass('acym__wysid__column__element__button--focus');

            if (!jQuery(this).hasClass('acym__wysid__content-no-settings-style')) jQuery(this).addClass('acym__wysid__content-no-settings-style');

            let $button = jQuery(this);

            acym_editorWysidColorPicker.setColorPickerForContextModal(jQuery('#acym__wysid__context__button__background-color'),
                'background-color',
                $button,
                $button,
                'background-color',
                false,
                true
            );
            acym_editorWysidColorPicker.setColorPickerForContextModal(jQuery('#acym__wysid__context__button__border-color'),
                'border-left-color',
                $button,
                $button,
                'border-color'
            );
            acym_editorWysidColorPicker.setColorPickerForContextModal(jQuery('#acym__wysid__context__button__color'), 'color', $button, $button, 'color');

            let $inputLink = jQuery('.acym__wysid__context__button__link__container');
            let $inputText = jQuery('.acym__wysid__context__button__text__container');

            let $buttonsType = jQuery('.acym__wysid__context__button--type').addClass('button-radio-unselected').removeClass('button-radio-selected');

            if ($button.attr('href').indexOf('{confirm}') !== -1) {
                jQuery('[acym-data-type="confirm"]').removeClass('button-radio-unselected').addClass('button-radio-selected');
                $inputLink.hide();
            } else if ($button.attr('href').indexOf('{unsubscribe}') !== -1) {
                jQuery('[acym-data-type="unsubscribe"]').removeClass('button-radio-unselected').addClass('button-radio-selected');
                $inputLink.hide();
            } else {
                jQuery('[acym-data-type="call-action"]').removeClass('button-radio-unselected').addClass('button-radio-selected');
                $inputLink.show();
            }

            $buttonsType.off('click').on('click', function () {
                let $self = jQuery(this);
                if (!$self.hasClass('button-radio-unselected')) return;

                $buttonsType.addClass('button-radio-unselected').removeClass('button-radio-selected');
                $self.removeClass('button-radio-unselected').addClass('button-radio-selected');

                let linkType = $self.attr('acym-data-type');
                if (linkType.indexOf('call-action') !== -1) {
                    $inputLink.find('#acym__wysid__context__button__link, .acym__wysid__context__button__link').val('#').trigger('change');
                    $inputText.find('#acym__wysid__context__button__text').val(ACYM_JS_TXT.ACYM_BUTTON).trigger('change');
                    $inputLink.show();
                } else {
                    let dynamicLink = `{${linkType}}{/${linkType}}`;
                    $inputLink.find('#acym__wysid__context__button__link, .acym__wysid__context__button__link').val(dynamicLink).trigger('change');
                    $inputText.find('#acym__wysid__context__button__text').val(ACYM_JS_TXT[`ACYM_${linkType.toUpperCase()}`]).trigger('change');
                    $inputLink.hide();
                }
            });

            let $contextBtn = jQuery('#acym__wysid__context__button');

            jQuery('#acym__wysid__context__button__border-radius')
                .val($button.css('border-radius').replace(/[^-\d\.]/g, ''))
                .off('change paste keyup')
                .on('change paste keyup', function () {
                    $button.css('border-radius', jQuery(this).val() + 'px');
                });
            jQuery('#acym__wysid__context__button__border-width')
                .val($button.css('border-left-width').replace(/[^-\d\.]/g, ''))
                .off('change paste keyup')
                .on('change paste keyup', function () {
                    $button.css('border-width', jQuery(this).val() + 'px');
                });
            jQuery('#acym__wysid__context__button__font-family').val($button.css('font-family').replace(/['"]/g, '')).off('change').on('change', function () {
                $button.css('font-family', jQuery(this).val());
            });
            jQuery('#acym__wysid__context__button__font-size').val($button.css('font-size').replace(/[^-\d\.]/g, '')).off('change').on('change', function () {
                $button.css('font-size', jQuery(this).val() + 'px');
            });
            jQuery('#acym__wysid__context__button__text').val($button.text()).off('change paste keyup').on('change paste keyup', function () {
                $button.text(jQuery(this).val());
            });
            jQuery('#acym__wysid__context__button__link, .acym__wysid__context__button__link')
                .val($button.attr('href'))
                .off('change paste keyup')
                .on('change paste keyup', function () {
                    jQuery(this).val(jQuery(this).val().trim());
                    $button.attr('href', jQuery(this).val());
                });
            jQuery('#acym__wysid__context__button__bold')
                .css('background-color', $button.css('font-weight') == 700 ? '' : 'inherit')
                .off('click')
                .on('click', function () {
                    if ($button.css('font-weight') == 700) {
                        jQuery('#acym__wysid__context__button__bold').css('background-color', 'inherit');
                        $button.css('font-weight', 'inherit');
                    } else {
                        jQuery('#acym__wysid__context__button__bold').css('background-color', '');
                        $button.css('font-weight', 700);
                    }
                });

            jQuery('#acym__wysid__context__button__italic')
                .css('background-color', $button.css('font-style') === 'italic' ? '' : 'inherit')
                .off('click')
                .on('click', function () {
                    if ($button.css('font-style') === 'italic') {
                        jQuery('#acym__wysid__context__button__italic').css('background-color', 'inherit');
                        $button.css('font-style', 'inherit');
                    } else {
                        jQuery('#acym__wysid__context__button__italic').css('background-color', '');
                        $button.css('font-style', 'italic');
                    }
                });

            jQuery('.acym__wysid__context__button__align').each(function () {
                jQuery(this)
                    .css('background-color',
                        'acym__wysid__context__button__align__' + $button.closest('div').css('text-align') === jQuery(this).attr('id') ? '' : 'inherit'
                    );
                jQuery(this).off('click').on('click', function () {
                    $button.closest('div').css('text-align', jQuery(this).attr('data-align'));
                    jQuery('.acym__wysid__context__button__align').css('background-color', 'inherit');
                    jQuery(this).css('background-color', '');
                });
            });

            jQuery('.acym__button__padding__input').each(function () {
                jQuery(this).val($button.css(jQuery(this).attr('name')).replace(/[^-\d\.]/g, ''));
                jQuery(this).off('change').on('change', function () {
                    $button.css(jQuery(this).attr('name'), jQuery(this).val() + 'px');
                });
            });

            if (ACYM_IS_ADMIN) {
                jQuery('#acym__wysid__context__button__font-family')
                    .select2({
                        theme: 'foundation',
                        width: '40%'
                    });
                jQuery('#acym__wysid__context__button__font-size')
                    .select2({
                        theme: 'foundation',
                        width: '15%',
                        minimumResultsForSearch: Infinity
                    });
                jQuery('#acym__wysid__context__button__border-width')
                    .select2({
                        theme: 'foundation',
                        width: '15%',
                        minimumResultsForSearch: Infinity
                    });
                jQuery('#acym__wysid__context__button__border-radius')
                    .select2({
                        theme: 'foundation',
                        width: '15%',
                        minimumResultsForSearch: Infinity
                    });
            }

            $contextBtn.find('.switch-input').off('change');

            if ((document.getElementsByClassName('acym__wysid__column__element__button--focus')[0].style.width === '100%' && !$contextBtn.find('.switch-input')
                                                                                                                                         .is(':checked'))
                || (document.getElementsByClassName('acym__wysid__column__element__button--focus')[0].style.width
                    !== '100%'
                    && $contextBtn.find('.switch-input').is(':checked'))) {
                $contextBtn.find('.switch-paddle').trigger('click');
            }

            let $sliders = $contextBtn.find('.slider-handle');

            $sliders.each(function () {
                let cssRule, value, percentage;
                if ('slider__output__button__width' === jQuery(this).attr('aria-controls')) {
                    cssRule = 'left';
                    value = $button.css('padding-right').replace(/[^-\d\.]/g, '');
                    percentage = (value * 90) / 100;
                    jQuery(this).next().css('width', value + '%');
                } else {
                    cssRule = 'top';
                    value = $button.css('padding-top').replace(/[^-\d\.]/g, '');
                    percentage = (value * 77) / 100;
                    jQuery(this).next().css('height', value + '%');
                }
                jQuery(this).css(cssRule, percentage + '%');
                jQuery('#' + jQuery(this).attr('aria-controls')).val(value);
            });

            $contextBtn.find('.switch-input').off('change').on('change', function () {
                let toggle, padding;
                if (jQuery(this).is(':checked')) {
                    toggle = '100%';
                    padding = '0';
                } else {
                    toggle = 'auto';
                    padding = jQuery('#slider__output__button__width').val() + 'px';
                }
                $button.css({
                    'width': toggle,
                    'padding-right': padding,
                    'padding-left': padding
                });
            });

            jQuery('.acym__wysid__context__button__slider').off('moved.zf.slider').on('moved.zf.slider', function () {
                let value = jQuery('#' + jQuery(this).attr('data-output')).val();
                let cssRules = 'slider__output__button__width' !== jQuery(this).attr('data-output') ? {
                    'padding-top': value + 'px',
                    'padding-bottom': value + 'px'
                } : {
                    'padding-right': value + 'px',
                    'padding-left': value + 'px'
                };

                $button.css(cssRules);

            });

            acym_editorWysidContextModal.showBlockOptions($contextBtn);
            acym_helperTooltip.setTooltip();

            $contextBtn.off('mousedown').on('mousedown', function (event) {
                jQuery('.sp-container').addClass('sp-hidden').attr('style', '');
                event.stopPropagation();
            });

            jQuery(window).on('mousedown', function (event) {
                if (jQuery('.sp-container').is(':visible') || acym_editorWysidContextModal.clickedOnRightToolbar(event)) return;
                jQuery(window).off('mousedown');
                acym_editorWysidContextModal.hideBlockOptions($contextBtn, jQuery(event.target));
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
            });
        });
    },
    setSpaceOptions: function () {
        jQuery('.acy-editor__space').off('click').on('click', function (event) {
            event.stopPropagation();
            jQuery('.acym__context__color__picker').remove();

            let $space = jQuery(this);
            let $contextSpace = jQuery('#acym__wysid__context__space');
            let $slideHandler = $contextSpace.find('.slider-handle');

            acym_editorWysidContextModal.showBlockOptions($contextSpace);
            let $tdParent = $space.closest('.acym__wysid__column__element__td');

            let heightSpace = $tdParent.css('height').replace(/[^-\d\.]/g, '');
            $space.css('height', '100%');
            $tdParent.css('height', heightSpace + 'px');

            $slideHandler.css('left', heightSpace + '%');
            $contextSpace.find('.slider-fill').css('width', heightSpace + '%');
            jQuery('#sliderOutput1').val(heightSpace);


            let $sliderSpace = jQuery('#acym__wysid__context__space__slider');
            $sliderSpace.off('moved.zf.slider').on('moved.zf.slider', function () {
                $tdParent.css('height', jQuery('#sliderOutput1').val() + 'px');

            });
            $sliderSpace.off('changed.zf.slider').on('changed.zf.slider', function () {
                acym_helperEditorWysid.setColumnRefreshUiWYSID(false);
            });

            $contextSpace.off('mousedown').on('mousedown', function (event) {
                $tdParent.css('height', jQuery('#sliderOutput1').val() + 'px');
                event.stopPropagation();
            });

            jQuery(window).on('mousedown', function (event) {
                if (acym_editorWysidContextModal.clickedOnRightToolbar(event)) return true;
                $tdParent.css('height', jQuery('#sliderOutput1').val() + 'px');
                jQuery(window).off('mousedown');
                acym_editorWysidContextModal.hideBlockOptions($contextSpace, jQuery(event.target));
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
            });
        });
    },
    setFollowOptions: function () {
        jQuery('.acym__wysid__column__element__follow').off('click').on('click', function (e) {
            e.stopPropagation();

            let $selectSocialSelect = jQuery('#acym__wysid__context__follow__select');

            jQuery('.acym__wysid__column__element__follow--focus').removeClass('acym__wysid__column__element__follow--focus');
            jQuery(this).addClass('acym__wysid__column__element__follow--focus');
            jQuery('#acym__wysid__context__follow__list').empty();
            $selectSocialSelect.html('<option></option>');
            let firstSocial = jQuery('.acym__wysid__column__element__follow--focus img').eq(0);

            let contextWidth;
            if (firstSocial.length) {
                let firstWidth = firstSocial.css('width').replace(/[^-\d\.]/g, '');
                if (firstWidth <= 80) {
                    if (firstWidth >= 30) {
                        contextWidth = firstWidth;
                    } else {
                        contextWidth = 30;
                    }
                } else {
                    contextWidth = 80;
                }
            } else {
                contextWidth = 40;
            }

            jQuery('#acym__wysid__context__social__width').val(contextWidth);

            let socialNetworks = acym_helper.parseJson(ACYM_SOCIAL_MEDIA);

            for (let i = 0, l = socialNetworks.length ; i < l ; i++) {
                let found = 0;

                jQuery('.acym__wysid__column__element__follow--focus a').each(function () {
                    if (jQuery(this).attr('class').indexOf(socialNetworks[i]) !== -1) {
                        let currentSocialNetwork = socialNetworks[i];
                        found = 1;

                        let content = '<div class="grid-x small-12 cell acym__wysid__context__follow__list__item">';

                        content += '<div class="small-3 cell">';
                        content += '<img style="height: auto;  box-sizing: border-box; width: 42px; padding: 5px; margin-left: 5px" src="'
                                   + acym_helperEditorWysid.socialMedia[currentSocialNetwork].src
                                   + '" alt="">';
                        content += '</div>';

                        content += '<div class="small-7 cell">';
                        content += '<div class="input-group small-12 cell">';

                        content += '<input class="input-group-field acym__wysid__context__button__link acym__wysid__context__button__link--'
                                   + currentSocialNetwork
                                   + '" type="text" placeholder="https://" value="'
                                   + jQuery(this).attr('href')
                                   + '">';
                        content += '</div>';
                        content += '</div>';

                        content += '<div class="auto cell">';
                        content += '<p class="acym__wysid__context__follow__list__remove acym__wysid__context__follow__list__remove--'
                                   + currentSocialNetwork
                                   + '" aria-hidden="true">×</p>';
                        content += '</div>';

                        content += '</div>';
                        jQuery('#acym__wysid__context__follow__list').append(content);

                        jQuery('.acym__wysid__context__follow__list__remove--' + currentSocialNetwork).off('click').on('click', function () {
                            let $elementFollow = jQuery('.acym__wysid__column__element__follow--focus');
                            $elementFollow.find('.acym__wysid__column__element__follow__' + currentSocialNetwork).remove();
                            jQuery(this).closest('.acym__wysid__context__follow__list__item').remove();
                            acym_editorWysidContextModal.setFollowOptions();
                            $elementFollow.trigger('click');
                        });

                        jQuery('.acym__wysid__context__button__link--' + currentSocialNetwork).off('change paste keyup').on('change paste keyupe', function () {
                            jQuery('.acym__wysid__column__element__follow--focus')
                                .find('.acym__wysid__column__element__follow__' + currentSocialNetwork)
                                .attr('href', jQuery(this).val());
                        });
                    }
                });

                if (found == 0) {
                    $selectSocialSelect.append(new Option(ACYM_IS_ADMIN ? '' : socialNetworks[i], socialNetworks[i], false, false)).trigger('change');
                }
            }

            let $follow = jQuery(this);
            let width = $follow.innerWidth();
            let $contextFollow = jQuery('#acym__wysid__context__follow');

            jQuery('.acym__wysid__context__follow__align').each(function () {
                jQuery(this)
                    .css('background-color', 'acym__wysid__context__follow__align__' + $follow.css('text-align') === jQuery(this).attr('id') ? '' : 'inherit');
                jQuery(this).off('click').on('click', function () {
                    let alignement = jQuery(this).attr('data-align');
                    $follow.css('text-align', alignement);
                    if (alignement === 'center') {
                        $follow.find('img').removeAttr('align').removeAttr('hspace');
                    } else {
                        $follow.find('img').attr('align', alignement).attr('hspace', '3');
                    }
                    jQuery('.acym__wysid__context__follow__align').css('background-color', 'inherit');
                    jQuery(this).css('background-color', '');
                });
            });

            acym_editorWysidContextModal.showBlockOptions($contextFollow);

            if (ACYM_IS_ADMIN) {
                $selectSocialSelect.select2({
                    theme: 'foundation',
                    minimumResultsForSearch: -1,
                    placeholder: '+',
                    selectOnClose: false,
                    closeOnSelect: true,
                    width: '50px',
                    templateResult: acym_editorWysidContextModal.getFollowDataFormatWYSID,
                    templateSelection: acym_editorWysidContextModal.getFollowDataFormatWYSID
                });

                $selectSocialSelect.off('select2:select').on('select2:select', function (e) {
                    let selectedNetwork = e.params.data.id;
                    acym_editorWysidContextModal.addContentFollowContext(jQuery('.acym__wysid__column__element__follow--focus'), selectedNetwork);
                });
            } else {
                $selectSocialSelect.off('change').on('change', function () {
                    acym_editorWysidContextModal.addContentFollowContext(jQuery('.acym__wysid__column__element__follow--focus'), jQuery(this).val());
                });
            }

            $contextFollow.off('mousedown').on('mousedown', function (event) {
                event.stopPropagation();
            });

            jQuery(window).on('mousedown', function (event) {
                if (acym_editorWysidContextModal.clickedOnRightToolbar(event)) return true;
                jQuery(window).off('mousedown');
                acym_editorWysidContextModal.hideBlockOptions($contextFollow, jQuery(event.target));
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
            });

            jQuery('#acym__wysid__context__social__width__slider').off('moved.zf.slider').on('moved.zf.slider', function () {
                let followIconsWidth = jQuery('#acym__wysid__context__social__width').val();
                if (followIconsWidth > 80) followIconsWidth = 80;
                if (followIconsWidth < 30) followIconsWidth = 30;

                jQuery('.acym__wysid__column__element__follow--focus img').css('width', followIconsWidth).attr('width', followIconsWidth);
            });
        });

        jQuery('.acym__wysid__column__element__follow a').off('click').on('click', function (event) {
            event.preventDefault();
        });
    },
    setBuiltWithOptions: function () {
        jQuery('#acym__powered_by_acymailing').off('click').on('click', function (e) {
            e.stopPropagation();
            e.preventDefault();

            let $contextPoweredBy = jQuery('#acym__wysid__context__poweredby');
            acym_editorWysidContextModal.showBlockOptions($contextPoweredBy);

            jQuery(window).off('mousedown').on('mousedown', function (event) {
                if (acym_editorWysidContextModal.clickedOnRightToolbar(event)) return true;
                if (event.target.title !== 'poweredby' && jQuery(event.target).children().children('[title="poweredby"]').length === 0) {

                    jQuery(this).off('mousedown');
                    acym_editorWysidContextModal.hideBlockOptions($contextPoweredBy, jQuery(event.target));
                    jQuery('.acym__wysid__context__modal__container--structure').hide();
                    acym_editorWysidVersioning.setUndoAndAutoSave();
                }
            });
        });
    },
    getFollowDataFormatWYSID: function (data) {
        if (!data.id) {
            return data.text;
        }

        return jQuery('<span><img src="' + acym_helperEditorWysid.socialMedia[data.element.value.toLowerCase()].src + '"/>' + data.text + '</span>');
    },
    addContentFollowContext: function ($followContainer, selectedNetwork) {
        if ('' === selectedNetwork) return;

        let $contextSocial = jQuery('#acym__wysid__context__social__width');
        let width = $contextSocial.val() <= 80 ? $contextSocial.val() >= 30 ? $contextSocial.val() : 30 : 80;
        let content = '<a class="acym__wysid__column__element__follow__' + selectedNetwork + '" href="">';
        content += '<img style="display: inline-block; max-width: 100%; height: auto; box-sizing: border-box; width: '
                   + width
                   + 'px; padding: 3px;" src="'
                   + acym_helperEditorWysid.socialMedia[selectedNetwork].src
                   + '" width="'
                   + width
                   + '" alt="'
                   + selectedNetwork
                   + '">';
        content += '</a>';
        $followContainer.append(content);
        acym_editorWysidContextModal.setFollowOptions();
        $followContainer.trigger('click');
    },
    setSeparatorOptions: function () {
        jQuery('.acym__wysid__column__element__separator').off('click').on('click', function (e) {
            e.stopPropagation();
            jQuery('.acym__context__color__picker').remove();
            //We get the hr to change
            let $hr = jQuery(this).find('hr:first');
            jQuery('#sliderOutput2').val($hr.css('border-bottom-width').replace(/[^-\d\.]/g, ''));
            let leftSlider2 = $hr.css('border-bottom-width').replace(/[^-\d\.]/g, '') + '%';
            jQuery('[aria-controls="sliderOutput2"]').css('left', leftSlider2).next().css('width', leftSlider2);

            let leftSlider3 = Math.round($hr.width() * 100 / $hr.parent().width());
            jQuery('#sliderOutput3').val(leftSlider3);
            leftSlider3 += '%';
            jQuery('[aria-controls="sliderOutput3"]').css('left', leftSlider3).next().css('width', leftSlider3);

            let leftSlider4 = $hr.css('margin-top').replace(/[^-\d\.]/g, '');
            leftSlider4 = ((parseInt(leftSlider4) * 100) / 50) + '%';
            jQuery('#sliderOutput4').val($hr.css('margin-top').replace(/[^-\d\.]/g, ''));
            jQuery('[aria-controls="sliderOutput4"]').css('left', leftSlider4).next().css('width', leftSlider4);

            const $colorInput = jQuery('#acym__wysid__context__separator__color');
            //We set the color picker of the separator
            acym_editorWysidColorPicker.setColorPickerForContextModal($colorInput, 'border-bottom-color', $hr, $hr, 'border-bottom-color');

            $colorInput.off('change').on('change', function () {
                $hr.css('color', this.value);
            });

            let $allType = jQuery('.acym__wysid__context__separator__kind');
            $allType.removeClass('separator-selected');
            $allType.each(function () {
                if (jQuery(this).find('hr').attr('data-kind') == $hr.css('border-bottom-style')) {
                    jQuery(this).addClass('separator-selected');
                }
            });

            $allType.off('click').on('click', function () {
                $allType.removeClass('separator-selected');
                jQuery(this).addClass('separator-selected');
                $hr.css('border-bottom-style', jQuery(this).find('hr').attr('data-kind'));
            });

            let $slider = jQuery('#acym__wysid__context__separator__slide');
            let $sliderWidth = jQuery('#acym__wysid__context__separator__slide__width');
            let $sliderSpace = jQuery('#acym__wysid__context__separator__slide__space');

            $slider.off('moved.zf.slider').on('moved.zf.slider', function () {
                if ($slider.css('display') !== 'none') {
                    $hr.css('border-bottom-width', jQuery('#sliderOutput2').val() + 'px');
                    $hr.css('size', jQuery('#sliderOutput2').val() + 'px');
                }
            });

            $sliderWidth.off('moved.zf.slider').on('moved.zf.slider', function () {
                if ($slider.css('display') !== 'none') {
                    $hr.css('width', (jQuery('#sliderOutput3').val()) + '%');
                }
            });

            $sliderSpace.off('moved.zf.slider').on('moved.zf.slider', function () {
                if ($slider.css('display') !== 'none') {
                    let $spaceVal = jQuery('#sliderOutput4').val();
                    $hr.css('margin-top', $spaceVal + 'px');
                    $hr.css('margin-bottom', $spaceVal + 'px');
                }
            });

            let $contextSeparator = jQuery('#acym__wysid__context__separator');

            acym_editorWysidContextModal.showBlockOptions($contextSeparator);

            $contextSeparator.off('mousedown').on('mousedown', function (event) {
                jQuery('.sp-container').addClass('sp-hidden').attr('style', '');
                event.stopPropagation();
            });

            jQuery(window).on('mousedown', function (event) {
                if (jQuery('.sp-container').is(':visible') || acym_editorWysidContextModal.clickedOnRightToolbar(event)) return;
                jQuery(window).off('mousedown');
                acym_editorWysidContextModal.hideBlockOptions($contextSeparator, jQuery(event.target));
                $colorInput.off('change');
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
            });
        });
    }
};
