jQuery(document).on('acym_plugins_installed_loaded', function () {
    let appVue = [];
    let $modals = jQuery('[acym-data-plugins-id]');
    $modals.on('open.zf.reveal', function () {
        let $modal = jQuery(this);
        if (appVue[$modal.attr('acym-data-plugins-id')] !== undefined) return;
        let pluginFolderName = $modal.attr('acym-data-plugin-folder');
        let pluginClassName = $modal.attr('acym-data-plugin-class');
        appVue[$modal.attr('acym-data-plugins-id')] = new Vue({
            el: '#' + $modal.attr('acym-data-plugins-id'),
            components: {
                'vue-prism-editor': VuePrismEditor
            },
            data: () => {
                return {
                    language: 'html',
                    code: '',
                    saving: false,
                    saved: false,
                    messageSaved: '',
                    deleting: false,
                    deleted: false,
                    messageDeleted: '',
                    tags: [],
                    optionIndent: {
                        indent_size: 2,
                        wrap_line_length: '120'
                    },
                    loading: true
                };
            },
            mounted() {
                let ajaxUrl = ACYM_AJAX_URL + '&ctrl=plugins&task=getCustomViewPlugin&plugin=' + pluginFolderName + '&plugin_class=' + pluginClassName;
                this.setTags();
                jQuery.get(ajaxUrl, (response) => {
                    response = acym_helper.parseJson(response);
                    if (undefined !== response.error) {
                        acym_helperNotification.addNotification(response.error, 'error');
                        $modal.foundation('close');
                        return false;
                    }
                    response.content = undefined === html_beautify ? response.content : html_beautify(response.content, this.optionIndent);
                    this.code = response.content;
                    this.loading = false;
                });
            },
            methods: {
                save() {
                    this.saving = true;
                    let ajaxUrl = ACYM_AJAX_URL + '&ctrl=plugins&task=saveCustomViewPlugin&plugin=' + pluginFolderName;
                    jQuery.post(ajaxUrl, {custom_view: encodeURIComponent(this.code)}, (response) => {
                        response = acym_helper.parseJson(response);
                        if (undefined !== response.error) {
                            acym_helperNotification.addNotification(response.error, 'error');
                            $modal.foundation('close');
                            return false;
                        }
                        this.saving = false;
                        this.messageSaved = response.message;
                        this.saved = true;
                        setTimeout(() => {
                            this.saved = false;
                        }, 2000);
                    });
                },
                resetView() {
                    if (!confirm(ACYM_JS_TXT.ACYM_RESET_VIEW_CONFIRM)) return;
                    this.deleting = true;
                    let ajaxUrl = ACYM_AJAX_URL + '&ctrl=plugins&task=deleteCustomViewPlugin&plugin=' + pluginFolderName + '&plugin_class=' + pluginClassName;
                    jQuery.get(ajaxUrl, (response) => {
                        response = acym_helper.parseJson(response);
                        if (undefined !== response.error) {
                            acym_helperNotification.addNotification(response.error, 'error');
                            $modal.foundation('close');
                            return false;
                        }
                        response.content = undefined === html_beautify ? response.content : html_beautify(response.content, this.optionIndent);
                        this.code = response.content;
                        this.deleting = false;
                        this.messageDeleted = response.message;
                        this.deleted = true;
                        setTimeout(() => {
                            this.deleted = false;
                        }, 2000);
                    });
                },
                insertTag(tag) {
                    tag = `{${tag}}`;
                    let editor = this.$children[0];
                    if (editor.selection === undefined || editor.selection.start === undefined) return false;
                    //We insert the code were the selection is
                    this.code = `${this.code.slice(0, editor.selection.start)}${tag}${this.code.slice(editor.selection.end)}`;
                    //We update the selection
                    if (editor.selection.end !== editor.selection.start) editor.selection.end = editor.selection.start + tag.length;
                    if (editor.selection.end === editor.selection.start) {
                        editor.selection.start += tag.length;
                        editor.selection.end += tag.length;
                    }
                },
                setTags() {
                    this.tags = acym_helper.parseJson($modal.find('[acym-data-tags]').attr('acym-data-tags'));
                    for (let [tag, params] of Object.entries(this.tags)) {
                        this.tags[tag] = ACYM_JS_TXT[params[0]] === undefined ? params[0] : ACYM_JS_TXT[params[0]];
                    }
                }
            }
        });
    });
    $modals.on('closed.zf.reveal', function (){
        jQuery(document).trigger('acym_custom_view_modal_closed');
    })
});
