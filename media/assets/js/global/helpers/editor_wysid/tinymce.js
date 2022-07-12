const acym_editorWysidTinymce = {
    addTinyMceWYSID: function () {
        tinymce.remove();
        tinymce.baseURL = ACYM_MEDIA_URL + 'js/tinymce';

        tinymce.init({
            convert_urls: false,
            relative_urls: false,
            selector: '.acym__wysid__tinymce--text',
            inline: true,
            menubar: false,
            plugins: 'textcolor colorpicker lists link code noneditable lineheight table',
            image_class_list: [
                {
                    title: 'Responsive',
                    value: 'img-responsive'
                }
            ],
            fixed_toolbar_container: '#acym__wysid__text__tinymce__editor',
            fontsize_formats: '10px=10px 12px=12px 14px=14px 16px=16px 18px=18px 20px=20px 22px=22px 24px=24px 26px=26px 28px=28px 30px=30px 32px=32px 34px=34px 36px=36px',
            lineheight_formats: '100% 110% 120% 130% 140% 150% 160% 170% 180% 190% 200% 210% 220% 230% 240%',
            toolbar: [
                'undo redo formatselect fontselect fontsizeselect',
                'alignmentsplit | listsplit outdent indent lineheightselect | table',
                'bold italic underline strikethrough removeformat | forecolor backcolor | link unlink | code'
            ],
            link_class_list: [
                {
                    title: 'None',
                    value: ''
                },
                {
                    title: ACYM_JS_TXT.ACYM_DONT_APPLY_STYLE_TAG_A,
                    value: 'acym__wysid__content-no-settings-style'
                }
            ],
            formats: {
                removeformat: [
                    {
                        selector: 'b,strong,em,i,font,u,strike,pre,code',
                        remove: 'all',
                        split: true,
                        expand: false,
                        block_expand: true,
                        deep: true
                    },
                    {
                        selector: 'span',
                        attributes: [
                            'style',
                            'class'
                        ],
                        remove: 'empty',
                        split: true,
                        expand: false,
                        deep: true
                    },
                    {
                        selector: '*',
                        attributes: [
                            'style',
                            'class'
                        ],
                        split: false,
                        expand: false,
                        deep: true
                    }
                ]
            },
            preview_styles: false,
            block_formats: 'Paragraph=p;Heading 1=h1;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6',
            init_instance_callback: function (editor) {
                acym_editorWysidDynamic.setDTextActions();
                editor.on('keydown', function (e) {
                    let currentText = jQuery(editor.getElement()).find('>:first-child');
                    if (acym_editorWysidTinymce.isCurrentTextEmpty(currentText)
                        && e.key
                        === 'Backspace'
                        && jQuery(editor.getElement()).children().length
                        === 1) {
                        e.preventDefault();
                        return true;
                    }
                });
                editor.on('keyup', function () {
                    let currentText = jQuery(editor.getElement()).find('>:first-child');
                    if (acym_editorWysidTinymce.isCurrentTextEmpty(currentText)) {
                        currentText.addClass('acym__wysid__tinymce--text--placeholder acym__wysid__tinymce--text--placeholder--empty');
                    } else {
                        currentText.removeClass('acym__wysid__tinymce--text--placeholder--empty');
                    }
                    acym_editorWysidTinymce.checkForEmptyText();
                    jQuery(editor.getElement()).trigger('click');
                });
                editor.on('click', function () {
                    acym_editorWysidRowSelector.hideOverlays();
                    acym_editorWysidContextModal.showTextOptions();
                });
                editor.off('change').on('change', function (e) {
                    if (e.lineheight !== undefined) {
                        let $element = jQuery(editor.getElement()).find('.acym__wysid__tinymce--text--placeholder, .acym__wysid__tinymce--title--placeholder');
                        $element.css('line-height', e.lineheight);
                    }
                    acym_editorWysidFontStyle.applyCssOnAllElementTypesBasedOnSettings();
                });
                editor.on('blur', function (e) {
                    let initialContent = e.target.startContent;
                    let finalContent = e.target.bodyElement.innerHTML;
                    if (initialContent !== finalContent) acym_editorWysidVersioning.setUndoAndAutoSave();
                    acym_editorWysidRowSelector.showOverlays();
                    acym_editorWysidRowSelector.resizeZoneOverlays();
                    acym_editorWysidTinymce.checkForEmptyText();
                });
                editor.on('ExecCommand', function (e) {
                    let currentText = jQuery(editor.getElement()).find('>:first-child');
                    if (e.command === 'mceTableDelete' && acym_editorWysidTinymce.isCurrentTextEmpty(currentText)) {
                        e.target.bodyElement.innerHTML = '<p class="acym__wysid__tinymce--text--placeholder">&zwj;</p>';
                        jQuery(':focus').blur();
                    }
                });
                editor.on('BeforeSetContent', function (e) {
                    if (e.content.indexOf('<table id="__mce"') === 0) {
                        let currentText = jQuery(editor.getElement()).find('>:first-child');
                        if (acym_editorWysidTinymce.isCurrentTextEmpty(currentText)) {
                            currentText.remove();
                        }
                        let sUsrAg = navigator.userAgent;
                        if (sUsrAg.indexOf('Firefox') > -1) {
                            acym_editorWysidTinymce.cleanForFirefox(jQuery(editor.getElement()), 0);
                        }
                    }
                });
                editor.addButton('listsplit', {
                    type: 'splitbutton',
                    text: '',
                    icon: 'bullist',
                    onclick: function (e) {
                        tinyMCE.execCommand(this.value);
                    },
                    menu: [
                        {
                            icon: 'bullist',
                            text: 'Bullet list',
                            onclick: function () {
                                tinyMCE.execCommand('InsertUnorderedList');
                                this.parent().parent().icon('bullist');
                                this.parent().parent().value = 'InsertUnorderedList';
                            }
                        },
                        {
                            icon: 'numlist',
                            text: 'Ordered List',
                            onclick: function () {
                                tinyMCE.execCommand('InsertOrderedList');
                                this.parent().parent().icon('numlist');
                                this.parent().parent().value = 'InsertOrderedList';
                            }
                        }
                    ],
                    onPostRender: function () {
                        // Select the first item by default
                        this.value = 'InsertUnorderedList';
                    }
                });
                editor.addButton('alignmentsplit', {
                    type: 'splitbutton',
                    text: '',
                    icon: 'alignleft',
                    onclick: function (e) {
                        tinyMCE.execCommand(this.value);
                    },
                    menu: [
                        {
                            icon: 'alignleft',
                            text: 'Align Left',
                            onclick: function () {
                                tinyMCE.execCommand('JustifyLeft');
                                this.parent().parent().icon('alignleft');
                                this.parent().parent().value = 'JustifyLeft';
                            }
                        },
                        {
                            icon: 'alignright',
                            text: 'Align Right',
                            onclick: function () {
                                tinyMCE.execCommand('JustifyRight');
                                this.parent().parent().icon('alignright');
                                this.parent().parent().value = 'JustifyRight';
                            }
                        },
                        {
                            icon: 'aligncenter',
                            text: 'Align Center',
                            onclick: function () {
                                tinyMCE.execCommand('JustifyCenter');
                                this.parent().parent().icon('aligncenter');
                                this.parent().parent().value = 'JustifyCenter';
                            }
                        },
                        {
                            icon: 'alignjustify',
                            text: 'Justify',
                            onclick: function () {
                                tinyMCE.execCommand('JustifyFull');
                                this.parent().parent().icon('alignjustify');
                                this.parent().parent().value = 'JustifyFull';
                            }
                        }
                    ],
                    onPostRender: function () {
                        // Select the first item by default
                        this.value = 'JustifyLeft';
                    }
                });
            }
        });
        tinymce.execCommand('mceAddEditor', true, '');

        tinymce.init({
            selector: '.acym__wysid__tinymce--image',
            inline: true,
            menubar: false,
            plugins: 'image nonbreaking',
            toolbar: [],
            relative_urls: true,
            remove_script_host: false,
            image_class_list: [
                {
                    title: 'Responsive',
                    value: 'img-responsive'
                }
            ],
            preview_styles: false,
            init_instance_callback: function (editor) {
                acym_editorWysidImage.setDoubleClickImage();
                editor.on('click', function (e) {
                    let $img = jQuery(editor.getElement()).find('img');
                    acym_helperEditorWysid.timeClickImage = new Date().getTime();
                    acym_editorWysidRowSelector.hideOverlays();
                    acym_editorWysidContextModal.showImageOptions($img);
                });
                editor.on('blur', function () {
                    acym_editorWysidRowSelector.showOverlays();
                    acym_helperEditorWysid.removeBlankCharacters();
                });
            }
        });
        tinymce.execCommand('mceAddEditor', true, '');
    },
    isCurrentTextEmpty: function (currentText) {
        return (currentText.is(':empty')
                || currentText.html()
                === '&nbsp;<br>'
                || currentText.html()
                === '<br>'
                || escape(currentText.html())
                == '%u200D'
                || currentText.html()
                == '<br data-mce-bogus="1">');
    },
    checkForEmptyText: function () {
        jQuery('.acym__wysid__tinymce--text--placeholder, .acym__wysid__tinymce--title--placeholder').each(function () {
            let severalTags = jQuery(this)
                                  .closest('.acym__wysid__tinymce--text')
                                  .find('.acym__wysid__tinymce--text--placeholder, .acym__wysid__tinymce--title--placeholder').length > 1;
            if (acym_editorWysidTinymce.isCurrentTextEmpty(jQuery(this)) && !severalTags) {
                jQuery(this).addClass('acym__wysid__tinymce--text--placeholder acym__wysid__tinymce--text--placeholder--empty');
            } else {
                jQuery(this).removeClass('acym__wysid__tinymce--text--placeholder--empty');
            }
        });
    },
    cleanForFirefox: function (currentArea, timerTotal) {
        let tinymceP = currentArea.find('>p');
        if (tinymceP.length === 0 && timerTotal < 1000) {
            setTimeout(() => {
                acym_editorWysidTinymce.cleanForFirefox(currentArea, timerTotal + 50);
            }, 50);
        } else if (tinymceP.length === 1 && acym_editorWysidTinymce.isCurrentTextEmpty(tinymceP)) {
            tinymceP.remove();
        }
    }
};
