const acym_helperSearch = {
    setClearSearch: function () {
        jQuery('.acym__search-clear').off('click').on('click', function () {
            jQuery('.acym__search-field').attr('value', '');
            acym_helperPagination.initPagination();
        });
    }
};
