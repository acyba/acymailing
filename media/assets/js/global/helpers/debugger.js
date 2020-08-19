const acym_helperDebugger = {
    initDebugger: function () {
        function KeyCheck(e) {
            if (e.key === 'F8') {
                debugger;
            }
        }

        window.addEventListener('keydown', KeyCheck, false);
    }
};
