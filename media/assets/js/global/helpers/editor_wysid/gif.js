let isSearching = false;
let lastSearch = '';
let defaultSearch = 'hello';
let limitSearch = 24;
let offsetGif = '';
let queryGif = 'hello';
let typingTimerGif = '';

const acym_editorWysidGif = {
    tenorKey: '',
    init: function () {
        acym_helper.config_get('tenor_key').done((resConfig) => {
            this.tenorKey = resConfig.data.value;

            this.insertGif();
            this.makeNewResearch('');
        });
    },
    loadGif: function () {
        if (!this.tenorKey) {
            jQuery('.gif_fields').prop('disabled', true);
            const $errorMessageZone = jQuery('#acym__wysid__modal__gif--error_message');
            $errorMessageZone.html(ACYM_JS_TXT.ACYM_TENOR_KEY_NEEDED
                                   + ' <a class="acym__color__blue" target="_blank" href="https://docs.acymailing.com/main-pages/the-email-editor/tenor-integration">'
                                   + ACYM_JS_TXT.ACYM_GET_ONE_HERE
                                   + '</a> ');
            $errorMessageZone.show();
            return;
        }

        //When we're already searching for gif no need to make more research in the function setResearchInput
        isSearching = true;
        //When it's the same research no need to make the research again
        lastSearch = queryGif;
        //We get the grid where we display the result and then we initiate masonry
        let $grid = jQuery('#acym__wysid__modal__gif--results');
        $grid = $grid.masonry({
            itemSelector: 'img'
        });

        let queryUrl = 'https://tenor.googleapis.com/v2/search?contentfilter=medium&client_key=acymailing&media_filter=gif,tinygif';
        queryUrl += '&limit=' + limitSearch;
        queryUrl += '&q=' + queryGif;
        queryUrl += '&key=' + this.tenorKey;

        if (offsetGif !== '') {
            queryUrl += '&pos=' + offsetGif;
        }

        // We make the call on Tenor to get all the GIFs
        jQuery.ajax({
            url: queryUrl,
            dataType: 'json'
        }).then((res) => {
            const $errorMessage = jQuery('#acym__wysid__modal__gif--error_message');

            if (res.results.length === 0 && offsetGif === '') {
                // There is no result
                if ($grid.data('masonry')) {
                    $grid.masonry('destroy');
                }

                $errorMessage.html(ACYM_JS_TXT.ACYM_NO_RESULTS_FOUND);
                $errorMessage.show();
                // We empty the results grid and empty it
                $grid.html('');
                $grid.hide();

                return;
            }

            $grid.show();
            //We remove the class new to only get the new images
            jQuery('.acym__wysid__modal__gif__results--img--new').removeClass('acym__wysid__modal__gif__results--img--new');

            //We remove the title no result if there is one
            $errorMessage.html('');
            $errorMessage.hide();

            //if the offset is not set it means we reset the research or it's the first call
            if (offsetGif === '') {
                //We remove all the images that could be in the grid
                $grid.masonry('remove', $grid.find('.acym__wysid__modal__gif__results--img'));
                //We scroll at the top to get the first images
                jQuery('.acym__wysid__modal__gif__results__container').scrollTop(0);
            }

            //We build the content of the grid => create img tab for every result
            let results = '';
            let columnWidth = $grid.width() / 4;
            offsetGif = res.next;
            jQuery.each(res.results, function (index, value) {
                let ratio = parseInt((columnWidth * 100) / parseInt(value.media_formats.tinygif.dims[0])) / 100;
                let height = parseInt(value.media_formats.tinygif.dims[1] * ratio);
                results += `<img alt="" 
                                class="acym__wysid__modal__gif__results--img acym__wysid__modal__gif__results--img--new" 
                                style="height: ${height}px" 
                                src="${value.media_formats.tinygif.url}" 
                                data-full-res-src="${value.media_formats.gif.url}">`;
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
        }).fail(() => {
            //If the request fail we let know the user
            if ($grid.data('masonry')) {
                $grid.masonry('destroy');
            }
            let $errorMessage = jQuery('#acym__wysid__modal__gif--error_message');
            $errorMessage.html(ACYM_JS_TXT.ACYM_COULD_NOT_LOAD_GIF_TRY_AGAIN);
            $errorMessage.show();
            // We empty the results grid and empty it
            $grid.html('');
            $grid.hide();
        }).always(() => {
            isSearching = false;
        });
    },
    setSelectGif: function () {
        //selection for the gif (put the border selected)
        jQuery('.acym__wysid__modal__gif__results--img').off('click').on('click', function () {
            jQuery('.acym__wysid__modal__gif__results--img--selected').removeClass('acym__wysid__modal__gif__results--img--selected');
            jQuery(this).toggleClass('acym__wysid__modal__gif__results--img--selected');
            jQuery('#acym__wysid__modal__gif--insert').removeAttr('disabled');
        });
    },
    loadMoreGif: function () {
        //load more on scroll
        jQuery('.acym__wysid__modal__gif__results__container').on('scroll', function () {
            //We subtract 80 this way the call trigger before the user touch the bottom and he have to wait less time
            let scrollToDo = jQuery(this)[0].scrollHeight - 80;
            let scrollDone = jQuery(this).height() + jQuery(this).scrollTop();

            //if we reach the end we load more entities
            if (scrollDone >= scrollToDo) {
                //once it's done we remove the event listener on the scroll to prevent calling X times the urls
                jQuery(this).off('scroll');
                acym_editorWysidGif.loadGif();
            }
        });
    },
    setResearchInput: function () {
        //Set the research bar with the button
        jQuery('#acym__wysid__modal__gif--search--button').off('click').on('click', () => {
            this.makeNewResearch(jQuery('#acym__wysid__modal__gif--search').val());
        });

        jQuery('#acym__wysid__modal__gif--search').off('keyup').on('keyup', function (e) {
            let search = jQuery(this).val();
            let sameResearch = lastSearch === search;

            if ((search === '' && lastSearch === defaultSearch) || isSearching || sameResearch) return;

            clearTimeout(typingTimerGif);

            if (e.key === 'Enter') {
                acym_editorWysidGif.makeNewResearch(search);
            } else if (search.length >= 2) {
                typingTimerGif = setTimeout(function () {
                    acym_editorWysidGif.makeNewResearch(search);
                }, 1000);
            } else if (search === '') {
                acym_editorWysidGif.makeNewResearch(search);
            }
        });
    },
    makeNewResearch: function (query) {
        clearTimeout(typingTimerGif);
        //If we make a new research we reset the research to 0 like it's the first we load the modal
        offsetGif = '';
        queryGif = query === '' ? defaultSearch : query;
        const $grid = jQuery('#acym__wysid__modal__gif--results');
        if ($grid.data('masonry')) {
            $grid.masonry('destroy');
        }
        this.loadGif();
    },
    insertGif: function () {
        //Function to insert gif
        jQuery('#acym__wysid__modal__gif--insert').off('click').on('click', () => {
            const $selectedImg = jQuery('.acym__wysid__modal__gif__results--img--selected');
            //if no selected image => out
            if ($selectedImg.length === 0) return false;

            let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
            content += '<td class="large-12 acym__wysid__column__element__td">';
            content += '<div class="acym__wysid__tinymce--image">';
            content += '<div style="text-align: center" data-mce-style="text-align: center">';
            content += '<img alt="" class="acym__wysid__media__inserted acym__wysid__media__inserted--focus acym__wysid__media__gif" src="'
                       + $selectedImg.attr('data-full-res-src')
                       + '" style="max-width: 100%; height: auto;  box-sizing: border-box; padding: 0 5px;display:block; margin-left: auto; margin-right: auto;"/>';
            content += '</div>';
            content += '</div>';
            content += '</td>';
            content += '</tr>';
            acym_helperEditorWysid.$focusElement.replaceWith(content);

            //Display none on the modal to hide it
            jQuery('#acym__wysid__modal').css('display', 'none');

            jQuery('.acym__wysid__media__inserted--focus').on('load', function () {
                jQuery(this).removeClass('acym__wysid__media__inserted--focus');
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
                acym_editorWysidRowSelector.setZoneAndBlockOverlays();
                acym_editorWysidImage.setImageWidthHeightOnInsert();
                acym_editorWysidTinymce.addTinyMceWYSID();
            });
        });
    }
};
