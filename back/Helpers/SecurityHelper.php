<?php

namespace AcyMailing\Helpers;

use AcyMailing\Core\AcymObject;

class SecurityHelper extends AcymObject
{
    const ALLOWED_HTML_DATE = [
        'div' => [
            'class' => [],
            'style' => [],
            'id' => [],
            'data-reveal' => [],
            'data-reveal-larger' => [],
        ],
        'input' => [
            'type' => [],
            'name' => [],
            'id' => [],
            'value' => [],
            'class' => [],
            'data-open' => [],
            'readonly' => [],
            'data-acym-translate' => [],
            'data-rs' => [],
            'onchange' => [],
            'data-reveal' => [],
            'data-reveal-larger' => [],
        ],
        'span' => ['class' => [], 'aria-hidden' => []],
        'button' => [
            'type' => [],
            'class' => [],
            'data-close' => [],
            'data-type' => [],
            'aria-label' => [],
            'data-open' => [],
        ],
        'select' => [
            'id' => [],
            'name' => [],
            'class' => [],
        ],
        'optgroup' => ['label' => []],
        'option' => ['value' => [], 'selected' => [], 'disabled' => []],
    ];

    const ALLOWED_HTML_SELECT = [
        'select' => [
            'class' => true,
            'name' => true,
            'id' => true,
        ],
        'option' => [
            'value' => true,
            'selected' => true,
            'disabled' => true,
        ],
    ];

    const ALLOWED_HTML_TERMS = [
        'a' => [
            'href' => true,
            'target' => true,
            'title' => true,
            'class' => true,
            'data-acym-modal' => true,
        ],
        'div' => [
            'class' => true,
            'id' => true,
            'style' => true,
        ],
        'span' => [
        ],
        'iframe' => [
            'class' => true,
            'src' => true,
        ],
    ];
}
