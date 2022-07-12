const acym_helperPreview = {
    setPreviewIframe: function () {
        //On saving the wysid editor the preview is reloaded.
        jQuery('.acym__wysid__hidden__save__content').off('change').on('change', function () {
            acym_helperPreview.setPreviewIframe();
        });

        acym_helperPreview.addIframePreview('acym__wysid__email__preview', 'desktop');

        //Add preview buttons for the WYSID editor
        jQuery('#acym__wysid__view__desktop').off('click').on('click', function () {
            tinymce.remove();
            acym_helperPreview.setTemplateForPreview(true);
        });
        jQuery('#acym__wysid__view__smartphone').off('click').on('click', function () {
            tinymce.remove();
            acym_helperPreview.setTemplateForPreview(false);
        });
    },
    addIframePreview: function (where, device, onEditor) {
        onEditor == undefined ? onEditor = false : onEditor = true;

        let collapse = true;
        let heightIframe;
        let $divIframe = jQuery('#acym__wysid__email__preview');

        let idIframe = 'acym__wysid__preview__iframe__' + where;

        //Add the iframe
        let newContent = '<div class="cell auto"></div>';
        newContent += '<iframe id="' + idIframe + '" src="about:blank" frameborder="0" class="cell shrink" style="width: 100%"></iframe>';
        newContent += '<div class="cell auto"></div>';
        newContent += '<div class="acym__fadeout"></div>';
        newContent += '<i class="acymicon-keyboard_arrow_down acym__preview__extend acym__preview__toggle acym__preview-toggle"></i>';
        newContent += '<i class="acymicon-sort acym__preview-toggle acym__preview__toggle__top"></i>';

        jQuery('#' + where).html(newContent);

        let $iframe = jQuery('#' + idIframe);

        //Sets the resolution according to the selected device
        device == 'smartphone' ? $iframe.css('maxWidth', '425px') : $iframe.css('maxWidth', 'inherit');
        $iframe.css('height', '100%');

        heightIframe = $iframe.css('height');
        let heightForce = '200px';

        if (jQuery('#acym__campaign__summary').length) {
            $iframe.css({'height': heightForce});
            $divIframe.css('padding-bottom', heightForce);
            $iframe.contents().find('body').css('overflow', 'hidden');
            jQuery('.acym__preview-toggle').off('click').on('click', function () {
                jQuery('.acym__fadeout').toggle();
                if (collapse) {
                    jQuery('.acym__preview__toggle')
                        .removeClass('acymicon-keyboard_arrow_down')
                        .removeClass('acym__preview__extend')
                        .addClass('acymicon-keyboard_arrow_up')
                        .addClass('acym__preview__collapse');
                    $iframe.css({'height': heightIframe}).contents().find('body').css('overflow', 'auto');
                    $divIframe.css('padding-bottom', heightIframe);
                    collapse = !collapse;
                } else {
                    jQuery('.acym__preview__toggle')
                        .removeClass('acymicon-keyboard_arrow_up')
                        .removeClass('acym__preview__collapse')
                        .addClass('acymicon-keyboard_arrow_down')
                        .addClass('acym__preview__extend');
                    $iframe.css({'height': heightForce}).contents().find('body').css('overflow', 'hidden');
                    $divIframe.css('padding-bottom', heightForce);
                    collapse = !collapse;
                }
            });
        } else {
            jQuery('.acym__fadeout').hide();
            jQuery('.acym__preview-toggle').hide();
        }

        //If chrome we load the iframe synchronously, yeah it's a fix for chrome
        if (/chrom(e|ium)/.test(navigator.userAgent.toLowerCase()) || /^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {
            acym_helperPreview.loadIframe(idIframe, onEditor);
        } else {
            $iframe.on('load', function () {
                acym_helperPreview.loadIframe(idIframe, onEditor);
            });
        }
    },
    setTemplateForPreview: function (large) {
        let $template = jQuery('#acym__wysid__template');
        let device = large ? 'desktop' : 'smartphone';
        $template.find('.acym__wysid__row__selector').remove();
        jQuery('#acym__template__preview').val($template.html());
        jQuery('.acym__wysid__fullscreen__modal__content__container').hide();
        jQuery('#acym__wysid__fullscreen__modal__content__' + device).closest('.acym__wysid__fullscreen__modal__content__container').show();
        acym_helperPreview.addIframePreview('acym__wysid__fullscreen__modal__content__' + device, device, true);
        jQuery('#acym__wysid__fullscreen__modal')
            .css('display', 'flex')
            .on('click', function () {
                acym_helperEditorWysid.setColumnRefreshUiWYSID(false);
                acym_editorWysidTinymce.addTinyMceWYSID();
                acym_editorWysidRowSelector.setZoneAndBlockOverlays();

                jQuery(this).css('display', 'none');
            });
    },
    loadIframe: function (idIframe, onEditor) {
        let defaultContentWYSID = '<div id="acym__wysid__template" class="cell acym__foundation__for__email">';
        defaultContentWYSID += '<table class="body"><tbody><tr>';
        defaultContentWYSID += '<td align="center" class="center acym__wysid__template__content" valign="top" style="background-color: rgb(239, 239, 239);"><center>';
        defaultContentWYSID += '<table align="center" border="0" cellpadding="0" cellspacing="0"><tbody>';
        defaultContentWYSID += '<tr><td class="acym__wysid__row ui-droppable ui-sortable">';
        defaultContentWYSID += '<table class="row acym__wysid__row__element" border="0" cellpadding="0" cellspacing="0"><tbody style="background-color: transparent;">';
        defaultContentWYSID += '<tr><th class="small-12 medium-12 large-12 columns">';
        defaultContentWYSID += '<table class="acym__wysid__column" style="min-height: 75px; display: block;" border="0" cellpadding="0" cellspacing="0"><tbody class="ui-sortable" style="min-height: 75px; display: block;"></tbody></table>';
        defaultContentWYSID += '</th></tr>';
        defaultContentWYSID += '</tbody></table>';
        defaultContentWYSID += '</td></tr>';
        defaultContentWYSID += '</tbody></table>';
        defaultContentWYSID += '</center></td>';
        defaultContentWYSID += '</tr></tbody></table>';
        defaultContentWYSID += '</div>';

        let $iframeContents = jQuery('#' + idIframe).contents();
        let $iframeHead = $iframeContents.find('head');
        let $iframeBody = $iframeContents.find('body');

        let $savedStylesheet = jQuery('.acym__wysid__hidden__save__stylesheet').attr('value');

        // Content of the wysid edition page when we click the "Apply" button
        let $savedContent = jQuery('.acym__wysid__hidden__save__content');

        // Content of the wysid edition page on load
        let $mailContent = jQuery('.acym__hidden__mail__content').val();
        let $editorContent = jQuery('#acym__wysid__template');
        let defaultContent = $editorContent.find('#acym__wysid__default').detach();
        $editorContent.find('.acym__wysid__row__selector, .acym__wysid__element__toolbox').remove();
        let $templateHtml = $editorContent.html();
        $editorContent.find('.acym__wysid__column__first').before(defaultContent);

        $iframeHead.append('<meta name="viewport" content="width=device-width, initial-scale=1.0" />');


        // Apply the CSS
        $iframeHead.append('<link rel="stylesheet" href="' + FOUNDATION_FOR_EMAIL + '">');
        $iframeHead.append('<style>' + ACYM_FIXES_FOR_EMAIL + '</style>');
        $iframeHead.append('<style type="text/css">#acym__wysid__template center > table { width: 100%; }</style>');

        if ($savedStylesheet !== undefined && $savedStylesheet !== '') {
            setTimeout(function () {
                $iframeHead.append('<style>' + $savedStylesheet + '</style>');
            }, 100);
        }

        // Add current template content
        let $previewContent = '';
        if (onEditor) {
            $savedContent.attr('value', '<div id="acym__wysid__template" class="cell">' + $templateHtml + '</div>');
            $previewContent = '<div id="acym__wysid__template" class="cell">' + jQuery('#acym__template__preview').val() + '</div>';
        } else if ($savedContent.attr('value') === '') {
            if ($templateHtml === '') {
                $savedContent.attr('value', defaultContentWYSID);
            } else {
                $savedContent.attr('value', '<div id="acym__wysid__template" class="cell">' + $templateHtml + '</div>');
            }
        }

        if ($savedContent.attr('value') === 'empty') {
            $savedContent.attr('value', defaultContentWYSID);
        }

        // Set content and user stylesheet for summary preview
        if ($mailContent !== '') {
            let $mailStylesheet = jQuery('.acym__hidden__mail__stylesheet').html();
            if ($mailStylesheet !== undefined && $mailStylesheet !== '') {
                $iframeHead.append('<style>' + $mailStylesheet + '</style>');
            }
            $iframeBody.html('');
            $iframeBody.append($mailContent);
        }

        if ($previewContent !== '') {
            $iframeBody.css('margin', '0').append($previewContent);
        } else {
            $iframeBody.css('margin', '0').append($savedContent.attr('value'));
        }

        // Add blank target to 'a' tags except anchors
        $iframeContents.find('a').attr('target', '_blank');
        $iframeContents.find('a[href^="#"]').attr('target', '_top');

        jQuery(document).trigger('acy_preview_loaded');
    }
};
