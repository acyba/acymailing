jQuery(function ($) {
    if ($('#acym__plugin__available__application').length > 0) {

        const remove = (array, pluginId) => array.filter((plugin) => plugin.id !== pluginId);

        const appVue = new Vue({
            directives: {infiniteScroll},
            el: '#acym__plugin__available__application',
            data: {
                allPlugins: [],
                displayedPlugins: [],
                search: acym_helper.getCookie('acym_available_plugins_search'),
                type: acym_helper.getCookie('acym_available_plugins_type'),
                level: acym_helper.getCookie('acym_available_plugins_level'),
                loading: true,
                noPluginTodisplay: false,
                typingTimer: '',
                doneTypingInterval: 1000,
                currentLevel: '',
                downloading: {},
                allPluginsInstalled: {},
                pageDisplay: 1,
                busy: false,
                installed: {}

            },
            mounted: function () {
                this.getAllPlugins().then((res) => {
                    res = acym_helper.parseJson(res);
                    if (undefined !== res.error) {
                        acym_helperNotification.addNotification(res.error, 'error');

                        return false;
                    }
                    acym_helper.config_get('level').done((resConfig) => {
                        if (resConfig.error) {
                            acym_helperNotification.addNotification(resConfig.message, 'error');

                            return false;
                        }
                        this.getAllPluginsInstalled().then((response) => {
                            this.allPluginsInstalled = response.data;
                            this.currentLevel = resConfig.data.value.toLowerCase();
                            this.allPlugins = res;
                            res = res.map((plugin) => {
                                plugin.description = plugin.description.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br />');
                            });
                            this.setStatus();
                            this.resetDisplay();
                            $('[name="acym__plugins__level"], [name="acym__plugins__type"]').select2({
                                theme: 'foundation',
                                width: '100%'
                            });
                            if (this.displayedPlugins.length === 0) {
                                this.noPluginTodisplay = true;
                            }
                            this.loading = false;
                            setTimeout(() => {
                                this.$forceUpdate();
                                acym_helperTooltip.setTooltip();
                            }, 400);
                        });
                    });
                });
                this.$el.addEventListener('touchend', this.loadMorePlugins);
            },
            methods: {
                getAllPlugins() {
                    if (ACYM_CMS === 'joomla') {
                        return $.get(ACYM_UPDATEME_API_URL + 'public/addons');
                    } else if (ACYM_CMS === 'wordpress') {
                        return Promise.resolve($('#acym__plugin__available__plugins').val());
                    }
                },
                getAllPluginsInstalled() {
                    const data = {
                        ctrl: 'plugins',
                        task: 'getAllPluginsAjax'
                    };

                    return acym_helper.post(ACYM_AJAX_URL, data);
                },
                download(plugin) {
                    if (this.downloading[plugin.file_name]) {
                        return true;
                    }

                    this.downloading[plugin.file_name] = true;
                    this.downloading = {...this.downloading};

                    const data = {
                        ctrl: 'plugins',
                        task: 'download',
                        plugin: plugin
                    };
                    acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                        if (response.error) {
                            acym_helperNotification.addNotification(response.message, 'error');
                            this.downloading[plugin.file_name] = false;
                            this.downloading = {...this.downloading};
                            return false;
                        }
                        acym_helperNotification.addNotification(response.message, 'info');
                        this.downloading[plugin.file_name] = false;
                        this.installed[plugin.file_name] = true;
                    });
                },
                resetDisplay() {
                    this.pageDisplay = 1;
                    this.fillDisplayPlugins();
                },
                fillDisplayPlugins() {
                    this.displayedPlugins = this.filter().slice(0, 18 * this.pageDisplay);
                    this.displayedPlugins = [...this.displayedPlugins];
                },
                rightLevel(pluginLevel) {
                    if (pluginLevel === 'starter') {
                        return true;
                    }

                    if (pluginLevel === 'essential' && [
                        'essential',
                        'enterprise'
                    ].indexOf(this.currentLevel) !== -1) {
                        return true;
                    }

                    return pluginLevel === 'enterprise' && this.currentLevel === 'enterprise';
                },
                ucfirst(edition) {
                    return undefined === edition ? '' : edition.charAt(0).toUpperCase() + edition.slice(1);
                },
                setStatus() {
                    for (let plugin in this.allPlugins) {
                        this.downloading[this.allPlugins[plugin].file_name] = false;
                        this.installed[this.allPlugins[plugin].file_name] = this.isInstalled(this.allPlugins[plugin]);
                    }
                },
                isInstalled(pluginDefinition) {
                    if (this.allPluginsInstalled.find(plugin => plugin.title === pluginDefinition.name) !== undefined) {
                        return true;
                    }

                    return this.allPluginsInstalled.find(plugin => plugin.folder_name === pluginDefinition.file_name) !== undefined;
                },
                filter() {
                    return this.allPlugins.filter((plugin) => (plugin.level.toLowerCase().indexOf(this.level.toLowerCase()) !== -1)
                                                              && (plugin.category.toLowerCase().indexOf(this.type.toLowerCase()) !== -1)
                                                              && (plugin.name.toLowerCase().indexOf(this.search.toLowerCase()) !== -1));
                },
                loadMorePlugins() {
                    this.pageDisplay++;
                    this.fillDisplayPlugins();
                },
                imageUrl(pluginName) {
                    if (undefined === pluginName) return '';

                    if (ACYM_CMS === 'joomla') {
                        return ACYM_UPDATEME_API_URL + 'public/addons/banner?file_name=' + pluginName;
                    } else if (ACYM_CMS === 'wordpress') {
                        return ACYM_MEDIA_URL + 'images/plugins/' + pluginName + '.png';
                    }
                },
                isOverflown(index) {
                    if (this.$refs.plugins === undefined || this.$refs.plugins[index] === undefined) {
                        return '';
                    }

                    return this.$refs.plugins[index].scrollHeight > this.$refs.plugins[index].clientHeight ? 'acym__plugins__card__params_desc__overflown' : '';
                }
            },
            watch: {
                displayedPlugins(newVal, oldVal) {
                    this.noPluginTodisplay = this.displayedPlugins.length === 0 && oldVal[0] !== 1;
                },
                search(newValue) {
                    clearTimeout(this.typingTimer);
                    document.cookie = 'acym_available_plugins_search=' + newValue + ';';
                    if ('' === newValue) {
                        this.resetDisplay();
                    } else {
                        this.typingTimer = setTimeout(() => {
                            this.resetDisplay();
                        }, this.doneTypingInterval);
                    }
                },
                type() {
                    document.cookie = 'acym_available_plugins_type=' + this.type + ';';
                    this.resetDisplay();
                },
                level() {
                    document.cookie = 'acym_available_plugins_level=' + this.level + ';';
                    this.resetDisplay();
                }
            }
        });
    }
});
