jQuery(function ($) {

    const currentStepData = {
        scenarioId: null,
        stepId: null,
        type: null,
        page: null,
        search: null
    };
    let oldStepDataForReturn = {};

    function Init() {
        setSankeyChart();
        setCloseRightPanel();
    }

    function resetCurrentStepData() {
        for (let key in currentStepData) {
            currentStepData[key] = null;
        }

        oldStepDataForReturn = {};
    }

    function setRefreshRightPanel() {
        setPagination();
        setUserClickDetails();
        setSearchInput();
        setReturnListingButton();
    }

    function setReturnListingButton() {
        const returnButton = document.getElementById('acym__scenario__performance__user__back');

        if (!returnButton) {
            return;
        }

        returnButton.addEventListener('click', function () {
            for (let key in currentStepData) {
                if (oldStepDataForReturn[key] === null) {
                    continue;
                }
                currentStepData[key] = oldStepDataForReturn[key];
            }

            oldStepDataForReturn = {};

            getStepInfoAjax();
        });
    }

    function setSearchInput() {
        const searchInput = document.getElementById('acym__scenario__performance__step__search');

        if (!searchInput) {
            return;
        }

        let timeout = null;

        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentStepData.search = searchInput.value;
                getStepInfoAjax();
            }, 500);
        });
    }

    function setPagination() {
        const pageInput = document.getElementById('acym_pagination');
        const pageButtons = document.getElementsByClassName('acym__pagination__page');

        if (!pageInput) {
            return;
        }

        pageInput.addEventListener('change', () => {
            currentStepData.page = pageInput.value;
            getStepInfoAjax();
        });

        for (let i = 0 ; i < pageButtons.length ; i++) {
            pageButtons[i].addEventListener('click', function () {
                currentStepData.page = this.getAttribute('page');
                getStepInfoAjax();
            });
        }
    }

    function setUserClickDetails() {
        const userLinks = document.getElementsByClassName('acym__scenario__performance__trigger__user');

        for (let i = 0 ; i < userLinks.length ; i++) {
            userLinks[i].addEventListener('click', function () {
                oldStepDataForReturn = JSON.parse(JSON.stringify(currentStepData));
                getUserInfoAjax(this.getAttribute('data-acym-user-id'), this.getAttribute('data-acym-process-id'));
            });
        }
    }

    function getUserInfoAjax(userId, processId) {
        const rightPanelContent = document.getElementById('acym__scenario__edit__right__panel__content');

        if (!userId || !processId || !rightPanelContent) {
            return;
        }

        toggleLoaderRightPanel(true);

        const data = {
            ctrl: 'scenarios',
            task: 'getUserInfo',
            userId: userId,
            processId: processId
        };

        acym_helper.get(ACYM_AJAX_URL, data).then(res => {
            toggleLoaderRightPanel(false);
            if (res.error) {
                // TODO show error
                return;
            }

            rightPanelContent.innerHTML = res.data.content;
            setRefreshRightPanel();
        });
    }

    function getStepInfoAjax() {
        const rightPanelContent = document.getElementById('acym__scenario__edit__right__panel__content');

        if (!rightPanelContent) {
            return;
        }

        toggleLoaderRightPanel(true);

        const data = {
            ctrl: 'scenarios',
            task: 'getStepStats',
            scenarioId: currentStepData.scenarioId,
            stepId: currentStepData.stepId,
            type: currentStepData.type,
            page: currentStepData.page,
            search: currentStepData.search
        };

        acym_helper.get(ACYM_AJAX_URL, data).then(res => {
            toggleLoaderRightPanel(false);
            if (res.error) {
                // TODO show error
                return;
            }

            rightPanelContent.innerHTML = res.data.content;
            setRefreshRightPanel();
        });
    }

    function toggleLoaderRightPanel(loading) {
        const rightPanelContent = document.getElementById('acym__scenario__edit__right__panel__content');

        if (!rightPanelContent) {
            return;
        }

        if (loading) {
            rightPanelContent.innerHTML = '<div id="acym__scenario__edit__right__panel__content__loader"><i class="acymicon-circle-o-notch acymicon-spin"></i></div>';
        } else {
            rightPanelContent.innerHTML = '';
        }

    }

    function openRightPanel(data) {
        const rightPanel = document.getElementById('acym__scenario__edit__right__panel');
        const rightPanelTitle = document.getElementById('acym__scenario__edit__right__panel__title');

        if (!rightPanel || !rightPanelTitle) {
            return;
        }

        if (!data.type || !data.scenarioId || data.stepId === undefined) {
            return;
        }

        currentStepData.scenarioId = data.scenarioId;
        currentStepData.stepId = data.stepId;
        currentStepData.type = data.type;

        getStepInfoAjax();

        rightPanelTitle.innerText = data.name;
        rightPanel.style.display = 'flex';

        // We add a delay to avoid the click event to be triggered by the click that opened the right panel
        setTimeout(() => {
            acym_helperScenarioRightPanel.addCloseRightPanelListener(closeRightPanel);
        }, 100);
    }

    function setCloseRightPanel() {
        const rightPanelCloseButton = document.getElementById('acym__scenario__edit__right__panel__close');

        if (!rightPanelCloseButton) {
            return;
        }

        rightPanelCloseButton.addEventListener('click', () => {
            closeRightPanel();
        });
    }

    function closeRightPanel() {
        acym_helperScenarioRightPanel.removeCloseRightPanelListener();

        const rightPanel = document.getElementById('acym__scenario__edit__right__panel');
        const rightPanelContent = document.getElementById('acym__scenario__edit__right__panel__content');

        if (!rightPanel || !rightPanelContent) {
            return;
        }

        rightPanel.style.display = 'none';
        rightPanelContent.innerHTML = '';
        resetCurrentStepData();
    }

    function setSankeyChart() {
        const inputData = document.getElementById('acym__scenario__performances__chart-data');
        const inputNodesData = document.getElementById('acym__scenario__performances__chart-nodes');

        if (!inputData || !inputNodesData) {
            return;
        }

        const data = JSON.parse(inputData.value);
        const nodesData = JSON.parse(inputNodesData.value);

        const labels = {};

        for (const [key, node] of Object.entries(nodesData)) {
            labels[key] = node.name;
        }

        SankeyChart.display({
            id: 'acym__scenario__performances__sankey',
            data,
            labels,
            nodeClickCallback: function (nodeId, isLastNodeBranch) {
                if (nodesData[nodeId]) {
                    openRightPanel(nodesData[nodeId]);
                }
            },
            interactive: true,
            iterationHidden: 3,
            interactiveOn: 'link'
        });
    }

    Init();
});
