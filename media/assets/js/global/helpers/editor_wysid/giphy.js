let isSearching = false;
let lastSearch = '';
let defaultSearch = 'hello';
let limitSearch = 24;

const acym_editorWysidGiphy = {
    loadGif: function () {
        //When we're already searching for gif no need to make more research in the function setResearchInput
        isSearching = true;
        //When it's the same research no need to make the research again
        lastSearch = acym_helperEditorWysid.queryGiphy;
        //We get the grid where we display the result and then we initiate masonry
        let $grid = jQuery('#acym__wysid__modal__giphy--results');
        $grid = $grid.masonry({
            itemSelector: 'img'
        });
        //We make the call on giphy to get all the gif
        jQuery.ajax({
            url: 'https://api.giphy.com/v1/gifs/search?limit='
                 + limitSearch
                 + '&offset='
                 + acym_helperEditorWysid.offsetGiphy
                 + '&q='
                 + acym_helperEditorWysid.queryGiphy
                 + '&api_key=6hR2IN2Db2aw4XdtNxLELKtOh66F5XSo&rating=PG',
            dataType: 'json'
        }).then((res) => {
            if (res.data.length === 0 && acym_helperEditorWysid.offsetGiphy === 0) {
                //If there is no result
                if ($grid.data('masonry')) $grid.masonry('destroy');

                let $errorMessage = jQuery('#acym__wysid__modal__giphy--error_message');
                $errorMessage.html(ACYM_JS_TXT.ACYM_NO_RESULTS_FOUND);
                $errorMessage.show();
                // We empty the results grid and empty it
                $grid.html('');
                $grid.hide();
                jQuery('#acym__wysid__modal__giphy--low-res-message').hide();
            } else {
                $grid.show();
                //We remove the class new to only get the new images
                jQuery('.acym__wysid__modal__giphy__results--img--new').removeClass('acym__wysid__modal__giphy__results--img--new');

                //We remove the title no result if there is one
                let $errorMessage = jQuery('#acym__wysid__modal__giphy--error_message');
                $errorMessage.html('');
                $errorMessage.hide();
                jQuery('#acym__wysid__modal__giphy--low-res-message').show();

                //if the offset is at 0 it means we reset the research or it's the first call
                if (acym_helperEditorWysid.offsetGiphy === 0) {
                    //We remove all the images that could be in the grid
                    $grid.masonry('remove', $grid.find('.acym__wysid__modal__giphy__results--img'));
                    //We scroll at the top to get the first images
                    jQuery('.acym__wysid__modal__giphy__results__container').scrollTop(0);
                }

                //We build the content of the grid => create img tab for every result
                let results = '';
                let columnWidth = $grid.width() / 4;
                jQuery.each(res.data, function (index, value) {
                    let ratio = parseInt((columnWidth * 100) / parseInt(value.images.preview_gif.width)) / 100;
                    let height = parseInt(value.images.preview_gif.height * ratio);
                    results += `<img class="acym__wysid__modal__giphy__results--img acym__wysid__modal__giphy__results--img--new" style="height: ${height}px" src="${value.images.preview_gif.url}" data-full-res-src="${value.images.downsized.url}">`;
                });

                //We append it and init masonry for them
                let $results = jQuery(results);
                $grid.append($results).masonry('appended', $results);

                $grid.masonry('layout');

                //load more on scroll
                this.loadMoreGif();
                //selection for the gif (put the border selected)
                this.setSelectGif();
                //Set the research bar with the button
                this.setResearchInput();
            }
        }).fail(() => {
            //If the request fail we let know the user
            if ($grid.data('masonry')) $grid.masonry('destroy');
            let $errorMessage = jQuery('#acym__wysid__modal__giphy--error_message');
            $errorMessage.html(ACYM_JS_TXT.ACYM_COULD_NOT_LOAD_GIF_TRY_FEW_MINUTES);
            $errorMessage.show();
            // We empty the results grid and empty it
            $grid.html('');
            $grid.hide();
            jQuery('#acym__wysid__modal__giphy--low-res-message').hide();
        }).always(() => {
            isSearching = false;
        });
    },
    setSelectGif: function () {
        //selection for the gif (put the border selected)
        jQuery('.acym__wysid__modal__giphy__results--img').off('click').on('click', function () {
            jQuery('.acym__wysid__modal__giphy__results--img--selected').removeClass('acym__wysid__modal__giphy__results--img--selected');
            jQuery(this).toggleClass('acym__wysid__modal__giphy__results--img--selected');
            jQuery('#acym__wysid__modal__giphy--insert').removeAttr('disabled');
        });
    },
    loadMoreGif: function () {
        //load more on scroll
        jQuery('.acym__wysid__modal__giphy__results__container').on('scroll', function () {
            //We subtract 80 this way the call trigger before the user touch the bottom and he have to wait less time
            let scrollToDo = jQuery(this)[0].scrollHeight - 80;
            let scrollDone = jQuery(this).height() + jQuery(this).scrollTop();

            //if we reach the end we load more entities
            if (scrollDone >= scrollToDo) {
                //once it's done we remove the event listener on the scroll to prevent calling X times the urls
                jQuery(this).off('scroll');
                acym_helperEditorWysid.offsetGiphy += limitSearch;
                acym_editorWysidGiphy.loadGif();
            }
        });
    },
    setResearchInput: function () {
        //Set the research bar with the button
        jQuery('#acym__wysid__modal__giphy--search--button').off('click').on('click', () => {
            this.makeNewResearch(jQuery('#acym__wysid__modal__giphy--search').val());
        });

        jQuery('#acym__wysid__modal__giphy--search').off('keyup').on('keyup', function (e) {
            let search = jQuery(this).val();
            let sameResearch = lastSearch === search;

            if ((search === '' && lastSearch === defaultSearch) || isSearching || sameResearch) return;

            clearTimeout(acym_helperEditorWysid.typingTimerGiphy);

            if (e.which === 13) {
                acym_editorWysidGiphy.makeNewResearch(search);
            } else if (search.length >= 2) {
                acym_helperEditorWysid.typingTimerGiphy = setTimeout(function () {
                    acym_editorWysidGiphy.makeNewResearch(search);
                }, 1000);
            } else if (search === '') {
                acym_editorWysidGiphy.makeNewResearch(search);
            }
        });
    },
    makeNewResearch: function (query) {
        //If we make a new research we reset the research to 0 like it's the first we load the modal
        acym_helperEditorWysid.offsetGiphy = 0;
        acym_helperEditorWysid.queryGiphy = query === '' ? defaultSearch : query;
        let $grid = jQuery('#acym__wysid__modal__giphy--results');
        if ($grid.data('masonry')) $grid.masonry('destroy');
        this.loadGif();
    },
    insertGif: function () {
        //Function to insert gif
        jQuery('#acym__wysid__modal__giphy--insert').off('click').on('click', () => {
            const $selectedImg = jQuery('.acym__wysid__modal__giphy__results--img--selected');
            //if no selected image => out
            if ($selectedImg.length === 0) return false;
            let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
            content += '<td class="large-12 acym__wysid__column__element__td">';
            content += '<div class="acym__wysid__tinymce--image">';
            content += '<img alt="" class="acym__wysid__media__inserted acym__wysid__media__inserted--focus acym__wysid__media__giphy" src="'
                       + $selectedImg.attr('data-full-res-src')
                       + '" style="max-width: 100%; height: auto;  box-sizing: border-box; padding: 0 5px;display:block; margin-left: auto; margin-right: auto;"/>';
            content += '</div>';
            content += '</td>';
            content += '</tr>';
            acym_helperEditorWysid.$focusElement.replaceWith(content);

            //Display none on the modal to hide it
            jQuery('#acym__wysid__modal').css('display', 'none');

            jQuery('.acym__wysid__media__inserted--focus').on('load', function () {
                jQuery(this).removeClass('acym__wysid__media__inserted--focus');
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
                acym_editorWysidVersioning.setUndoAndAutoSave();
            });
        });
    }
};
