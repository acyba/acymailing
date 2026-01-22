let isSearching = false;
let lastSearch = '';
let defaultSearch = 'hello';
let limitSearch = 24;
let offsetGif = 0;
let queryGif = 'hello';
let typingTimerGif = '';

const acym_editorWysidGif = {
    giphyKey: '',
    init: function () {
        acym_helper.config_get('giphy_key').done((resConfig) => {
            this.giphyKey = resConfig.data.value;

            this.insertGif();
            this.makeNewResearch('');
        });
    },
    loadGif: function () {
        const $errorMessage = jQuery('#acym__wysid__modal__gif--error_message');
        $errorMessage.html('');
        $errorMessage.hide();

        if (!this.giphyKey) {
            jQuery('.gif_fields').prop('disabled', true);
            $errorMessage.html(`${ACYM_JS_TXT.ACYM_GIPHY_KEY_NEEDED} 
                <div class="margin-top-1">
                    <a
                        class="button margin-auto" 
                        target="_blank" 
                        href="https://docs.acymailing.com/main-pages/the-email-editor/giphy-integration"
                    >
                        ${ACYM_JS_TXT.ACYM_GET_MY_API_KEY}
                    </a>
                </div>`);
            $errorMessage.show();
            return;
        }

        // When we're already searching for gif no need to make more research in the function setResearchInput
        isSearching = true;
        // When it's the same research no need to make the research again
        lastSearch = queryGif;
        // We get the grid where we display the result and then we initiate masonry
        let $grid = jQuery('#acym__wysid__modal__gif--results');
        $grid = $grid.masonry({
            itemSelector: 'img'
        });

        let queryUrl = 'https://api.giphy.com/v1/gifs/search?rating=g';
        queryUrl += '&limit=' + limitSearch;
        queryUrl += '&offset=' + offsetGif;
        queryUrl += '&q=' + queryGif;
        queryUrl += '&api_key=' + this.giphyKey;

        // We make the call on Giphy to get all the GIFs
        jQuery.ajax({
            url: queryUrl,
            dataType: 'json'
        }).then((res) => {
            if (!res.data || !res.data.length) {
                if (offsetGif > 0) {
                    return;
                }

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
            jQuery('.acym__wysid__modal__gif__results--img--new').removeClass('acym__wysid__modal__gif__results--img--new');

            // If the offset is empty, we either reset the search or it's the first call
            if (offsetGif === 0) {
                // We remove all the images that could be in the grid
                $grid.masonry('remove', $grid.find('.acym__wysid__modal__gif__results--img'));
                // We scroll at the top to get the first images
                jQuery('.acym__wysid__modal__gif__results__container').scrollTop(0);
            }

            // We build the content of the grid => create img tab for every result
            let results = '';
            const columnWidth = $grid.width() / 4;
            jQuery.each(res.data, function (index, value) {
                const ratio = parseInt((
                    columnWidth * 100
                ) / parseInt(value.images.fixed_width.width)) / 100;
                const height = parseInt(value.images.fixed_width.height * ratio);

                results += `<img alt="${value.title.replaceAll('"', '&quot;')}" 
                                class="acym__wysid__modal__gif__results--img acym__wysid__modal__gif__results--img--new" 
                                style="height: ${height}px" 
                                src="${value.images.fixed_width.webp}" 
                                data-full-res-src="${value.images.original.webp}" />`;
            });

            const $results = jQuery(results);
            $grid.append($results).masonry('appended', $results);
            $grid.masonry('layout');

            this.loadMoreGif();
            this.setSelectGif();
            this.setResearchInput();

            offsetGif += limitSearch;
        }).fail(() => {
            if ($grid.data('masonry')) {
                $grid.masonry('destroy');
            }
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

            if ((
                    search === '' && lastSearch === defaultSearch
                ) || isSearching || sameResearch) {
                return;
            }

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
        offsetGif = 0;
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
                       + '" style="max-width: 100%; height: auto;  box-sizing: border-box; padding: 0 5px;display:block; margin-left: auto; margin-right: auto; vertical-align: middle;"/>';
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
