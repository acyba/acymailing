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
function acym_modal($button, $data, $id = null, $attributesModal = '', $attributesButton = '', $isButton = true, $isLarge = true, $classesModal = '')
{
    if (empty($id)) {
        $id = 'acymodal_'.rand(1000, 9000);
    }

    $buttonParams = '';
    if (is_array($attributesButton)) {
        foreach ($attributesButton as $oneAttribute => $oneValue) {
            $buttonParams .= ' '.$oneAttribute.'="'.acym_escape($oneValue).'"';
        }
    } else {
        $buttonParams = $attributesButton;
    }

    $modal = $isButton ? '<button type="button" data-open="'.$id.'" '.$buttonParams.'>'.$button.'</button>' : $button;
    $modal .= '<div class="reveal '.$classesModal.'" '.($isLarge ? 'data-reveal-larger' : '').' id="'.$id.'" '.$attributesModal.' data-reveal>';
    $modal .= $data;
    $modal .= '<button class="close-button" data-close aria-label="Close reveal" type="button">';
    $modal .= '<span aria-hidden="true">&times;</span>';
    $modal .= '</button></div>';

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

    $dataModal = $data;

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
 * @param string  $inputEventId
 * @param string  $checkedLists
 * @param boolean $needDisplaySubscribers
 * @param string  $attributesModal
 *
 * @return string The modal
 */
function acym_modalPaginationLists($inputEventId = '', $checkedLists = '[]', $needDisplaySubscribers = false, $attributesModal = '')
{
    $searchField = acym_filterSearch('', 'modal_search_lists');

    $data = '';
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
                <h4 class="cell text-center acym__title acym__title__secondary">'.acym_translation('ACYM_CHOOSE_LISTS').'</h4>
            </div>
            <div class="cell grid-x modal__pagination__search">
                '.$searchField.'
            </div>
            <div class="cell text-center padding-top-1" id="modal__pagination__search__spinner" style="display: none">
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

function acym_frontModal($iframeSrc, $buttonText, $isButton, $identifier = null, $iframeClass = null, $additionalContent = '')
{
    static $loaded = false;
    if (empty($loaded)) {
        $loaded = true;
        acym_addStyle(false, ACYM_CSS.'modal.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'modal.min.css'));
        acym_addScript(false, ACYM_JS.'modal.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'modal.min.js'));
    }

    if (empty($identifier)) {
        $identifier = 'identifier_'.rand(1000, 9000);
    }

    if (empty($iframeClass)) {
        $iframeClass = 'acym__modal__iframe';
    }

    ob_start();
    ?>
	<a class="<?php echo $isButton ? 'btn ' : ''; ?>acym__modal__handle" data-acym-modal="<?php echo acym_escape($identifier); ?>" href="#">
        <?php echo acym_translation($buttonText); ?>
	</a>
	<div class="acym__modal" id="acym__modal__<?php echo $identifier; ?>" style="display: none;">
		<div class="acym__modal__content">
			<div class="acym__modal__close"><span>&times;</span></div>
            <?php echo $additionalContent; ?>
			<iframe class="<?php echo $iframeClass; ?>" src="<?php echo $iframeSrc; ?>"></iframe>
		</div>
	</div>
    <?php

    return ob_get_clean();
}
