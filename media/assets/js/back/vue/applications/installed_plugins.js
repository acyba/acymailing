jQuery(function ($) {
    Vue.component('vue-switch', {
        props: [
            'plugin',
            'ischecked'
        ],
        data: function () {
            return {
                id: 'vue-switch_',
                loading: false,
                disabled: false
            };
        },
        mounted: function () {
            this.id += this.plugin.id;
        },
        methods: {
            toggleActive(id) {
                if (this.loading) return true;
                this.loading = true;
                this.disabled = true;
                let ajaxUrl = ACYM_AJAX_URL + '&ctrl=plugins&task=toggleActivate&id=' + id;
                $.get(ajaxUrl, (res) => {
                    res = acym_helper.parseJson(res);
                    if (res.error) {
                        acym_helperNotification.addNotification(res.message, 'error');
                        return false;
                    }
                    this.loading = false;
                    this.disabled = false;
                });
            }
        },
        template: '<div class="cell shrink grid-x"><div class="switch cell shrink"> '
                  + '<input class="switch-input" :id="id" type="checkbox" name="exampleSwitch" :checked="ischecked"> '
                  + '<label class="switch-paddle" @click="toggleActive(plugin.id)" :for="id"> '
                  + '</label> '
                  + '</div>'
                  + '<i v-show="loading" class="cell shrink acym__card__loader acymicon-circle-o-notch acymicon-spin"></i></div>'
    });

    if ($('#acym__plugin__installed__application').length > 0) {
        const remove = (array, pluginId) => array.filter((plugin) => plugin.id !== pluginId);

        const appVue = new Vue({
            directives: {infiniteScroll},
            el: '#acym__plugin__installed__application',
            data: {
                allPlugins: [],
                displayedPlugins: [1],
                search: acym_helper.getCookie('acym_installed_plugins_search'),
                type: acym_helper.getCookie('acym_installed_plugins_type'),
                level: acym_helper.getCookie('acym_installed_plugins_level'),
                loading: true,
                noPluginTodisplay: false,
                typingTimer: '',
                doneTypingInterval: 1000,
                currentLevel: '',
                downloading: {},
                pluginToggling: {},
                updating: {},
                pageDisplay: 1,
                busy: false,
                showSettings: {}
            },
            mounted: function () {
                acym_helper.config_get('level').done((resConfig) => {
                    if (resConfig.error) {
                        acym_helperNotification.addNotification(resConfig.message, 'error');

                        return false;
                    }
                    this.allPlugins = acym_helper.parseJson(document.getElementById('acym__plugins__all').value);
                    this.currentLevel = resConfig.data.value.toLowerCase();
                    this.resetDisplay();
                    if (this.displayedPlugins.length === 0) {
                        this.noPluginTodisplay = true;
                    }
                    this.fillPluginToggling();
                    this.fillUpdating();
                    acym_helper.setSubmitButtonGlobal();
                    this.loading = false;
                    setTimeout(() => {
                        this.$forceUpdate();
                        this.afterRender();
                    }, 100);
                });
            },
            methods: {
                afterRender() {
                    $(document).foundation();
                    $('.reveal-overlay').appendTo('#acym_form');
                    $(document).trigger('acym_plugins_installed_loaded');
                    acym_helperTooltip.setTooltip();
                    acym_helper.setSubmitButtonGlobal();
                    acym_helperRadio.setRadioIconsGlobal();
                    acym_helperSwitch.setSwitchFieldsGlobal();
                    acym_helperDatePicker.setRSDateChoice();
                    acym_helperDatePicker.setDatePickerGlobal();
                    acym_helperSelect2.setSelect2();
                    acym_helperInput.setMulticouple();
                },
                toggleSettings(folderName) {
                    let $cardToFlip = $('#acym__plugins__card__' + folderName);
                    let $openCard = $('.acym__plugins__card__flip');
                    if (!$cardToFlip.hasClass('acym__plugins__card__flip') && $openCard.length > 0) {
                        this.flipCard($openCard);
                    }
                    this.flipCard($cardToFlip);
                    $('[name="plugin__folder_name"]').val($cardToFlip.hasClass('acym__plugins__card__flip') ? folderName : '');
                },
                flipCard($element) {
                    $element.toggleClass('acym__plugins__card__flip');
                    setTimeout(() => {
                        $element.toggleClass('acym__plugins__card__flipped');
                        acym_helperTooltip.setTooltip();
                    }, 110);
                },
                updatePlugin(plugin) {
                    if (this.updating[plugin.id]) return true;
                    this.updating[plugin.id] = true;
                    this.updating = {...this.updating};
                    let ajaxUrl = ACYM_AJAX_URL + '&ctrl=plugins&task=update';
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {'plugin': plugin}
                    }).then((res) => {
                        res = acym_helper.parseJson(res);
                        if (res.error) {
                            acym_helperNotification.addNotification(res.message, 'error');
                            this.updating[plugin.id] = false;
                            this.updating = {...this.updating};
                            return false;
                        }
                        acym_helperNotification.addNotification(res.message, 'info');
                        this.updating[plugin.id] = false;
                        this.updating = {...this.updating};
                        this.toggleUptodateCurrentApp(plugin.id);
                    });
                },
                removeOnePluginInApp(id) {
                    this.allPlugins = remove(this.allPlugins, id);
                    this.displayedPlugins = remove(this.displayedPlugins, id);
                },
                resetDisplay() {
                    this.pageDisplay = 1;
                    this.fillDisplayPlugins();
                },
                fillDisplayPlugins() {
                    this.displayedPlugins = this.filterPlugin().slice(0, 18 * this.pageDisplay);
                    this.displayedPlugins = [...this.displayedPlugins];
                },
                rightLevel(pluginLevel) {
                    if (pluginLevel === 'starter') return true;
                    if (pluginLevel === 'essential' && [
                        'essential',
                        'enterprise'
                    ].indexOf(this.currentLevel) !== -1) {
                        return true;
                    }
                    if (pluginLevel === 'enterprise' && this.currentLevel === 'enterprise') return true;
                    return false;
                },
                filterPlugin() {
                    return this.allPlugins.filter((plugin) => {
                        return (plugin.level.toLowerCase().indexOf(this.level.toLowerCase()) !== -1)
                               && (plugin.category.toLowerCase()
                                         .indexOf(this.type.toLowerCase()) !== -1)
                               && (plugin.folder_name.toLowerCase().indexOf(this.search.toLowerCase()) !== -1);
                    });
                },
                toggleUptodateCurrentApp(pluginId) {
                    this.allPlugins.map((plugin) => {
                        if (plugin.id === pluginId) plugin.uptodate = plugin.uptodate == '1' ? '0' : '1';
                    });
                    this.displayedPlugins = [...this.displayedPlugins];
                },
                deletePlugin(id) {
                    if (acym_helper.confirm(ACYM_JS_TXT.ACYM_ARE_YOU_SURE_DELETE_ADD_ON)) {
                        let ajaxUrl = ACYM_AJAX_URL + '&ctrl=plugins&task=deletePlugin&id=' + id;
                        $.get(ajaxUrl, (res) => {
                            res = acym_helper.parseJson(res);
                            if (res.error) {
                                acym_helperNotification.addNotification(res.message, 'error');
                                return false;
                            }
                            acym_helperNotification.addNotification(res.message, 'info');
                            this.removeOnePluginInApp(id);
                            return true;
                        });
                    }
                },
                fillPluginToggling() {
                    for (let plugin in this.allPlugins) {
                        this.pluginToggling[this.allPlugins[plugin].id] = false;
                    }
                },
                fillUpdating() {
                    for (let plugin in this.allPlugins) {
                        this.updating[this.allPlugins[plugin].id] = false;
                    }
                },
                isActivated(active) {
                    return active == 1 ? 'checked' : '';
                },
                loadMorePlugins() {
                    this.pageDisplay++;
                    this.fillDisplayPlugins();
                },
                imageUrl(pluginName, type) {
                    if (type === 'PLUGIN') {
                        return ACYM_MEDIA_URL + 'images/plugins/' + pluginName + '.png';
                    } else {
                        return ACYM_CORE_DYNAMICS_URL + pluginName + '/banner.png';
                    }
                },
                documentationUrl(pluginName, type) {
                    if ([
                            'article',
                            'page',
                            'post'
                        ].indexOf(pluginName) !== -1) {
                        return 'https://docs.acymailing.com/addons/all-cms-add-ons/wordpress-posts-pages-and-joomla-articles';
                    }

                    if (pluginName === 'createuser') {
                        return 'https://docs.acymailing.com/addons/all-cms-add-ons/create-user';
                    }

                    if (type === 'PLUGIN') {
                        const pluginDefinitions = acym_helper.parseJson(ACYM_AVAILABLE_PLUGINS);
                        const matchingPlugin = pluginDefinitions.find(plugin => plugin.file_name === pluginName);
                        if (matchingPlugin) {
                            return matchingPlugin.documentation;
                        }
                    } else {
                        return ACYM_UPDATEME_API_URL + 'public/addons/documentation?file_name=' + pluginName;
                    }
                },
                isOverflown(index) {
                    if (this.$refs.plugins === undefined || this.$refs.plugins[index] === undefined) return '';
                    return this.$refs.plugins[index].scrollHeight > this.$refs.plugins[index].clientHeight ? 'acym__plugins__card__params_desc__overflown' : '';
                }
            },
            watch: {
                displayedPlugins(newVal, oldVal) {
                    this.noPluginTodisplay = this.displayedPlugins.length === 0 && oldVal[0] !== 1;
                },
                search(newValue) {
                    clearTimeout(this.typingTimer);
                    document.cookie = 'acym_installed_plugins_search=' + newValue + ';';
                    if ('' === newValue) {
                        this.resetDisplay();
                    } else {
                        this.typingTimer = setTimeout(() => {
                            this.resetDisplay();
                        }, this.doneTypingInterval);
                    }
                },
                type() {
                    document.cookie = 'acym_installed_plugins_type=' + this.type + ';';
                    this.resetDisplay();
                },
                level() {
                    document.cookie = 'acym_installed_plugins_level=' + this.level + ';';
                    this.resetDisplay();
                }
            }
        });
    }
});
