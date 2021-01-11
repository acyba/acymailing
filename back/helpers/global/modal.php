<?php

/**
 * @param string  $button The Way to open the modal (Button, link ...). Add the attribute data-open (should be the ID of the modal) to any element.
 * @param string  $data   Modal's content
 * @param null    $id     The ID of the modal
 * @param string  $attributesModal
 * @param string  $attributesButton
 * @param boolean $isButton
 * @param boolean $isLarge
 *
 * @return string The modal
 */
function acym_modal($button, $data, $id = null, $attributesModal = '', $attributesButton = '', $isButton = true, $isLarge = true)
{
    if (empty($id)) {
        $id = 'acymodal_'.rand(1000, 9000);
    }

    $modal = $isButton ? '<button type="button" data-open="'.$id.'" '.$attributesButton.'>'.$button.'</button>' : $button;
    $modal .= '<div class="reveal" '.($isLarge ? 'data-reveal-larger' : '').' id="'.$id.'" '.$attributesModal.' data-reveal>';
    $modal .= $data;
    $modal .= '<button class="close-button" data-close aria-label="Close reveal" type="button">';
    $modal .= '<span aria-hidden="true">&times;</span>';
    $modal .= '</button ></div>';

    return $modal;
}

/**
 * Deprecated see acym_modalInclude
 */
function acym_modal_include($button, $file, $id, $data, $attributes = '', $classModal = '', $containerAttributes = '')
{
    return acym_modalInclude($button, $file, $id, $data, $attributes = '', $classModal = '', $containerAttributes = '');
}

/**
 * @param string $button
 * @param string $file
 * @param string $id
 * @param array  $data Is used in the included file, don't remove
 * @param string $attributes
 * @param string $classModal
 * @param string $containerAttributes
 *
 * @return string
 */
function acym_modalInclude($button, $file, $id, $data, $attributes = '', $classModal = '', $containerAttributes = '')
{
    if (empty($id)) {
        $id = 'acymodal_'.rand(1000, 9000);
    }

    $modal = '<div data-open="'.acym_escape($id).'" '.$containerAttributes.'>'.$button;
    $modal .= '<div class="reveal '.$classModal.'" id="'.acym_escape($id).'" '.$attributes.' data-reveal>';
    ob_start();
    include $file;
    $modal .= ob_get_clean();
    $modal .= '<button type="button" class="close-button" data-close aria-label="Close reveal">';
    $modal .= '<span aria-hidden="true">&times;</span>';
    $modal .= '</button></div></div>';

    return $modal;
}

/**
 * Deprecated see acym_modalPaginationLists
 */
function acym_modal_pagination_lists($button, $class, $textButton = null, $id = null, $attributes = '', $inputEventId = "", $checkedLists = "[]", $needDisplaySubscribers = false, $attributesModal = '')
{
    return acym_modalPaginationLists(
        $button,
        $class,
        $textButton = null,
        $id = null,
        $attributes = '',
        $inputEventId = "",
        $checkedLists = "[]",
        $needDisplaySubscribers = false,
        $attributesModal = ''
    );
}

/**
 * @param string  $button     The Way to open the modal (Button, link ...). Add the attribute data-open (should be the ID of the modal) to any element.
 * @param         $class
 * @param         $textButton
 * @param null    $id         Id of modal
 * @param string  $attributes All attributes of the modal
 * @param boolean $isModal
 * @param string  $inputEventId
 * @param string  $checkedLists
 * @param boolean $needDisplaySubscribers
 *
 * @return string The modal
 */
function acym_modalPaginationLists($button, $class, $textButton = null, $id = null, $attributes = '', $inputEventId = "", $checkedLists = "[]", $needDisplaySubscribers = false, $attributesModal = '')
{
    $searchField = acym_filterSearch('', 'modal_search_lists', 'ACYM_SEARCH');

    $data = "";

    if (!empty($inputEventId)) {
        $data .= '<input type="hidden" id="'.$inputEventId.'">';
    }
    if ($needDisplaySubscribers) {
        $data .= '<input type="hidden" id="modal__pagination__need__display__sub">';
    }

    $data .= '<div class="cell grid-x" '.$attributesModal.'>
            <input type="hidden" name="show_selected" value="false" id="modal__pagination__show-information">
            <input type="hidden" id="modal__pagination__search__lists">
            <input type="hidden" name="lists_selected" id="acym__modal__lists-selected" value="'.acym_escape($checkedLists).'">
            <div class="cell grid-x">
                <h4 class="cell text-center acym__modal__pagination__title">'.acym_translation('ACYM_CHOOSE_LISTS').'</h4>
            </div>
            <div class="cell grid-x modal__pagination__search">
                '.$searchField.'
            </div>
            <div class="cell text-center" id="modal__pagination__search__spinner" style="display: none">
                <i class="acymicon-circle-o-notch acymicon-spin"></i>
            </div>
            <div class="cell medium-6 modal__pagination__show">
                <a href="#" class="acym__color__blue modal__pagination__show-selected modal__pagination__show-button selected">'.acym_translation('ACYM_SHOW_SELECTED_LISTS').'</a>
                <a href="#" class="acym__color__blue modal__pagination__show-all modal__pagination__show-button">'.acym_translation('ACYM_SHOW_ALL_LISTS').'</a>
            </div>
            <div class="cell grid-x modal__pagination__listing__lists">
                <div class="cell modal__pagination__listing__lists__in-form"></div>
            </div>
            </div>';

    return $data;
}
