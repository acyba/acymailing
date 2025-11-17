<?php

/**
 * Add the attribute data-open (should be the ID of the modal) to any element to make it open the modal.
 */
function acym_modal(
    string  $button,
    string  $data,
    ?string $id = null,
    array   $attributesModal = [],
    array   $attributesButton = [],
    bool    $isButton = true,
    bool    $isLarge = true,
    string  $classesModal = ''
): string {
    if (empty($id)) {
        $id = 'acymodal_'.rand(1000, 9000);
    }

    //TODO test with data-iframe param since it's a partial URL
    $attributesButton['data-open'] = $id;
    $buttonParams = acym_getFormattedAttributes($attributesButton);

    $attributesModal['class'] = 'reveal '.$classesModal;
    $attributesModal['id'] = $id;
    $attributesModal['data-reveal'] = '';
    if ($isLarge) {
        $attributesModal['data-reveal-larger'] = '';
    }
    $modalParams = acym_getFormattedAttributes($attributesModal);

    $modal = $isButton ? '<button type="button" '.$buttonParams.'>'.$button.'</button>' : $button;
    $modal .= '<div '.$modalParams.'>';
    $modal .= $data;
    $modal .= '<button class="close-button" data-close aria-label="Close reveal" type="button">';
    $modal .= '<span aria-hidden="true">&times;</span>';
    $modal .= '</button></div>';

    return $modal;
}

function acym_modalInclude(
    string $button,
    string $file,
    string $id,
    array  $data,
    string $classModal = '',
    array  $containerAttributes = []
): string {
    if (empty($id)) {
        $id = 'acymodal_'.rand(1000, 9000);
    }

    // Both can be used in the included file
    $dataModal = $data;

    $containerAttributes['data-open'] = $id;
    $containerParams = acym_getFormattedAttributes($containerAttributes);
    $modal = '<div '.$containerParams.'>'.$button;
    $modal .= '<div class="reveal '.acym_escape($classModal).'" id="'.acym_escape($id).'" data-reveal>';
    ob_start();
    include $file;
    $modal .= ob_get_clean();
    $modal .= '<button type="button" class="close-button" data-close aria-label="Close reveal">';
    $modal .= '<span aria-hidden="true">&times;</span>';
    $modal .= '</button></div></div>';

    return $modal;
}

function acym_modalPaginationLists(
    string $inputEventId,
    string $checkedLists = '[]',
    bool   $needDisplaySubscribers = false
): string {
    $searchField = acym_filterSearch('', 'modal_search_lists');

    $data = '<input type="hidden" id="'.acym_escape($inputEventId).'">';

    if ($needDisplaySubscribers) {
        $data .= '<input type="hidden" id="modal__pagination__need__display__sub">';
    }

    $data .= '<div class="cell grid-x" style="display: none;" id="acym__popup__plugin__subscription__lists__modal">
            <input type="hidden" name="show_selected" value="false" id="modal__pagination__show-information">
            <input type="hidden" id="modal__pagination__search__lists">
            <input type="hidden" name="lists_selected" id="acym__modal__lists-selected" value="'.acym_escape($checkedLists).'">
            <div class="cell grid-x">
                <h4 class="cell text-center acym__title acym__title__secondary">'.acym_escape(acym_translation('ACYM_CHOOSE_LISTS')).'</h4>
            </div>
            <div class="cell grid-x modal__pagination__search">
                '.$searchField.'
            </div>
            <div class="cell text-center padding-top-1" id="modal__pagination__search__spinner" style="display: none">
                <i class="acymicon-circle-o-notch acymicon-spin"></i>
            </div>
            <div class="cell medium-6 modal__pagination__show">
                <a href="#" class="acym__color__blue modal__pagination__show-selected modal__pagination__show-button selected">'.acym_escape(
            acym_translation('ACYM_SHOW_SELECTED_LISTS')
        ).'</a>
                <a href="#" class="acym__color__blue modal__pagination__show-all modal__pagination__show-button">'.acym_escape(acym_translation('ACYM_SHOW_ALL_LISTS')).'</a>
            </div>
            <div class="cell grid-x modal__pagination__listing__lists">
                <div class="cell modal__pagination__listing__lists__in-form"></div>
            </div>
            </div>';

    return $data;
}

function acym_frontModal(
    string  $iframeSrc,
    string  $buttonText,
    bool    $isButton,
    ?string $identifier = null,
    ?string $iframeClass = null
): string {
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
        <?php echo acym_escape(acym_translation($buttonText)); ?>
	</a>
	<div class="acym__modal" id="acym__modal__<?php echo acym_escape($identifier); ?>" style="display: none;">
		<div class="acym__modal__content">
			<div class="acym__modal__close"><span>&times;</span></div>
			<iframe class="<?php echo acym_escape($iframeClass); ?>" src="<?php echo acym_escapeUrl($iframeSrc); ?>"></iframe>
		</div>
	</div>
    <?php
    return ob_get_clean();
}
