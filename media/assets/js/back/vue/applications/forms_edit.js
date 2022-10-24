jQuery(function ($) {
    let timeout;
    let appVue = new Vue({
        'el': '#acym__forms',
        data: () => {
            return {
                form: acym_helper.parseJson($('#acym__form__structure').val()),
                menuActive: 'settings',
                loading: true,
                noReloading: [
                    'name',
                    'pages',
                    'active',
                    'delay',
                    'redirection_options.after_subscription',
                    'redirection_options.confirmation_message'
                ],
                oldForm: {}
            };
        },
        mounted() {
            this.oldForm = JSON.parse(JSON.stringify(this.form));
            $('#acym__forms__preview__content').on('load', () => {
                this.getFormRender();
                this.disableClickIframe();
            });
            acym_helperSwitch.setSwitchFieldsGlobal();
            acym_helperTooltip.setTooltip();
        },
        computed: {
            isMenuSettingsActive() {
                return this.menuActive === 'settings';
            },
            imageText() {
                return ACYM_JS_TXT[this.form.image_options.url === '' ? 'ACYM_SELECT' : 'ACYM_CHANGE'];
            }
        },
        methods: {
            changeMenuActive(status) {
                this.menuActive = status;
            },
            selectPosition(position) {
                this.form.style_options.position = position;
            },
            save(exit) {
                if (this.form.name === '') {
                    alert(ACYM_JS_TXT.ACYM_PLEASE_FILL_FORM_NAME);
                    return;
                }
                this.loading = true;
                this.form.active = $('[name="form[active]"]').val();
                let ajaxUrl = ACYM_AJAX_URL + '&ctrl=forms&task=saveAjax';
                return $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    dataType: 'json',
                    data: {
                        form: this.form
                    }
                }).then((res) => {
                    this.loading = false;
                    if (res.error) {
                        acym_helperNotification.addNotification(res.message, 'error');
                        return false;
                    }
                    if (exit) {
                        window.location.href = $('#acym_form').attr('action') + '&task=listing';
                    } else {
                        acym_helperNotification.addNotification(res.message, 'info', true);
                        this.form.id = res.data.id;
                        $('#acym__form__structure').val(res.data.id);
                    }
                });
            },
            disableClickIframe() {
                let $iframe = $('#acym__forms__preview__content');
                let html = `<style>
                                * {pointer-events: none !important;}
                            </style>`;
                $iframe.contents().find('#acym__forms__edit_live__preview').remove();
                $iframe.contents().find('body').prepend(html);
            },
            fillIframe(html) {
                let $iframe = $('#acym__forms__preview__content');
                html = `<div id="acym__forms__edit_live__preview">${html}</div>`;
                $iframe.contents().find('#acym__forms__edit_live__preview').remove();
                $iframe.contents().find('body').prepend(html);
            },
            getFormRender() {
                this.loading = true;
                let ajaxUrl = ACYM_AJAX_URL + '&ctrl=forms&task=updateFormPreview';
                return $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    dataType: 'json',
                    data: {
                        form: this.form
                    }
                }).then((res) => {
                    this.loading = false;
                    if (res.error) {
                        acym_helperNotification.addNotification(res.message, 'error');
                        return false;
                    }
                    this.fillIframe(res.data.html);
                });
            },
            cleanValues() {
                if (undefined !== this.form.display_options) {
                    if (this.form.display_options.scroll > 100) {
                        this.form.display_options.scroll = 100;
                    }
                    if (this.form.display_options.scroll < 0) {
                        this.form.display_options.scroll = 0;
                    }
                }
            }
        },
        watch: {
            form: {
                handler() {
                    $('.acym__forms__menu__container').off('scroll');
                    clearTimeout(timeout);
                    this.cleanValues();
                    let stopReloading = false;
                    for (let i = 0 ; i < this.noReloading.length ; i++) {
                        if (this.noReloading[i].indexOf('.') === -1) {
                            if (this.noReloading[i] === 'pages') {
                                if (!acym_helper.sameArrays(this.form[this.noReloading[i]], this.oldForm[this.noReloading[i]])) {
                                    stopReloading = true;
                                }
                            } else if (this.form[this.noReloading[i]] !== this.oldForm[this.noReloading[i]]) {
                                stopReloading = true;
                            }
                        } else {
                            let noReloadingSplit = this.noReloading[i].split('.');
                            if (this.form[noReloadingSplit[0]][noReloadingSplit[1]] !== this.oldForm[noReloadingSplit[0]][noReloadingSplit[1]]) {
                                stopReloading = true;
                            }
                        }
                    }
                    this.oldForm = JSON.parse(JSON.stringify(this.form));

                    if (stopReloading) return;

                    this.loading = true;
                    timeout = setTimeout(() => {
                        this.getFormRender();
                    }, 1000);
                },
                deep: true
            }
        }
    });
});
