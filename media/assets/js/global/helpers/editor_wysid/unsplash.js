let isSearchingUnsplash = false;
let lastSearchUnsplash = '';
let defaultSearchUnsplash = 'journey';
let limitSearchUnsplash = 24;
let pageUnsplash = 1;
let queryUnsplash = '';
let typingTimerUnsplash = '';

const acym_editorWysidUnsplash = {
    unsplashKey: '',
    init: function () {
        acym_helper.config_get('unsplash_key').done((resConfig) => {
            this.unsplashKey = resConfig.data.value;

            this.setSearchInput();
            this.insertImage();
            this.makeNewSearch('');
        });
    },
    makeNewSearch: function (query) {
        clearTimeout(typingTimerUnsplash);
        //If we make a new research we reset the research to 0 like it's the first we load the modal
        pageUnsplash = 1;
        queryUnsplash = query === '' ? defaultSearchUnsplash : query;
        const $grid = jQuery('#acym__wysid__modal__unsplash--results');
        if ($grid.data('masonry')) {
            $grid.masonry('destroy');
        }
        this.loadImages();
    },
    insertImage: function () {
        //Function to insert image
        jQuery('#acym__wysid__modal__unsplash--insert').off('click').on('click', () => {
            const $selectedImg = jQuery('.acym__wysid__modal__unsplash__results--img--selected');
            //if no selected image => out
            if ($selectedImg.length === 0) return false;

            const fullResSrc = $selectedImg.attr('data-full-res-src');
            const photographerName = $selectedImg.attr('data-photographer-name');
            let content = `<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">
                <td class="large-12 acym__wysid__column__element__td">
                    <div class="acym__wysid__tinymce--image">
                        <div style="text-align: center" data-mce-style="text-align: center">
                            <img alt="" 
                                class="acym__wysid__media__inserted acym__wysid__media__inserted--focus acym__wysid__media__unsplash" 
                                src="${fullResSrc}" 
                                style="max-width: 100%; height: auto;  box-sizing: border-box; padding: 0 5px;display:block; margin-left: auto; margin-right: auto; vertical-align: middle;"
                                title="Photo by ${photographerName} on Unsplash"/>
                        </div>
                    </div>
                </td>
            </tr>`;
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
    },
    loadImages: function () {
        const $errorMessage = jQuery('#acym__wysid__modal__unsplash--error_message');

        if (!this.unsplashKey) {
            jQuery('.unsplash_fields').prop('disabled', true);
            $errorMessage.html(`${ACYM_JS_TXT.ACYM_UNSPLASH_KEY_NEEDED} 
                <div class="margin-top-1">
                    <a class="button margin-auto" target="_blank" href="https://docs.acymailing.com/main-pages/the-email-editor/unsplash-integration">
                        ${ACYM_JS_TXT.ACYM_GET_MY_API_KEY}
                    </a>
                </div>`);
            $errorMessage.show();
            return;
        }

        //When we're already searching for images no need to make more research in the function setSearchInput
        isSearchingUnsplash = true;
        //When it's the same research no need to make the research again
        lastSearchUnsplash = queryUnsplash;
        //We get the grid where we display the result and then we initiate masonry
        let $grid = jQuery('#acym__wysid__modal__unsplash--results');
        $grid = $grid.masonry({
            itemSelector: 'img'
        });

        let urlSearch = 'https://api.unsplash.com/search/photos?per_page=' + limitSearchUnsplash;
        urlSearch += '&page=' + pageUnsplash;
        urlSearch += '&query=' + queryUnsplash;
        urlSearch += '&client_id=' + this.unsplashKey;

        const searchOrientation = jQuery('#acym__wysid__modal__unsplash--orientation').val();
        if (searchOrientation !== 'all') {
            urlSearch += '&orientation=' + searchOrientation;
        }

        jQuery.ajax({
            url: urlSearch,
            dataType: 'json'
        }).then((res) => {
            if (res.results.length === 0 && pageUnsplash === 1) {
                //If there is no result
                if ($grid.data('masonry')) {
                    $grid.masonry('destroy');
                }

                $errorMessage.html(ACYM_JS_TXT.ACYM_NO_RESULTS_FOUND);
                $errorMessage.show();
                // We empty the results grid and empty it
                $grid.html('');
                $grid.hide();
            } else {
                $grid.show();
                //We remove the class new to only get the new images
                jQuery('.acym__wysid__modal__unsplash__results--img--new').removeClass('acym__wysid__modal__unsplash__results--img--new');

                //We remove the title no result if there is one
                $errorMessage.html('');
                $errorMessage.hide();

                if (pageUnsplash === 1) {
                    //We scroll at the top to get the first images
                    jQuery('.acym__wysid__modal__unsplash__results__container').scrollTop(0);
                    //We remove all the images that could be in the grid
                    $grid.masonry('remove', $grid.find('.acym__wysid__modal__unsplash__results--img'));
                }

                //We build the content of the grid => create img tab for every result
                let results = '';
                const columnWidth = $grid.width() / 4;
                const widthMatcher = /&w=(\d+)/;
                jQuery.each(res.results, function (index, value) {
                    const originalWidth = value.width;
                    const originalHeight = value.height;

                    let width = value.urls.thumb.match(widthMatcher);
                    if (width !== null) {
                        width = width[1];
                    } else {
                        width = originalWidth;
                    }

                    const ratio = parseInt((columnWidth * 100) / parseInt(width)) / 100;
                    const height = parseInt((originalHeight * width / originalWidth) * ratio);

                    const searchSize = jQuery('#acym__wysid__modal__unsplash--size').val();
                    let insertionUrl = value.urls.full;
                    if ([
                            'regular',
                            'small',
                            'thumb'
                        ].indexOf(searchSize) !== -1 && value.urls[searchSize] !== undefined) {
                        insertionUrl = value.urls[searchSize];
                    }

                    results += `<img alt="" 
                                    class="acym__wysid__modal__unsplash__results--img acym__wysid__modal__unsplash__results--img--new" 
                                    style="height: ${height}px" 
                                    src="${value.urls.thumb}" 
                                    data-full-res-src="${insertionUrl}" 
                                    data-photographer-name="${value.user.name}">`;
                });

                //We append it and init masonry for them
                const $results = jQuery(results);
                $grid.append($results);
                $grid.masonry('appended', $results);
                $grid.masonry('layout');

                //load more on scroll
                this.loadMoreImages();
                //selection for the image (put the border selected)
                this.setSelectImage();
            }
        }).fail((jqXHR, textStatus, errorThrown) => {
            //If the request fail we let know the user
            if ($grid.data('masonry')) $grid.masonry('destroy');
            let $errorMessage = jQuery('#acym__wysid__modal__unsplash--error_message');

            if (jqXHR.responseText === 'Rate Limit Exceeded') {
                $errorMessage.html(ACYM_JS_TXT.ACYM_REACHED_SEARCH_LIMITS);
            } else {
                $errorMessage.html(ACYM_JS_TXT.ACYM_COULD_NOT_LOAD_UNSPLASH);
            }
            $errorMessage.show();
            // We empty the results grid and empty it
            $grid.html('');
            $grid.hide();
            jQuery('#acym__wysid__modal__unsplash--low-res-message').hide();
        }).always(() => {
            isSearchingUnsplash = false;
        });
    },
    setSelectImage: function () {
        //selection for the image (put the border selected)
        jQuery('.acym__wysid__modal__unsplash__results--img').off('click').on('click', function () {
            jQuery('.acym__wysid__modal__unsplash__results--img--selected').removeClass('acym__wysid__modal__unsplash__results--img--selected');
            jQuery(this).toggleClass('acym__wysid__modal__unsplash__results--img--selected');
            jQuery('#acym__wysid__modal__unsplash--insert').removeAttr('disabled');
        });
    },
    loadMoreImages: function () {
        //load more on scroll
        jQuery('.acym__wysid__modal__unsplash__results__container').off('scroll').on('scroll', function () {
            //We subtract 80 this way the call trigger before the user touch the bottom and he have to wait less time
            let scrollToDo = jQuery(this)[0].scrollHeight - 80;
            let scrollDone = jQuery(this).height() + jQuery(this).scrollTop();

            //if we reach the end we load more entities
            if (scrollDone >= scrollToDo) {
                //once it's done we remove the event listener on the scroll to prevent calling X times the urls
                jQuery(this).off('scroll');
                pageUnsplash++;
                acym_editorWysidUnsplash.loadImages();
            }
        });
    },
    setSearchInput: function () {
        //Set the research bar with the button
        jQuery('#acym__wysid__modal__unsplash--search--button').off('click').on('click', () => {
            this.makeNewSearch(jQuery('#acym__wysid__modal__unsplash--search').val());
        });

        jQuery('#acym__wysid__modal__unsplash--size, #acym__wysid__modal__unsplash--orientation').on('change', () => {
            this.makeNewSearch(jQuery('#acym__wysid__modal__unsplash--search').val());
        });

        jQuery('#acym__wysid__modal__unsplash--search').off('keyup').on('keyup', function (e) {
            const search = jQuery(this).val();
            const sameResearch = lastSearchUnsplash === search;

            if ((search === '' && lastSearchUnsplash === defaultSearchUnsplash) || isSearchingUnsplash || sameResearch) {
                return;
            }

            clearTimeout(typingTimerUnsplash);

            if (e.key === 'Enter') {
                acym_editorWysidUnsplash.makeNewSearch(search);
            } else if (search.length >= 2) {
                typingTimerUnsplash = setTimeout(function () {
                    acym_editorWysidUnsplash.makeNewSearch(search);
                }, 3000);
            } else if (search === '') {
                acym_editorWysidUnsplash.makeNewSearch(search);
            }
        });
    }
};
