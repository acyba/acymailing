const acym_helperScenarioRightPanel = {
    rightPanel: null,
    callback: null,
    isListening: false,

    addCloseRightPanelListener: function (callback) {
        if (this.isListening) {
            return;
        }
        this.rightPanel = document.getElementById('acym__scenario__edit__right__panel');
        this.callback = callback;
        this.isListening = true;
        window.addEventListener('click', acym_helperScenarioRightPanel.closePanelOnClickOutside);
    },

    removeCloseRightPanelListener: function () {
        this.isListening = false;
        window.removeEventListener('click', acym_helperScenarioRightPanel.closePanelOnClickOutside);
    },

    closePanelOnClickOutside: function (event) {
        if (!acym_helperScenarioRightPanel.isListening) {
            return;
        }

        if (event.target.closest('.sankey_chart_link')) {
            acym_helperScenarioRightPanel.callback();
            return;
        }

        const allElementToIgnore = [
            '#acym__scenario__edit__right__panel',
            '.sankey_chart_node',
            '.reveal-overlay'
        ];

        const isElementToIgnore = allElementToIgnore.some(selector => event.target.closest(selector));

        if (isElementToIgnore || !document.contains(event.target)) {
            return;
        }

        acym_helperScenarioRightPanel.callback();
    }
};
