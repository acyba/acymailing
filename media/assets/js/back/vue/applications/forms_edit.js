jQuery(document).ready(function ($) {
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
                    'delay'
                ],
                oldForm: {}
            };
        },
        mounted() {
            this.oldForm = Object.assign({}, this.form);
            $('#acym__forms__preview__content').on('load', () => {
                this.getFormRender();
                this.disableClickIframe();
            });
            acym_helperSwitch.setSwitchFieldsGlobal();
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
                    if (res.error !== undefined) {
                        acym_helperNotification.addNotification(res.error, 'error');
                        return false;
                    }
                    if (exit) {
                        window.location.href = $('#acym_form').attr('action') + '&task=listing';
                    } else {
                        acym_helperNotification.addNotification(res.message, 'info', true);
                        this.form.id = res.id;
                        $('#acym__form__structure').val(res.id);
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
                    if (res.error !== undefined) {
                        acym_helperNotification.addNotification(res.error, 'error');
                        return false;
                    }
                    this.fillIframe(res.html);
                });
            }
        },
        watch: {
            form: {
                handler() {
                    clearTimeout(timeout);
                    for (let i = 0 ; i < this.noReloading.length ; i++) {
                        if (this.form[this.noReloading[i]] !== this.oldForm[this.noReloading[i]]) {
                            this.oldForm = Object.assign({}, this.form);
                            return;
                        }
                    }
                    this.loading = true;
                    this.oldForm = Object.assign({}, this.form);
                    timeout = setTimeout(() => {
                        this.getFormRender();
                    }, 1000);
                },
                deep: true
            }
        }
    });
});
