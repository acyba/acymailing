jQuery(document).ready(readyFunction);

function readyFunction($) {
    Vue.use(infiniteScroll);
    const entityPerCalls = 500;
    let entityNumber = 0;
    let allEntities = [];
    let allEntitiesSelected = [];
    let numberOfEntities = 0;
    let numberOfCalls = 0;
    let strokeProgression = 0;

    const perScroll = 20;
    const start = {
        'available': perScroll,
        'selected': perScroll
    };

    let timeoutSearch;

    //get all entities in a which aren't in b
    const diff = (a, b) => a.filter((i) => JSON.stringify(b).indexOf(JSON.stringify(i)) === -1);

    //search in array
    const search = (array, search, columns) => {
        search = search.toLowerCase();
        return array.filter((entity) => columns.map(function (column) {
            if (entity[column] === null) {
                return false;
            }

            if (typeof entity[column] === 'number') {
                return entity[column].toString().toLowerCase().includes(search);
            }

            return entity[column].toLowerCase().includes(search);
        }).includes(true));
    };

    //remove from array
    const remove = (array, entityId) => array.filter((entity) => entity.id !== entityId);

    //get one element from array
    const getOne = (array, entityId) => array.filter((entity) => entity.id === entityId);

    //get entities ids
    const getIds = (array) => array.map((entity) => parseInt(entity.id));

    //get only selected
    const getSelected = (array, columnsNotNull) => array.filter((entity) => entity[columnsNotNull] !== null);

    function addTotalRecipients(selectedLists) {
        let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_campaigns&ctrl=' + acym_helper.ctrlCampaigns + '&task=countNumberOfRecipients';

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                listsSelected: selectedLists
            },
            beforeSend: function () {
                $('.acym__campaign__recipients__number-recipients, #acym__campaign__recipients__span').hide();
                $('.acym_loader_logo').show();
            }
        }).done(function (result) {
            $('.acym__campaign__recipients__number-recipients').html(result).show();
            $('#acym__campaign__recipients__span').show();
            $('.acym_loader_logo').hide();
        }).fail(function () {
            $('.acym__campaign__recipients__number-recipients').html(0);
        });
    }

    if (document.getElementById('acym__entity_select') !== null) {
        const appVue = new Vue({
            directives: {infiniteScroll},
            el: '#acym__entity_select',
            data: {
                tableJoin: '',
                entitiesUnselected: [],
                data: {},
                columnJoin: '',
                offsetBase: 5000,
                entity: '',
                displaySelected: true,
                loading: true,
                entitiesToDisplay_available: [],
                columnsToDisplay: [],
                entitiesToDisplay_selected: [],
                entitiesAvailable: [],
                entitiesSelected: [],
                join: '',
                busy: false,
                availableSearch: '',
                selectedSearch: '',
                displaySelectAll_available: true,
                displaySelectAll_selected: true,
                columnsClasses: []
            },
            mounted: function () {
                let entitySelectContainer = document.getElementById('acym__entity_select');
                entitySelectContainer.style.display = 'flex';
                this.entity = entitySelectContainer.getAttribute('data-entity');
                this.columnsToDisplay = entitySelectContainer.getAttribute('data-columns').split(',');
                this.columnsClasses = acym_helper.parseJson(entitySelectContainer.getAttribute('data-columns-class'));
                this.join = entitySelectContainer.getAttribute('data-join');
                this.displaySelected = entitySelectContainer.getAttribute('data-display-selected') === 'true';
                this.columnJoin = entitySelectContainer.getAttribute('data-column-join');
                this.tableJoin = entitySelectContainer.getAttribute('data-table-join');

                //we load everything
                this.getAllEntities(this.entity);
                $(window).off('refreshEntitySelect').on('refreshEntitySelect', function () {
                    readyFunction($);
                });
                setTimeout(() => {
                    acym_helperTooltip.setTooltip();
                }, 400);

                $('#acym__lists__settings__subscribers__entity__modal').off('open.zf.reveal').on('open.zf.reveal', function () {
                    acym_helperTooltip.setUserInfoLoadingHover();
                });
            },
            methods: {
                getClass(column) {
                    return typeof this.columnsClasses[column] === 'string' ? this.columnsClasses[column] : 'auto';
                },
                selectEntity(entityId) {
                    let entity = getOne(this.entitiesToDisplay_available, entityId)[0];
                    this.entitiesToDisplay_selected.push(entity);
                    this.entitiesSelected.push(entity);
                    this.entitiesToDisplay_available = remove(this.entitiesToDisplay_available, entityId);
                    this.entitiesAvailable = remove(this.entitiesAvailable, entityId);
                    this.entitiesUnselected = remove(this.entitiesUnselected, entityId);
                },
                unselectEntity(entityId) {
                    let entity = getOne(this.entitiesToDisplay_selected, entityId)[0];
                    this.entitiesToDisplay_available.push(entity);
                    this.entitiesUnselected.push(entity);
                    this.entitiesAvailable.push(entity);
                    this.entitiesToDisplay_selected = remove(this.entitiesToDisplay_selected, entityId);
                    this.entitiesSelected = remove(this.entitiesSelected, entityId);
                },
                loadMoreEntity(type) {
                    start[type] += perScroll;
                    let variableName = 'entitiesToDisplay_' + type;
                    let variableNameAll = 'entities' + type.charAt(0).toUpperCase() + type.slice(1);
                    this[variableName] = '' === this[type + 'Search'] ? this[variableNameAll].slice(0, start[type]) : search(this['entities'
                                                                                                                                  + type.charAt(0)
                                                                                                                                        .toUpperCase()
                                                                                                                                  + type.slice(1)],
                        this[type + 'Search'],
                        this.columnsToDisplay
                    );
                },
                loadMoreEntityAvailable() {
                    this.loadMoreEntity('available');
                },
                loadMoreEntitySelected() {
                    this.loadMoreEntity('selected');
                },
                moveAll(type) {
                    if ('available' === type) {
                        this.entitiesSelected = this.entitiesSelected.concat(this.entitiesAvailable);
                        this.entitiesAvailable = [];
                        this.entitiesToDisplay_available = [];
                        this.entitiesToDisplay_selected = this.entitiesSelected.slice(0, start.selected);
                    } else {
                        this.entitiesAvailable = this.entitiesAvailable.concat(this.entitiesSelected);
                        this.entitiesUnselected = this.entitiesUnselected.concat(this.entitiesSelected);
                        this.entitiesSelected = [];
                        this.entitiesToDisplay_available = this.entitiesAvailable.slice(0, start.available);
                        this.entitiesToDisplay_selected = [];
                    }
                },
                doSearch(type) {
                    clearTimeout(timeoutSearch);
                    timeoutSearch = setTimeout(() => {
                        if ('' === this[type + 'Search']) {
                            this['displaySelectAll_' + type] = true;
                            start[type] = perScroll;
                            this['entitiesToDisplay_' + type] = this['entities' + type.charAt(0).toUpperCase() + type.slice(1)].slice(0, start[type]);
                        } else {
                            this['displaySelectAll_' + type] = false;
                            this['entitiesToDisplay_' + type] = search(this['entities' + type.charAt(0).toUpperCase() + type.slice(1)],
                                this[type + 'Search'],
                                this.columnsToDisplay
                            );
                        }
                    }, 1000);
                },
                handleEntities(data) {
                    allEntities = allEntities.concat(data);
                    let currentBatchSelected = [];
                    if (this.columnJoin !== null) {
                        currentBatchSelected = getSelected(data, this.columnJoin);
                        allEntitiesSelected = allEntitiesSelected.concat(currentBatchSelected);
                    }

                    //We handle the new entities available
                    this.entitiesAvailable = this.entitiesAvailable.concat(diff(data, currentBatchSelected));
                    if (this.entitiesToDisplay_available.length < 1) this.entitiesToDisplay_available = this.entitiesAvailable.slice(0, start.available);

                    //We handle the new entities selected
                    if (this.displaySelected && this.columnJoin !== null) {
                        this.entitiesSelected = this.entitiesSelected.concat(currentBatchSelected);
                        if (this.entitiesToDisplay_selected.length < 1) this.entitiesToDisplay_selected = this.entitiesSelected.slice(0, start.selected);
                    }

                },
                getAllEntities(entity) {
                    let joinColumn = '';
                    if (!acym_helper.empty(this.tableJoin) && !acym_helper.empty(this.columnJoin)) {
                        joinColumn = '&join_table=' + this.tableJoin + '.' + this.columnJoin;
                    }
                    let ctrl = ACYM_IS_ADMIN ? 'entitySelect' : 'frontentityselect';

                    let ajaxUrl = ACYM_AJAX_URL;
                    ajaxUrl += '&ctrl=' + ctrl;
                    ajaxUrl += '&task=loadEntityFront';
                    ajaxUrl += '&offset=' + entityNumber;
                    ajaxUrl += '&perCalls=' + entityPerCalls;
                    ajaxUrl += '&entity=' + entity;
                    ajaxUrl += '&join=' + this.join;
                    ajaxUrl += '&columns=' + this.columnsToDisplay.join(',') + joinColumn;
                    $.get(ajaxUrl, (res) => {
                        res = acym_helper.parseJson(res);
                        if (!res) {
                            return false;
                        }
                        if (res.error) {
                            console.log(res.error);
                            return false;
                        }

                        if ('end' === res.data.results.data) {
                            this.loading = false;
                            return true;
                        }

                        numberOfEntities = res.data.results.data.total;
                        this.handleEntities(res.data.results.data.elements);
                        if (entityNumber > numberOfEntities) {
                            this.loading = false;
                            return true;
                        }
                        entityNumber += entityPerCalls;

                        this.getAllEntities(entity);

                        return true;
                    });
                },
                finalLoad() {
                    this.loading = false;

                    this.loadMoreEntity('available');
                    this.loadMoreEntity('selected');
                    let buttonSubmit = document.getElementById('acym__entity_select__button__submit');
                    if (typeof buttonSubmit != 'undefined' && buttonSubmit != null && buttonSubmit.classList.contains('acy_button_submit')) {
                        acym_helper.setSubmitButtonGlobal();
                    }

                    $('.acym__entity_select__button__close').off('click').on('click', function () {
                        $(this).closest('.reveal').foundation('close');
                    });

                    acym_helperImport.setVerificationGenericImport();
                    acym_helperImport.setImportCMSLists();
                    acym_helperImport.setCreateListFromImportPage();
                }
            },
            watch: {
                availableSearch: function () {
                    this.doSearch('available');
                },
                selectedSearch: function () {
                    this.doSearch('selected');
                },
                entitiesSelected() {
                    let idsSelected = getIds(this.entitiesSelected);
                    document.querySelector('[name="acym__entity_select__selected"]').value = JSON.stringify(idsSelected);
                    let campaignsCountRecipients = document.querySelector('.acym__campaign__recipients__number-recipients');
                    if (typeof (campaignsCountRecipients) != 'undefined' && campaignsCountRecipients != null) addTotalRecipients(idsSelected);
                },
                entitiesUnselected() {
                    document.querySelector('[name="acym__entity_select__unselected"]').value = JSON.stringify(getIds(this.entitiesUnselected));
                },
                loading(newValue) {
                    if (!newValue) {
                        this.finalLoad();
                    }
                }
            }
        });
    }
}
