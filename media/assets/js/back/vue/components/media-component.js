jQuery(function ($) {

    Vue.component('acym-media', {
        name: 'acym-media',
        template: '<div class="cell shrink grid-x acym_vcenter"><div v-html="joomlaIframe"></div><button type="button" class="cell shrink button-secondary button margin-bottom-0 margin-right-1" @click="openMedia">{{ text }}</button><i @click="removeImage" class="cell shrink cursor-pointer acymicon-delete acym__color__red" v-if="value !== \'\'"></i></div>',
        props: {
            'value': String,
            'text': String
        },
        mounted: function () {
            this.initJoomlaModal();
        },
        data: () => {
            if (ACYM_CMS === 'wordpress') return {joomlaIframe: ''};
            const modalId = `acym__form__modal__joomla-image__container-${Math.floor(Math.random() * 1000)}`;
            const iframeId = `acym__form__modal__joomla-image__ui__iframe-${Math.floor(Math.random() * 1000)}`;

            let iframe = `<div id="${modalId}">`;
            iframe += '<div id="acym__form__modal__joomla-image__bg" class="acym__form__modal__joomla-image--close"></div>';
            iframe += '<div id="acym__form__modal__joomla-image__ui" class="float-center cell">';

            let url = 'index.php?option=com_media&amp;asset=com_content&amp;author=acymailing&amp;tmpl=component';
            if (!ACYM_J40) {
                url += '&amp;view=images';
            }

            iframe += `<iframe id="${iframeId}" src="${url}" frameborder="0"></iframe>`;

            if (ACYM_J40) {
                iframe += '<div class="cell grid-x align-right grid-margin-x">';
                iframe += '<button type="button" class="button button-secondary cell shrink margin-bottom-0 acym__form__modal__joomla-image__ui__iframe__actions__select">';
                iframe += ACYM_JS_TXT.ACYM_SELECT;
                iframe += '</button>';
                iframe += '<button type="button" class="button button-secondary cell shrink margin-bottom-0 acym__form__modal__joomla-image__ui__iframe__actions__cancel">';
                iframe += ACYM_JS_TXT.ACYM_CANCEL;
                iframe += '</button>';
                iframe += '</div>';
            }

            iframe += '</div></div>';

            return {
                joomlaIframe: iframe,
                modalId,
                iframeId
            };
        },
        methods: {
            openMedia() {
                if (ACYM_CMS === 'wordpress') {
                    this.openMediaWordpress();
                } else {
                    this.openMediaJoomla();
                }
            },
            openMediaWordpress() {
                let file_frame;
                file_frame = wp.media.frames.file_frame = wp.media({
                    title: ACYM_JS_TXT.ACYM_SELECT_IMAGE_TO_UPLOAD,
                    button: {text: ACYM_JS_TXT.ACYM_USE_THIS_IMAGE},
                    multiple: false
                });
                file_frame.on('select', () => {
                    let attachment = file_frame.state().get('selection').first().toJSON();
                    this.$emit('change', attachment.url);
                });
                file_frame.open();
            },
            openMediaJoomla() {
                jQuery(`#${this.modalId}`).css('display', 'inherit');
            },
            initJoomlaModal() {
                let vueComp = this;
                let $modalUi = jQuery(`#${this.iframeId}`);
                let iframeHeight = '100%';
                if (ACYM_J40) {
                    iframeHeight = 'calc(100% - 50px)';
                }
                $modalUi.css('height', iframeHeight).css('width', '100%');
                $modalUi.contents().find('.chzn-container-single').attr('style', '').css('width', '150px');
                $modalUi.on('load', function () {
                    jQuery('.acym__form__modal__joomla-image--close').on('click', function () {
                        jQuery(`#${vueComp.modalId}`).hide();
                    });
                    $modalUi.contents().find('.button-cancel').attr('onclick', '').off('click').on('click', function () {
                        jQuery(`#${vueComp.modalId}`).hide();
                    });
                    $modalUi.contents().find('.pull-right .btn-success, .pull-right .btn-primary').attr('onclick', '').off('click').on('click', function () {
                        let inputUrlImg = $modalUi.contents().find('#f_url').val();
                        if (inputUrlImg.match('^' + ACYM_JOOMLA_MEDIA_FOLDER)) inputUrlImg = ACYM_JOOMLA_MEDIA_IMAGE + inputUrlImg;
                        vueComp.$emit('change', inputUrlImg);
                        jQuery(`#${vueComp.modalId}`).hide();
                    });
                });

                // Joomla 4 select/cancel buttons
                jQuery('.acym__form__modal__joomla-image__ui__iframe__actions__cancel').off('click').on('click', function () {
                    jQuery(`#${vueComp.modalId}`).hide();
                });

                jQuery('.acym__form__modal__joomla-image__ui__iframe__actions__select').on('click', function () {
                    // 1 - Get current folder
                    let folderPath = ACYM_ROOT_URI;
                    $modalUi.contents().find('.media-breadcrumb-item a').each(function () {
                        folderPath += jQuery(this).text().trim() + '/';
                    });

                    // 2 - Get selected image(s)
                    let imagesUrls = [];

                    // When selecting a images from the grid view
                    $modalUi.contents()
                            .find('.media-browser-grid .media-browser-item.selected .media-browser-image .media-browser-item-info')
                            .each(function () {
                                imagesUrls.push(folderPath + jQuery(this).text().trim());
                            });

                    // When selecting images from the list view instead of grid view
                    if (imagesUrls.length === 0) {
                        $modalUi.contents().find('.media-browser .media-browser-item.selected').each(function () {
                            if (!acym_helper.empty(jQuery(this).find('.size').text().trim())) {
                                imagesUrls.push(folderPath + jQuery(this).find('.name').text().trim());
                            }
                        });
                    }

                    // 3 - Take the first image selected
                    let inputUrlImg = '';

                    if (imagesUrls.length !== 0) {
                        inputUrlImg = imagesUrls[0];
                    }

                    if (inputUrlImg.match('^' + ACYM_JOOMLA_MEDIA_FOLDER)) inputUrlImg = ACYM_JOOMLA_MEDIA_IMAGE + inputUrlImg;
                    vueComp.$emit('change', inputUrlImg);
                    jQuery(`#${vueComp.modalId}`).hide();
                });
            },
            removeImage() {
                this.$emit('change', '');
            }
        }
    });
});
