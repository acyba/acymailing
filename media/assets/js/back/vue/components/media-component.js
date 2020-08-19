jQuery(document).ready(function ($) {

    Vue.component('acym-media', {
        name: 'acym-media',
        template: '<div class="cell shrink grid-x acym_vcenter"><div v-html="joomlaIframe"></div><button class="cell shrink button-secondary button margin-bottom-0 margin-right-1" @click="openMedia">{{ text }}</button><i @click="removeImage" class="cell shrink cursor-pointer acymicon-trash-o acym__color__red" v-if="value !== \'\'"></i></div>',
        props: {
            'value': String,
            'text': String
        },
        mounted: function () {
            this.initJoomlaModal();
        },
        data: () => {
            return {
                joomlaIframe: CMS_ACYM === 'wordpress'
                              ? ''
                              : '<div id="acym__form__modal__joomla-image"><div id="acym__form__modal__joomla-image__bg" class="acym__form__modal__joomla-image--close"></div><div id="acym__form__modal__joomla-image__ui" class="float-center cell"><iframe id="acym__form__modal__joomla-image__ui__iframe" src="index.php?option=com_media&amp;view=images&amp;tmpl=component&amp;e_name=imageurl&amp;asset=com_content&amp;author=\'acymailing\'" frameborder="0"></iframe></div></div>'
            };
        },
        methods: {
            openMedia() {
                if (CMS_ACYM === 'wordpress') {
                    this.openMediaWordpress();
                } else {
                    this.openMediaJoomla();
                }
            },
            openMediaWordpress() {
                let file_frame;
                file_frame = wp.media.frames.file_frame = wp.media({
                    title: 'Select a image to upload',
                    button: {text: 'Use this image'},
                    multiple: false
                });
                file_frame.on('select', () => {
                    let attachment = file_frame.state().get('selection').first().toJSON();
                    this.$emit('change', attachment.url);
                });
                file_frame.open();
            },
            openMediaJoomla() {
                jQuery('#acym__form__modal__joomla-image').css('display', 'inherit');
            },
            initJoomlaModal() {
                let vueComp = this;
                let $modalUi = jQuery('#acym__form__modal__joomla-image__ui__iframe');
                $modalUi.css('height', '100%').css('width', '100%');
                $modalUi.contents().find('.chzn-container-single').attr('style', '').css('width', '150px');
                $modalUi.on('load', function () {
                    jQuery('.acym__form__modal__joomla-image--close').on('click', function () {
                        jQuery('#acym__form__modal__joomla-image').hide();
                    });
                    $modalUi.contents().find('.button-cancel').attr('onclick', '').off('click').on('click', function () {
                        jQuery('#acym__form__modal__joomla-image').hide();
                    });
                    $modalUi.contents().find('.pull-right .btn-success, .pull-right .btn-primary').attr('onclick', '').off('click').on('click', function () {
                        let inputUrlImg = $modalUi.contents().find('#f_url').val();
                        inputUrlImg = inputUrlImg.match('^images/') ? ACYM_JOOMLA_MEDIA_IMAGE + inputUrlImg : inputUrlImg;
                        vueComp.$emit('change', inputUrlImg);
                        jQuery('#acym__form__modal__joomla-image').hide();
                    });
                });
            },
            removeImage() {
                this.$emit('change', '');
            }
        }
    });
});
