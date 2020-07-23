const acym_helperImportBack = {
    initImportBack: function () {
        acym_helperImport.initImport();

        //Pour ne pas valider le formulaire d'import au entrée, sinon aucune fonction n'est renseignée.
        jQuery('#acym__users__import__from_database').keydown(function (event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
    },
};
