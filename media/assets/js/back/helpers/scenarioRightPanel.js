const acym_helperScenarioRightPanel = {
    rightPanel: null,
    rightPanelCloseButton: null,
    rightPanelCancelButton: null,
    callback: null,
    addCloseRightPanelListener: function (callback) {
        this.rightPanel = document.getElementById('acym__scenario__edit__right__panel');
        this.callback = callback;
        window.addEventListener('click', acym_helperScenarioRightPanel.closePanelOnClickOutside);
    },
    removeCloseRightPanelListener: function () {
        window.removeEventListener('click', acym_helperScenarioRightPanel.closePanelOnClickOutside);
    },
    closePanelOnClickOutside: function (event) {
        const allElementToIgnore = [
            '#acym__scenario__edit__right__panel',
            '#sankey_chart_svg_container path',
            '.reveal-overlay'
        ];

        const isElementToIgnore = allElementToIgnore.some(selector => event.target.closest(selector));

        if (isElementToIgnore || !document.contains(event.target)) {
            return;
        }

        acym_helperScenarioRightPanel.callback();
    }
};
