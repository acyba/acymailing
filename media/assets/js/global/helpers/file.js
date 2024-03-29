const acym_helperFile = {
    initFile: function () {
        acym_helperFile.setAttachment();
        acym_helperFile.setDisplayFileTree();
        acym_helperFile.setSwitchView();
        acym_helperFile.setChangeFolder();
    },
    setAttachment: function () {
        // Add selected file to hidden input
        jQuery('.acym__file__select__add').off('click').on('click', function (evt) {
            let mapId = jQuery('#acym__file__select__mapid').val();
            let mapData = evt.currentTarget.getAttribute('mapdata');
            let currentPath = jQuery('#currentPath').val();

            jQuery('#' + mapId, window.parent.document).val(currentPath + '/' + mapData);
            jQuery('#' + mapId + 'suppr', window.parent.document).css('display', '');
            jQuery('#' + mapId + 'selection', window.parent.document).html(mapData);

            jQuery('#acym__campaign__email__' + mapId, window.parent.document).closest('.reveal-overlay').trigger('click');
        });

        jQuery('.acym__file__select__add.acym_clickme').trigger('click');
    },
    setSwitchView: function () {
        jQuery('#acym__file__select__area__switch button').off('click').on('click', function () {
            jQuery('#acym__file__select__area__switch button').toggleClass('is-hidden');
            jQuery('#acym__file__select__area__grid').toggleClass('is-hidden');
            jQuery('#acym__file__select__area__list').toggleClass('is-hidden');
        });
    },
    setDisplayFileTree: function () {
        // display folders tree view
        jQuery('#displaytree').on('click', function () {
            let $treefile = jQuery('#treefile');
            if ($treefile.css('display') === 'none') {
                $treefile.css('display', 'block');
            } else {
                $treefile.css('display', 'none');
            }
        });
    },
    setChangeFolder: function () {
        // Chamge the current folder to relad the content with the right files
        jQuery('.tree-child-item').off('click').on('click', function (evt) {
            evt.stopPropagation();
            evt.preventDefault();

            let path = evt.currentTarget.getAttribute('data-path');

            let url = window.location.href;
            let lastParam = url.substring(url.lastIndexOf('&') + 1);
            lastParam = lastParam.split('=');
            if (lastParam === 'selected_folder') {
                url = url.replace(lastParam, 'selected_folder=' + path);
            } else {
                url += '&currentFolder=' + path;
            }
            window.location.href = url;
        });
    }
};
