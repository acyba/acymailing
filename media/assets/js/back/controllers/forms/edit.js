jQuery(function ($) {

    function Init() {
        window.addEventListener('select2-form[settings][display][display_action]', handleDisplayActionPopup);
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

    Init();
});
