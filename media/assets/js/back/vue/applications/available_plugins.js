jQuery(document).ready(function ($) {
    if ($('#acym__plugin__available__application').length > 0) {

        const remove = (array, pluginId) => array.filter((plugin) => plugin.id !== pluginId);

        const appVue = new Vue({
            directives: {infiniteScroll},
            el: '#acym__plugin__available__application',
            data: {
                allPlugins: [],
                displayedPlugins: [1],
                search: '',
                type: '',
                level: '',
                loading: true,
                typesColors: {
                    'Files management': 'background-color: #C5EAFF;',
                    'E-commerce solutions': 'background-color: #E7CEFF;',
                    'Content management': 'background-color: #FFC1E9;',
                    'Subscription system': 'background-color: #FFC7C7;',
                    'Others': 'background-color: #FFF1BC;',
                    'User management': 'background-color: #CAFFBA;',
                    'Events management': 'background-color: #C2FFF9;',
                },
                noPluginTodisplay: false,
                typingTimer: '',
                doneTypingInterval: 1000,
                currentLevel: '',
                downloading: {},
                allPluginsInstalled: {},
                pageDisplay: 1,
                busy: false,
                installed: {},

            },
            mounted: function () {
                this.getAllPlugins().then((res) => {
                    res = acym_helper.parseJson(res);
                    if (undefined !== res.error) {
                        acym_helperNotification.addNotification(res.error, 'error');

                        return false;
                    }
                    acym_helperBack.config_get('level').done((resConfig) => {
                        if (undefined !== resConfig.error) {
                            acym_helperNotification.addNotification(resConfig.error, 'error');

                            return false;
                        }
                        this.getAllPluginsInstalled().then((resPluginsSite) => {
                            this.allPluginsInstalled = resPluginsSite.elements;
                            this.currentLevel = resConfig.data.toLowerCase();
                            this.allPlugins = res;
                            res = res.map((plugin) => {
                                plugin.description = plugin.description.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br />');
                            });
                            this.setStatus();
                            this.resetDisplay();
                            $('[name="acym__plugins__level"], [name="acym__plugins__type"]').select2({
                                theme: 'foundation',
                                width: '100%',
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
            },
            methods: {
                getAllPlugins() {
                    let ajaxUrl = AJAX_URL_UPDATEME + 'integrationv6&task=getAllPlugin&cms=' + CMS_ACYM;
                    return $.post(ajaxUrl);
                },
                getAllPluginsInstalled() {
                    let ajaxUrl = ACYM_AJAX_URL + '&ctrl=plugins&task=getAllPluginsAjax';
                    return $.ajax({
                        type: 'POST',
                        url: ajaxUrl,
                        dataType: 'json',
                    });
                },
                download(plugin) {
                    if (this.downloading[plugin.id]) return true;
                    this.downloading[plugin.id] = true;
                    this.downloading = {...this.downloading};
                    let ajaxUrl = ACYM_AJAX_URL + '&ctrl=plugins&task=download';
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {'plugin': plugin},
                    }).then((res) => {
                        res = acym_helper.parseJson(res);
                        if (undefined !== res.error) {
                            acym_helperNotification.addNotification(res.error, 'error');
                            this.downloading[plugin.id] = false;
                            this.downloading = {...this.downloading};
                            return false;
                        }
                        acym_helperNotification.addNotification(res.message, 'info');
                        this.downloading[plugin.id] = false;
                        this.installed[plugin.id] = true;
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
                    if (pluginLevel === 'starter') return true;
                    if (pluginLevel === 'essential' && [
                        'essential',
                        'enterprise',
                    ].indexOf(this.currentLevel) !== -1) {
                        return true;
                    }
                    if (pluginLevel === 'enterprise' && this.currentLevel === 'enterprise') return true;
                    return false;
                },
                ucfirst(edition) {
                    if (undefined === edition) return '';
                    return edition.charAt(0).toUpperCase() + edition.slice(1);
                },
                setStatus() {
                    for (let plugin in this.allPlugins) {
                        this.downloading[this.allPlugins[plugin].id] = false;
                        this.installed[this.allPlugins[plugin].id] = this.isInstalled(this.allPlugins[plugin].name);
                    }
                },
                isInstalled(pluginName) {
                    return this.allPluginsInstalled.find(plugin => plugin.title == pluginName) !== undefined;
                },
                filter() {
                    return this.allPlugins.filter((plugin) => (plugin.level.toLowerCase().indexOf(this.level.toLowerCase()) !== -1) && (plugin.category.toLowerCase().indexOf(this.type.toLowerCase()) !== -1) && (plugin.name.toLowerCase().indexOf(this.search.toLowerCase()) !== -1));
                },
                handlePluginsInstalled(pluginsFromServer) {
                    if (undefined === this.allPluginsInstalled) {
                        return pluginsFromServer;
                    } else {
                        return pluginsFromServer.filter((plugin) => this.allPluginsInstalled.map((pluginsInstalled) => plugin.file_name.replace('.zip', '') === pluginsInstalled.folder_name).indexOf(true) === -1);
                    }
                },
                loadMorePlugins() {
                    this.pageDisplay++;
                    this.fillDisplayPlugins();
                },
                imageUrl(pluginName) {
                    if (undefined !== pluginName) {
                        pluginName = pluginName.substring(0, pluginName.indexOf('.'));
                    }
                    return AJAX_URL_UPDATEME + 'integrationv6&task=getImage&plugin=' + pluginName;
                },
                documentationUrl(pluginName) {
                    if (undefined === pluginName) return '';
                    pluginName = pluginName.replace('.zip', '');
                    return AJAX_URL_UPDATEME + 'integrationv6&task=getDocumentation&plugin=' + pluginName;
                },
                isOverflown(index) {
                    if (this.$refs.plugins === undefined || this.$refs.plugins[index] === undefined) return '';
                    return this.$refs.plugins[index].scrollHeight > this.$refs.plugins[index].clientHeight ? 'acym__plugins__card__params_desc__overflown' : '';
                },
            },
            watch: {
                displayedPlugins(newVal, oldVal) {
                    this.noPluginTodisplay = this.displayedPlugins.length === 0 && oldVal[0] !== 1;
                },
                search(newValue) {
                    clearTimeout(this.typingTimer);
                    if ('' === newValue) {
                        this.resetDisplay();
                    } else {
                        this.typingTimer = setTimeout(() => {
                            this.resetDisplay();
                        }, this.doneTypingInterval);
                    }
                },
                type() {
                    this.resetDisplay();
                },
                level() {
                    this.resetDisplay();
                },
            },
        });
    }
});
