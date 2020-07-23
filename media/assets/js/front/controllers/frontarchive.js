document.addEventListener('DOMContentLoaded', function () {
    let nlLinks = document.getElementsByClassName('acym__front__archive__newsletter_name');
    for (let i = 0 ; i < nlLinks.length ; i++) {
        nlLinks[i].addEventListener('click', function (event) {
            let nlid = event.target.getAttribute('data-nlid');
            let modal = document.getElementById('acym__front__archive__modal__' + nlid);

            acymShowModal(modal);
        });
    }

    let modalCloseButtons = document.querySelectorAll('.acym__front__archive__modal__close span');
    for (let i = 0 ; i < modalCloseButtons.length ; i++) {
        modalCloseButtons[i].onclick = function (event) {
            let modal = event.target.closest('.acym__front__archive__modal');

            acymHideModal(modal);
        };
    }

    let modals = document.getElementsByClassName('acym__front__archive__modal');
    for (let i = 0 ; i < modals.length ; i++) {
        modals[i].onclick = function (event) {
            let target = event.target;
            let modalContent = target.closest('.acym__front__archive__modal__content');

            if (null === modalContent) {
                let modal = target.closest('.acym__front__archive__modal');

                acymHideModal(modal);
            }
        };
    }

    function acymShowModal(modal) {
        modal.style.display = 'block';
    }

    function acymHideModal(modal) {
        modal.style.display = 'none';
    }
});


function acym_changePageFront(page) {
    let acymForm = document.getElementById('acym_form');
    let nextPage = document.getElementById('acym__front__archive__next-page');

    nextPage.value = page;

    acymForm.submit();
}


