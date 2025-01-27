const acym_helperWorkflow = {
    init: function () {
        this.setClick();
        this.setHover();
    },
    setClick: function () {
        const workflowButton = document.getElementsByClassName('acym__workflow__step');
        if (!workflowButton) {
            return;
        }

        for (let i = 0 ; i < workflowButton.length ; i++) {
            const test = workflowButton[i];
            if (workflowButton[i].classList.contains('acym__workflow__step__disabled')) {
                continue;
            }

            workflowButton[i].addEventListener('click', function () {
                const link = workflowButton[i].querySelector('a');

                if (!link) {
                    return;
                }

                link.click();
            });
        }
    },
    setHover: function () {
        const workflowButton = document.getElementsByClassName('acym__workflow__step');

        if (!workflowButton) {
            return;
        }

        for (let i = 0 ; i < workflowButton.length ; i++) {
            if (workflowButton[i].classList.contains('acym__workflow__step__disabled')) {
                continue;
            }

            workflowButton[i].addEventListener('mouseover', () => {
                const buttonBefore = i - 1 >= 0 ? workflowButton[i - 1] : null;
                const currentButton = workflowButton[i];
                const buttonAfter = i + 1 <= workflowButton.length ? workflowButton[i + 1] : null;

                if (buttonBefore) {
                    buttonBefore.classList.add('acym__workflow__step__hover__right');
                    currentButton.classList.add('acym__workflow__step__hover__left');
                }

                if (buttonAfter) {
                    buttonAfter.classList.add('acym__workflow__step__hover__left');
                    currentButton.classList.add('acym__workflow__step__hover__right');
                }

                workflowButton[i].addEventListener('mouseout', () => {
                    currentButton.classList.remove('acym__workflow__step__hover__right');
                    currentButton.classList.remove('acym__workflow__step__hover__left');

                    if (buttonBefore) {
                        buttonBefore.classList.remove('acym__workflow__step__hover__right');
                    }

                    if (buttonAfter) {
                        buttonAfter.classList.remove('acym__workflow__step__hover__left');
                    }
                });
            });
        }
    }
};
