const acym_helperScenario = {
    TYPE_ATTRIBUTE: 'data-acym-content-type',
    TYPE_TRIGGER: 'trigger',
    TYPE_DELAY: 'delay',
    TYPE_CONDITION: 'condition',
    TYPE_ACTION: 'action',
    TYPE_SETTINGS: 'settings',
    TIME_UNIT_TRANSLATION: {
        60: ACYM_JS_TXT.ACYM_MINUTES,
        3600: ACYM_JS_TXT.ACYM_HOURS,
        86400: ACYM_JS_TXT.ACYM_DAYS
    },
    icons: {
        trigger: 'trigger',
        condition: 'arrows-h',
        action: 'action',
        delay: 'access-time'
    },
    htmlGetter: {
        trigger: {
            containerId: 'acym_scenario_triggers',
            inputName: 'acym_scenario_triggers_input'
        },
        delay: {
            containerId: 'acym_scenario_delay',
            numberInputName: 'acym_scenario_delay_number',
            unitSelectName: 'acym_scenario_delay_unit'
        },
        condition: {
            containerId: 'acym_scenario_condition',
            inputName: 'acym_scenario_conditions_input'
        },
        action: {
            containerId: 'acym_scenario_action',
            inputName: 'acym_scenario_actions_input'
        },
        settings: {
            containerId: 'acym_scenario_settings'
        }
    },
    sendEmailParameters: {
        buttonCreate: null,
        stepSlugInput: null,
        mailIdInput: null
    },
    translation: {
        trigger: {},
        action: {},
        condition: {}
    },
    preOpenStepInput: null,
    settingsButton: null,
    rightPanel: null,
    rightPanelCloseButton: null,
    rightPanelContent: null,
    rightPanelSaveButtonFlow: null,
    rightPanelSaveButtonScenario: null,
    rightPanelCancelButton: null,
    rightPanelDeleteButton: null,
    rightPanelTitle: null,
    flowContainer: null,
    newScenarioContainer: null,
    chooseTriggerButton: null,
    baseSettingsElements: {},
    scenarioSettingsInputs: {
        name: null,
        active: null
    },
    currentFlow: [],
    cacheFlow: [],
    currentCachedVersion: 0,
    currentFlowInput: null,
    addDropdown: null,
    flowParams: {},
    currentEditingNode: null,
    currentAddParentSlug: null,
    lastEditedNodeSlug: null,
    init: function () {
        // This function needs to be called here because it modifies the body, and it's messing everything if called after the queries
        this.setAddDropdown();

        this.settingsButton = document.getElementById('acym__scenario__top__actions__configuration');
        this.rightPanel = document.getElementById('acym__scenario__edit__right__panel');
        this.rightPanelCloseButton = document.getElementById('acym__scenario__edit__right__panel__close');
        this.rightPanelContent = document.getElementById('acym__scenario__edit__right__panel__content');
        this.chooseTriggerButton = document.getElementById('acym__scenario__edit__content__new__choose__trigger');
        this.rightPanelSaveButtonFlow = document.getElementById('acym__scenario__edit__right__panel__save__flow');
        this.rightPanelSaveButtonScenario = document.getElementById('acym__scenario__edit__right__panel__save__scenario');
        this.rightPanelCancelButton = document.getElementById('acym__scenario__edit__right__panel__cancel');
        this.rightPanelDeleteButton = document.getElementById('acym__scenario__edit__right__panel__delete');
        this.rightPanelTitle = document.getElementById('acym__scenario__edit__right__panel__title');
        this.flowContainer = document.getElementById('acym__scenario__edit__content__flow');
        this.currentFlowInput = document.getElementById('acym__scenario__edit__value');
        this.newScenarioContainer = document.getElementById('acym__scenario__edit__content__new');
        this.baseSettingsElements.trigger = document.querySelector(`#${this.htmlGetter.trigger.containerId}`);
        this.baseSettingsElements.delay = document.querySelector(`#${this.htmlGetter.delay.containerId}`);
        this.baseSettingsElements.condition = document.querySelector(`#${this.htmlGetter.condition.containerId}`);
        this.baseSettingsElements.action = document.querySelector(`#${this.htmlGetter.action.containerId}`);
        this.baseSettingsElements.settings = document.querySelector(`#${this.htmlGetter.settings.containerId}`);
        this.scenarioSettingsInputs.name = document.querySelector('#acym_scenario_settings [name="scenario[name]"]');
        this.scenarioSettingsInputs.active = document.querySelector('#acym_scenario_settings [name="scenario[active]"]');
        this.sendEmailParameters.stepSlugInput = document.querySelector('#acym_scenario_action [name="send_mail[step_id]"]');
        this.sendEmailParameters.mailIdInput = document.querySelector('#acym_scenario_action [name="send_mail[mail_id]"]');
        this.sendEmailParameters.buttonCreate = document.querySelector('#acym_scenario_action [data-task="createMail"]');
        this.preOpenStepInput = document.getElementById('acym__scenario__preopen__stepid');

        const rawStepIds = document.getElementById('acym__scenario__edit__container').getAttribute('data-acym-step-ids');
        const stepIds = rawStepIds ? JSON.parse(rawStepIds) : [];

        this.flowParams = {
            id: 'acym__scenario__edit__content__flow',
            addButtonClick: this.clickOnAdd,
            cardClick: this.clickOnCard,
            undoFunction: this.undo,
            redoFunction: this.redo,
            listOfExistingSlugs: stepIds
        };

        this.setTranslation();
        this.setupFlowFromSaved();
        this.setCloseRightPanel();
        this.setChooseTrigger();
        this.setSaveRightPanel();
        this.setAddDelay();
        this.setAddCondition();
        this.setAddAction();
        this.setDeleteNode();
        this.setSettingsButton();
        this.openStepOnEditionOpen();

        acym_helper.setSubmitButtonGlobal();
    },
    setTranslation: function () {
        const rawTrigger = JSON.parse(document.getElementById('acym_scenario_triggers_data').value);
        const rawCondition = JSON.parse(document.getElementById('acym_scenario_conditions_data').value);
        const rawAction = JSON.parse(document.getElementById('acym_scenario_actions_data').value);

        rawTrigger.forEach((trigger) => {
            this.translation.trigger[trigger.key] = trigger.name.toLowerCase();
        });

        rawCondition.forEach((condition) => {
            this.translation.condition[condition.key] = condition.name.toLowerCase();
        });

        rawAction.forEach((action) => {
            this.translation.action[action.key] = action.name.toLowerCase();
        });
    },
    resetCurrentParentSlug: function () {
        this.currentAddParentSlug = null;
    },

    // ADD DROPDOWN

    setAddDropdown: function () {
        const addDropdownHtml = `<div id="acym__scenario__edit__add__overlay" style="display: none;">
<div class="acym__scenario__edit__add__overlay__item" id="acym__scenario__edit__add__delay"><i class="acymicon-add"></i><p>${ACYM_JS_TXT.ACYM_DELAY}</p></div>
<div class="acym__scenario__edit__add__overlay__item" id="acym__scenario__edit__add__condition"><i class="acymicon-add"></i><p>${ACYM_JS_TXT.ACYM_CONDITION}</p></div>
<div class="acym__scenario__edit__add__overlay__item" id="acym__scenario__edit__add__action"><i class="acymicon-add"></i><p>${ACYM_JS_TXT.ACYM_ACTION}</p></div>
</div>`;

        document.body.insertAdjacentHTML('beforeend', addDropdownHtml);
        this.addDropdown = document.getElementById('acym__scenario__edit__add__overlay');

        document.body.addEventListener('mousedown', (event) => {
            const isAddDropdownDisplayed = acym_helperScenario.addDropdown.style.display === 'flex';
            const isNotClickingOnAddButton = event.target.id !== 'acym__scenario__edit__add__overlay' && event.target.closest(
                '#acym__scenario__edit__add__overlay') === null;
            if (isAddDropdownDisplayed && isNotClickingOnAddButton) {
                acym_helperScenario.hideAddDropdown();
                acym_helperScenario.resetCurrentParentSlug();
            }
        });
    },
    setAddDelay: function () {
        document.getElementById('acym__scenario__edit__add__delay').addEventListener('click', () => {
            this.hideAddDropdown();
            this.displayRightPanelSettings(this.TYPE_DELAY, this.baseSettingsElements.delay);
        });
    },
    setAddCondition: function () {
        document.getElementById('acym__scenario__edit__add__condition').addEventListener('click', () => {
            this.hideAddDropdown();
            this.displayRightPanelSettings(this.TYPE_CONDITION, this.baseSettingsElements.condition);
        });
    },
    setAddAction: function () {
        document.getElementById('acym__scenario__edit__add__action').addEventListener('click', () => {
            this.hideAddDropdown();
            this.displayRightPanelSettings(this.TYPE_ACTION, this.baseSettingsElements.action);
        });
    },
    setTriggerLabel: function (trigger) {
        return `${ACYM_JS_TXT.ACYM_TRIGGER} ${this.translation.trigger[trigger]}`;
    },
    setWaitLabel: function (delay, unit) {
        return acym_helper.sprintf(ACYM_JS_TXT.ACYM_WAIT_X_UNIT, delay, this.TIME_UNIT_TRANSLATION[unit]);
    },
    setActionLabel: function (action) {
        return `${ACYM_JS_TXT.ACYM_ACTION} ${this.translation.action[action]}`;
    },
    setConditionLabel: function (condition) {
        return `${ACYM_JS_TXT.ACYM_CONDITION} ${this.translation.condition[condition]}`;
    },
    hideAddDropdown: function () {
        this.addDropdown.style.display = 'none';
    },

    // NEW SCENARIO BUTTONS

    setChooseTrigger: function () {
        this.chooseTriggerButton.addEventListener('click', () => {
            this.displayRightPanelSettings(this.TYPE_TRIGGER, this.baseSettingsElements.trigger);
        });
    },

    // RIGHT PANEL

    displayRightPanelSettings: function (type, elementToDisplay, params = undefined) {
        if (type === this.TYPE_SETTINGS) {
            this.rightPanelSaveButtonScenario.style.display = 'flex';
        } else {
            this.rightPanelSaveButtonFlow.style.display = 'flex';
        }

        this.rightPanel.style.display = 'flex';
        this.rightPanelContent.innerHTML = elementToDisplay.outerHTML;
        this.rightPanelContent.setAttribute(this.TYPE_ATTRIBUTE, type);

        if (params) {
            this.applyRightPanelParams(type, params);

            if ([
                this.TYPE_CONDITION,
                this.TYPE_ACTION,
                this.TYPE_DELAY
            ].includes(type)) {
                this.rightPanelDeleteButton.style.display = 'flex';
            }
        }

        switch (type) {
            case this.TYPE_TRIGGER:
                this.setRightPanelCurrentOption(this.htmlGetter.trigger.inputName, 'data-acym-trigger-option');
                this.rightPanelTitle.innerText = ACYM_JS_TXT.ACYM_TRIGGER;
                break;
            case this.TYPE_CONDITION:
                this.setRightPanelCurrentOption(this.htmlGetter.condition.inputName, 'data-acym-condition-option');
                this.setRightPanelConditionDateOption();
                this.rightPanelTitle.innerText = ACYM_JS_TXT.ACYM_CONDITION;
                break;
            case this.TYPE_ACTION:
                this.setRightPanelCurrentOption(this.htmlGetter.action.inputName, 'data-acym-action-option');
                this.setRightPanelActionSendEmail();
                this.rightPanelTitle.innerText = ACYM_JS_TXT.ACYM_ACTION;
                break;
            case this.TYPE_DELAY:
                this.rightPanelTitle.innerText = ACYM_JS_TXT.ACYM_DELAY;
                break;
            case this.TYPE_SETTINGS:
                this.rightPanelTitle.innerText = ACYM_JS_TXT.ACYM_SETTINGS;
                this.setSettingsInputs();
                break;
        }

        this.setFunctionSettingsRightPanel();

        // We add a delay to avoid the click event to be triggered by the click that opened the right panel
        setTimeout(() => {
            acym_helperScenarioRightPanel.addCloseRightPanelListener(this.closeRightPanel);
        }, 100);
    },
    setRightPanelActionSendEmail: function () {
        const inputEmail = this.rightPanelContent.querySelector('#acym__action__send__email__saved__id');
        const emailInfoContainer = this.rightPanelContent.querySelector('#acym__action__send__email__saved');
        emailInfoContainer.style.display = inputEmail.value ? 'flex' : 'none';

        // Set click on button create or edit email
        const createEmailButton = this.rightPanelContent.querySelector('[data-task="createMail"]');
        const editEmailButton = this.rightPanelContent.querySelector('#acym__action__send__email__saved__edit');
        if (!createEmailButton) {
            return;
        }

        const clickOnButton = () => {
            this.rightPanelSaveButtonFlow.click();
            this.sendEmailParameters.stepSlugInput.value = this.lastEditedNodeSlug;
            this.sendEmailParameters.mailIdInput.value = inputEmail.value;

            this.sendEmailParameters.buttonCreate.click();
        };

        createEmailButton.addEventListener('click', clickOnButton);
        if (editEmailButton) {
            editEmailButton.addEventListener('click', clickOnButton);
        }

        // Set email name in right panel
        const nodeParams = this.getNodeParams(this.currentEditingNode, this.currentFlow);

        if (!nodeParams || !nodeParams.option.mail) {
            return;
        }

        const nameLabel = this.rightPanelContent.querySelector('#acym__action__send__email__saved__name');
        nameLabel.innerHTML = nodeParams.option.mail.name;

        // Set the delete icon
        const deleteIcon = this.rightPanelContent.querySelector('#acym__action__send__email__saved__delete');
        deleteIcon.addEventListener('click', () => {
            inputEmail.value = '';
            nameLabel.innerHTML = '';
            emailInfoContainer.style.display = 'none';
        });
    },
    setRightPanelCurrentOption: function (inputName, attributeName) {
        const setOption = (key) => {
            const allTriggerOption = this.rightPanelContent.querySelectorAll(`[${attributeName}]`);
            allTriggerOption.forEach((option) => {
                if (option.getAttribute(attributeName) === key) {
                    option.style.display = 'flex';
                } else {
                    option.style.display = 'none';
                }
            });
        };

        const select = this.rightPanelContent.querySelector(`[name="${inputName}"]`);
        setOption(select.value);

        jQuery(select).on('change', () => {
            setOption(select.value);
        });
    },
    setRightPanelConditionDateOption: function () {
        const dateMinInput = this.rightPanelContent.querySelector('[data-rs="acym_acym_conditionconditions__numor____numand__acy_listdatemin"]');
        if (!dateMinInput) {
            return;
        }
        if (dateMinInput.value) {
            acym_helperFilter.setFieldValue(jQuery(dateMinInput), dateMinInput.value);
        }
        const dateMaxInput = this.rightPanelContent.querySelector('[data-rs="acym_acym_conditionconditions__numor____numand__acy_listdatemax"]');
        if (dateMaxInput.value) {
            acym_helperFilter.setFieldValue(jQuery(dateMaxInput), dateMaxInput.value);
        }
    },
    closeRightPanel: function () {
        acym_helperScenarioRightPanel.removeCloseRightPanelListener();
        acym_helperScenario.rightPanel.style.display = 'none';
        acym_helperScenario.rightPanelContent.removeAttribute(acym_helperScenario.TYPE_ATTRIBUTE);
        acym_helperScenario.rightPanelContent.innerHTML = '';
        acym_helperScenario.currentEditingNode = null;
        acym_helperScenario.rightPanelDeleteButton.style.display = 'none';
        acym_helperScenario.rightPanelSaveButtonScenario.style.display = 'none';
        acym_helperScenario.rightPanelSaveButtonFlow.style.display = 'none';
    },
    setCloseRightPanel: function () {
        this.rightPanelCloseButton.addEventListener('click', () => {
            this.closeRightPanel();
        });
        this.rightPanelCancelButton.addEventListener('click', () => {
            this.closeRightPanel();
        });
    },
    setDeleteNode: function () {
        this.rightPanelDeleteButton.addEventListener('click', () => {
            const nodeParams = this.getNodeParams(this.currentEditingNode, this.currentFlow);
            if (!nodeParams) {
                return;
            }

            const nodeTypeTrad = {
                delay: 'ACYM_DELAY',
                condition: 'ACYM_CONDITION',
                action: 'ACYM_ACTION'
            };

            const node = this.getNode(this.currentEditingNode, this.currentFlow);

            const confirmTranslationKey = node.children && node.children.length > 0
                                          ? 'ACYM_SCENARIO_ARE_YOU_SURE_DELETE_X_WITH_CHILDREN_NODES'
                                          : 'ACYM_SCENARIO_ARE_YOU_SURE_DELETE_X';
            const confirmMessage = acym_helper.sprintf(ACYM_JS_TXT[confirmTranslationKey], ACYM_JS_TXT[nodeTypeTrad[nodeParams.type]].toLowerCase());

            if (!confirm(confirmMessage)) {
                return;
            }

            acym_helperFlow.deleteNode(this.currentFlow, this.currentEditingNode);
            this.updateCurrentFlowInput();
            this.closeRightPanel();
            this.displayFlow();
        });
    },
    setSaveRightPanel: function () {
        this.rightPanelSaveButtonFlow.addEventListener('click', () => {
            try {
                switch (this.rightPanelContent.attributes[this.TYPE_ATTRIBUTE].value) {
                    case this.TYPE_TRIGGER:
                        this.saveTrigger();
                        break;
                    case this.TYPE_DELAY:
                        this.saveDelay();
                        break;
                    case this.TYPE_CONDITION:
                        this.saveCondition();
                        break;
                    case this.TYPE_ACTION:
                        this.saveAction();
                        break;
                }
            } catch (error) {
                alert(error.message);
                return;
            }

            this.closeRightPanel();
        });
    },
    applyRightPanelParams: function (type, params) {
        const changeValue = (element, value) => {
            const select2AjaxDataClass = element.getAttribute('data-class') && element.getAttribute('data-class').includes('acym_select2_ajax');
            const select2AjaxClass = element.getAttribute('class') && element.getAttribute('class').includes('acym_select2_ajax');
            const select2DataClass = element.getAttribute('data-class') && element.getAttribute('data-class').includes('acym__select');
            const select2Class = element.getAttribute('class') && element.getAttribute('class').includes('acym__select');
            if (select2AjaxDataClass || select2AjaxClass) {
                element.setAttribute('data-selected', value);
            } else if (select2Class || select2DataClass) {
                jQuery(element).val(value);
            } else {
                element.value = value;
            }
        };
        switch (type) {
            case this.TYPE_TRIGGER:
                const triggerSelect = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.trigger.inputName}"]`);
                changeValue(triggerSelect, params.trigger);

                if (params.option) {
                    for (const key in params.option) {
                        const input = this.rightPanelContent.querySelector(`[name="${key}"]`);
                        if (input) {
                            changeValue(input, params.option[key]);
                        }
                    }
                }

                break;
            case this.TYPE_DELAY:
                const inputDelay = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.delay.numberInputName}"]`);
                const selectUnit = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.delay.unitSelectName}"]`);
                changeValue(inputDelay, params.delay);
                changeValue(selectUnit, params.unit);
                break;
            case this.TYPE_CONDITION:
                const conditionSelect = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.condition.inputName}"]`);
                changeValue(conditionSelect, params.condition);

                if (params.option) {
                    for (const key in params.option) {
                        const input = this.rightPanelContent.querySelector(`[name="${key}"]`);
                        if (input) {
                            changeValue(input, params.option[key]);
                        }
                    }
                }

                break;
            case this.TYPE_ACTION:
                const actionSelect = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.action.inputName}"]`);
                changeValue(actionSelect, params.action);

                if (params.option) {
                    for (const key in params.option) {
                        const input = this.rightPanelContent.querySelector(`[name="${key}"]`);
                        if (input) {
                            changeValue(input, params.option[key]);
                        }
                    }
                }

                break;
        }
    },
    setSettingsButton: function () {
        this.settingsButton.addEventListener('click', () => {
            acym_helperScenario.displayRightPanelSettings(this.TYPE_SETTINGS, acym_helperScenario.baseSettingsElements.settings);
        });
    },
    areGeneralInformationSet: function () {
        if (!this.scenarioSettingsInputs.name.value) {
            acym_helperScenario.displayRightPanelSettings(this.TYPE_SETTINGS, acym_helperScenario.baseSettingsElements.settings);
            return false;
        }

        return true;
    },

    // SAVE NODES

    saveTrigger: function () {
        const triggerSelect = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.trigger.inputName}"]`);
        const triggerOption = this.rightPanelContent.querySelector(`[data-acym-trigger-option="${triggerSelect.value}"]`);
        const triggerOptionParams = {};
        if (triggerOption) {
            const inputs = triggerOption.querySelectorAll('[name^="[triggers][user]"]');
            if (inputs) {
                inputs.forEach((input) => {
                    const isSelect = input.getAttribute('class') && input.getAttribute('class').includes('acym__select');
                    triggerOptionParams[input.name] = isSelect ? jQuery(input).val() : input.value;
                });
            }
        }

        const node = acym_helperFlow.createNode(this.setTriggerLabel(triggerSelect.value), this.icons.trigger);
        node.params = {
            type: this.TYPE_TRIGGER,
            trigger: triggerSelect.value,
            option: triggerOptionParams
        };
        if (this.currentEditingNode) {
            node.slug = this.currentEditingNode;
        }

        acym_helperFlow.addUpdateNewNode(this.currentFlow, node);
        this.updateCurrentFlowInput();
        this.displayFlow();
    },
    saveDelay: function () {
        const inputDelay = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.delay.numberInputName}"]`);
        const selectUnit = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.delay.unitSelectName}"]`);
        const nodeLabel = this.setWaitLabel(inputDelay.value, selectUnit.value);
        const node = acym_helperFlow.createNode(nodeLabel, this.icons.delay);

        if (inputDelay.value == 0) {
            throw new Error(ACYM_JS_TXT.ACYM_DELAY_MUST_BE_SET);
        }

        node.params = {
            type: this.TYPE_DELAY,
            delay: inputDelay.value,
            unit: selectUnit.value
        };
        if (this.currentEditingNode) {
            node.slug = this.currentEditingNode;
        }

        acym_helperFlow.addUpdateNewNode(this.currentFlow, node, this.currentAddParentSlug);
        this.updateCurrentFlowInput();
        this.displayFlow();
    },
    saveCondition: function () {
        const conditionSelect = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.condition.inputName}"]`);
        const conditionOption = this.rightPanelContent.querySelector(`[data-acym-condition-option="${conditionSelect.value}"]`);
        const conditionOptionParams = {};
        if (conditionOption) {
            const inputs = conditionOption.querySelectorAll('[name^="acym_condition[conditions]"]');
            if (inputs) {
                inputs.forEach((input) => {
                    conditionOptionParams[input.name] = input.value;
                });
            }
        }

        const node = acym_helperFlow.createNode(this.setConditionLabel(conditionSelect.value), this.icons.condition, true);
        node.params = {
            type: this.TYPE_CONDITION,
            condition: conditionSelect.value,
            option: conditionOptionParams
        };
        if (this.currentEditingNode) {
            node.slug = this.currentEditingNode;
        }

        acym_helperFlow.addUpdateNewNode(this.currentFlow, node, this.currentAddParentSlug);
        this.updateCurrentFlowInput();
        this.displayFlow();
    },
    saveAction: function () {
        const actionSelect = this.rightPanelContent.querySelector(`[name="${this.htmlGetter.action.inputName}"]`);
        const actionOption = this.rightPanelContent.querySelector(`[data-acym-action-option="${actionSelect.value}"]`);
        const actionOptionParams = {};
        if (actionOption) {
            const inputs = actionOption.querySelectorAll('[name^="acym_action[actions]"]');
            if (inputs) {
                inputs.forEach((input) => {
                    actionOptionParams[input.name] = input.value;
                });
            }
        }

        const node = acym_helperFlow.createNode(this.setActionLabel(actionSelect.value), this.icons.action);
        node.params = {
            type: this.TYPE_ACTION,
            action: actionSelect.value,
            option: actionOptionParams
        };
        if (this.currentEditingNode) {
            node.slug = this.currentEditingNode;
        }

        this.lastEditedNodeSlug = node.slug;

        acym_helperFlow.addUpdateNewNode(this.currentFlow, node, this.currentAddParentSlug);
        this.updateCurrentFlowInput();
        this.displayFlow();
    },

    // FLOW DISPLAY

    displayFlow: function () {
        this.flowContainer.style.display = 'flex';
        this.newScenarioContainer.style.display = 'none';
        acym_helperFlow.createFlow(this.currentFlow, this.flowParams);
        this.updateCurrentFlowInput();
    },
    openStepOnEditionOpen: function () {
        if (!this.preOpenStepInput.value) {
            return;
        }

        const cardToOpen = document.querySelector(`#card_${this.preOpenStepInput.value}`);

        if (cardToOpen) {
            cardToOpen.click();
        }
    },

    // FLOW ACTIONS

    clickOnAdd: function (slug) {
        acym_helperScenario.closeRightPanel();
        // In this function as we are in a callback, the context is not the same as the one of the class
        // So we need to use "acym_helperScenario" instead of "this"

        acym_helperScenario.currentAddParentSlug = slug;

        const addButtonClicked = document.querySelector(`#${slug} .flow__step__card__add`);

        const addButtonClickedRect = addButtonClicked.getBoundingClientRect();
        acym_helperScenario.addDropdown.style.top = addButtonClickedRect.top + 'px';
        acym_helperScenario.addDropdown.style.left = addButtonClickedRect.left + addButtonClickedRect.width + 10 + 'px';
        acym_helperScenario.addDropdown.style.display = 'flex';
    },
    clickOnCard: function (slug) {
        acym_helperScenario.closeRightPanel();

        const nodeParams = acym_helperScenario.getNodeParams(slug, acym_helperScenario.currentFlow);
        if (nodeParams === null) {
            console.error(`Node params not found for slug ${slug}`);
            return;
        }

        acym_helperScenario.currentEditingNode = slug;
        switch (nodeParams.type) {
            case acym_helperScenario.TYPE_TRIGGER:
                acym_helperScenario.displayRightPanelSettings(acym_helperScenario.TYPE_TRIGGER, acym_helperScenario.baseSettingsElements.trigger, nodeParams);
                break;
            case acym_helperScenario.TYPE_DELAY:
                acym_helperScenario.displayRightPanelSettings(acym_helperScenario.TYPE_DELAY, acym_helperScenario.baseSettingsElements.delay, nodeParams);
                break;
            case acym_helperScenario.TYPE_CONDITION:
                acym_helperScenario.displayRightPanelSettings(acym_helperScenario.TYPE_CONDITION,
                    acym_helperScenario.baseSettingsElements.condition,
                    nodeParams
                );
                break;
            case acym_helperScenario.TYPE_ACTION:
                acym_helperScenario.displayRightPanelSettings(acym_helperScenario.TYPE_ACTION, acym_helperScenario.baseSettingsElements.action, nodeParams);
                break;
        }
    },
    undo: function () {
        acym_helperScenario.currentCachedVersion = acym_helperScenario.getNewCurrentCacheVersion(true);
        acym_helperScenario.currentFlow = JSON.parse(JSON.stringify(acym_helperScenario.cacheFlow[acym_helperScenario.currentCachedVersion]));
        acym_helperFlow.createFlow(acym_helperScenario.currentFlow, acym_helperScenario.flowParams);
    },
    redo: function () {
        acym_helperScenario.currentCachedVersion = acym_helperScenario.getNewCurrentCacheVersion(false);
        acym_helperScenario.currentFlow = JSON.parse(JSON.stringify(acym_helperScenario.cacheFlow[acym_helperScenario.currentCachedVersion]));
        acym_helperFlow.createFlow(acym_helperScenario.currentFlow, acym_helperScenario.flowParams);
    },
    getNewCurrentCacheVersion: function (isUndo) {
        const newCacheVersion = isUndo ? acym_helperScenario.currentCachedVersion - 1 : acym_helperScenario.currentCachedVersion + 1;
        if (newCacheVersion < 0 || newCacheVersion > acym_helperScenario.cacheFlow.length - 1 || !acym_helperScenario.cacheFlow[newCacheVersion]) {
            return acym_helperScenario.currentCachedVersion;
        }

        return newCacheVersion;
    },

    // FLOW UTILS

    getNode: function (slug, flowNodes) {
        for (const node of flowNodes) {
            if (node.slug === slug) {
                return node;
            } else if (Array.isArray(node.children)) {
                const nodeChild = this.getNode(slug, node.children);
                if (nodeChild !== null) {
                    return nodeChild;
                }
            }
        }

        return null;
    },
    getNodeParams: function (slug, flowNodes) {
        const node = this.getNode(slug, flowNodes);
        if (!node) {
            return null;
        }

        return node.params ? node.params : null;
    },
    setFunctionSettingsRightPanel: function () {
        this.setSelect2();
        jQuery(document).foundation();
        acym_helperModal.initModal();
        acym_helperDatePicker.setDatePickerGlobal();
        acym_helperDatePicker.setRSDateChoice();
        acym_helperSwitch.setSwitchFieldsGlobal();
    },
    updateCurrentFlowInput: function (isCreation = false) {
        this.currentFlowInput.value = JSON.stringify(this.currentFlow);

        if (isCreation) {
            return;
        }
        this.storeCacheFlow();
    },
    setupFlowFromSaved: function () {
        const savedFlowRaw = document.getElementById('acym__scenario__saved__flow').value;

        if (!savedFlowRaw) {
            return;
        }

        const savedFlow = JSON.parse(savedFlowRaw);
        this.createNodeFromSaved(savedFlow);
        this.updateCurrentFlowInput(true);
        this.displayFlow();
    },
    createNodeFromSaved: function (currentNode, parentSlug = null) {
        let node;
        switch (currentNode.params.type) {
            case this.TYPE_TRIGGER:
                node = acym_helperFlow.createNode(this.setTriggerLabel(currentNode.params.trigger), this.icons.trigger);
                break;
            case this.TYPE_DELAY:
                node = acym_helperFlow.createNode(this.setWaitLabel(currentNode.params.delay, currentNode.params.unit), this.icons.delay);
                break;
            case this.TYPE_ACTION:
                node = acym_helperFlow.createNode(this.setActionLabel(currentNode.params.action), this.icons.action);
                break;
            case this.TYPE_CONDITION:
                node = acym_helperFlow.createNode(this.setConditionLabel(currentNode.params.condition), this.icons.condition, true);
                break;
        }

        if (currentNode.slug) {
            node.slug = currentNode.slug;
        }

        node.params = currentNode.params;

        acym_helperFlow.addUpdateNewNode(this.currentFlow, node, parentSlug);

        if (currentNode.children) {
            if (currentNode.condition) {
                if (currentNode.children[0]) {
                    this.createNodeFromSaved(currentNode.children[0], node.children[0].slug);
                }

                if (currentNode.children[1]) {
                    this.createNodeFromSaved(currentNode.children[1], node.children[1].slug);
                }
            } else {
                for (const child of currentNode.children) {
                    this.createNodeFromSaved(child, node.slug);
                }
            }
        }
    },
    storeCacheFlow: function () {
        if (JSON.stringify(this.cacheFlow[this.cacheFlow.length - 1]) === JSON.stringify(this.currentFlow)) {
            return;
        }

        if (this.currentCachedVersion < this.cacheFlow.length - 1) {
            this.cacheFlow = this.cacheFlow.slice(0, this.currentCachedVersion + 1);
        }

        this.cacheFlow.push(JSON.parse(JSON.stringify(this.currentFlow)));
        this.currentCachedVersion = this.cacheFlow.length - 1;
    },

    // SELECT2

    setSelect2: function () {
        this.rightPanelContent.querySelectorAll('[data-class]').forEach((element) => {
            // If they are spaces, we need to create an array
            element.classList.add(...element.getAttribute('data-class').split(' '));
            element.removeAttribute('data-class');
        });

        jQuery('#acym__scenario__edit__right__panel__content .acym__select, #acym_acym_conditionconditions__numor____numand__acy_listdatemin .acym__select')
            .select2({
                theme: 'foundation',
                width: '100%'
            });

        jQuery('#acym__scenario__edit__right__panel__content .intext_select_automation select')
            .select2({
                theme: 'foundation',
                width: '100%'
            });

        acym_helperSelect2.setAjaxSelect2('#acym__scenario__edit__right__panel__content');
    },

    // GLOBAL SETTINGS UTILS

    setSettingsInputs: function () {
        const nameInput = this.rightPanel.querySelector('[name="scenario[name]"]');
        nameInput.value = this.scenarioSettingsInputs.name.value;
        nameInput.focus();

        const activeInput = this.rightPanel.querySelector('[name="scenario[active]"]');
        activeInput.value = this.scenarioSettingsInputs.active.value;

        this.rightPanelSaveButtonScenario.addEventListener('click', () => {
            this.scenarioSettingsInputs.name.value = nameInput.value;
            this.scenarioSettingsInputs.active.value = activeInput.value === '1' ? 1 : 0;

            this.closeRightPanel();
        });
    }
};
