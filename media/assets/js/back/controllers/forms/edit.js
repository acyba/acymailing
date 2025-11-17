jQuery(function ($) {

    function waitForElement(selector) {
        return new Promise(resolve => {
            if (document.querySelector(selector)) {
                return resolve(document.querySelector(selector));
            }

            const observer = new MutationObserver(() => {
                if (document.querySelector(selector)) {
                    resolve(document.querySelector(selector));
                    observer.disconnect();
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }

    async function Init() {
        await waitForElement('[name="form[settings][termspolicy][terms_type]"]');
        await waitForElement('[name="form[settings][termspolicy][privacy_type]"]');

        handleConditionalSelects('form[settings][termspolicy][terms_type]', {
            article: 'form[settings][termspolicy][termscond]',
            url: 'form[settings][termspolicy][terms_url]'
        });

        handleConditionalSelects('form[settings][termspolicy][privacy_type]', {
            article: 'form[settings][termspolicy][privacy]',
            url: 'form[settings][termspolicy][privacy_url]'
        });
    }


    function handleDisplayActionPopup() {
        const $selectDisplayAction = $('[name="form[settings][display][display_action]"]');
        const inputButtonId = document.querySelector('input[name="form[settings][display][button]"]').closest('.acym__forms__menu__options');
        const inputDelay = document.querySelector('input[name="form[settings][display][delay]"]').closest('.acym__forms__menu__options');
        const inputScroll = document.querySelector('input[name="form[settings][display][scroll]"]').closest('.acym__forms__menu__options');

        if (!$selectDisplayAction[0]) {
            return;
        }

        switchDisplayActionParams($selectDisplayAction[0], inputButtonId, inputDelay, inputScroll);

        $selectDisplayAction.on('change', () => {
            switchDisplayActionParams($selectDisplayAction[0], inputButtonId, inputDelay, inputScroll);
        });
    }

    function switchDisplayActionParams(selectDisplayAction, inputButtonId, inputDelay, inputScroll) {
        if (selectDisplayAction.value === 'yes') {
            inputButtonId.style.display = 'flex';
            inputDelay.style.display = 'none';
            inputScroll.style.display = 'none';
        } else {
            inputButtonId.style.display = 'none';
            inputDelay.style.display = 'flex';
            inputScroll.style.display = 'flex';
        }
    }

    function handleConditionalSelects(selectName, conditionalMap) {
        const $select = $(`[name="${selectName}"]`);

        if (!$select.length) return;

        function toggleFields() {
            const selected = $select.val();

            Object.values(conditionalMap).forEach(name => {
                const wrapper = $(`[name="${name}"]`).closest('.acym__forms__menu__options');
                wrapper.hide();
            });

            if (conditionalMap[selected]) {
                $(`[name="${conditionalMap[selected]}"]`).closest('.acym__forms__menu__options').show();
            }
        }

        toggleFields();
        $select.on('change', toggleFields);
    }

    Init();
});
