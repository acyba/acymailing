const acym_elementor = {
    init: function () {
        elementor.hooks.addAction('panel/open_editor/widget/wp-widget-acym_subscriptionform_widget', (panel, model, view) => {
            this.waitForElementToInit();
        });
        elementor.hooks.addAction('panel/open_editor/widget/wp-widget-acym_archive_widget', (panel, model, view) => {
            this.waitForElementToInit();
        });
    },
    waitForElementToInit: function () {
        if (jQuery('.acym_simple_select2').length < 1) {
            setTimeout(() => {
                this.waitForElementToInit();
            }, 500);
        } else {
            acym_widget.init();
        }
    },
    waitForElementor: function () {
        if (typeof elementor === 'undefined') {
            setTimeout(() => {
                this.waitForElementor();
            }, 500);
        } else {
            this.init();
        }
    },
};

acym_elementor.waitForElementor();
