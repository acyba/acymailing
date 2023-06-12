jQuery(function ($) {
    let timeout;
    let appVue = new Vue({
        'el': '#acym__forms',
        data: () => {
            return {
                form: acym_helper.parseJson($('#acym__form__structure').val()),
                menuActive: 'settings',
                loading: true,
                noReloadingArrayFields: [
                    'display_languages',
                    'pages'
                ],
                noReloading: [
                    'display_languages',
                    'pages',
                    'name',
                    'active',
                    'settings.display.display_action',
                    'settings.display.delay',
                    'settings.display.scroll',
                    'settings.cookie.cookie_expiration',
                    'settings.redirection.after_subscription',
                    'settings.redirection.confirmation_message'
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
                return ACYM_JS_TXT[this.form.settings.image.url === '' ? 'ACYM_SELECT' : 'ACYM_CHANGE'];
            }
        },
        methods: {
            changeMenuActive(status) {
                this.menuActive = status;
            },
            selectPosition(position) {
                this.form.settings.style.position = position;
            },
            save(exit) {
                if (this.form.name === '') {
                    alert(ACYM_JS_TXT.ACYM_PLEASE_FILL_FORM_NAME);
                    return;
                }
                this.loading = true;
                this.form.active = $('[name="form[active]"]').val();

                return $.ajax({
                    type: 'POST',
                    url: ACYM_AJAX_URL + '&ctrl=forms&task=saveAjax',
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
                return $.ajax({
                    type: 'POST',
                    url: ACYM_AJAX_URL + '&ctrl=forms&task=updateFormPreview',
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
                if (undefined !== this.form.settings.display) {
                    if (this.form.settings.display.scroll > 100) {
                        this.form.settings.display.scroll = 100;
                    }
                    if (this.form.settings.display.scroll < 0) {
                        this.form.settings.display.scroll = 0;
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
                    // Search which option has just been changed, and if it is one of the noReloading ones, don't refresh the form's preview
                    for (let i = 0 ; i < this.noReloading.length ; i++) {
                        if (this.noReloading[i].indexOf('.') === -1) {
                            if (this.noReloadingArrayFields.includes(this.noReloading[i])) {
                                // Special check for the pages option since it's a multi select
                                if (!acym_helper.sameArrays(this.form[this.noReloading[i]], this.oldForm[this.noReloading[i]])) {
                                    stopReloading = true;
                                }
                            } else if (this.form[this.noReloading[i]] !== this.oldForm[this.noReloading[i]]) {
                                stopReloading = true;
                            }
                        } else {
                            let noReloadingSplit = this.noReloading[i].split('.');
                            if (this.form[noReloadingSplit[0]][noReloadingSplit[1]][noReloadingSplit[2]]
                                !== this.oldForm[noReloadingSplit[0]][noReloadingSplit[1]][noReloadingSplit[2]]) {
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
