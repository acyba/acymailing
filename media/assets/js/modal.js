document.addEventListener('DOMContentLoaded', function () {
    let nlLinks = document.getElementsByClassName('acym__modal__handle');
    for (let i = 0 ; i < nlLinks.length ; i++) {
        nlLinks[i].addEventListener('click', function (event) {
            event.preventDefault();
            let modalId = event.target.getAttribute('data-acym-modal');
            let modal = document.getElementById('acym__modal__' + modalId);
            modal.style.display = 'block';
        });
    }

    let modalCloseButtons = document.querySelectorAll('.acym__modal__close span');
    for (let i = 0 ; i < modalCloseButtons.length ; i++) {
        modalCloseButtons[i].onclick = function (event) {
            let modal = event.target.closest('.acym__modal');
            modal.style.display = 'none';
        };
    }

    let modals = document.getElementsByClassName('acym__modal');
    for (let i = 0 ; i < modals.length ; i++) {
        modals[i].onclick = function (event) {
            let target = event.target;
            let modalContent = target.closest('.acym__modal__content');

            if (null === modalContent) {
                let modal = target.closest('.acym__modal');
                modal.style.display = 'none';
            }
        };
    }
});
