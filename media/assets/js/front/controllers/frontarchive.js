function acym_changePageFront(page) {
    let acymForm = document.querySelector('.acym__archive__form');
    let nextPage = document.getElementById('acym__front__archive__next-page');

    nextPage.value = page;

    acymForm.submit();
}
