<?php

namespace AcyMailing\Helpers;

use AcyMailing\Core\AcymObject;

class SecurityHelper extends AcymObject
{
    const ALLOWED_HTML_DATE = [
        'div' => [
            'class' => true,
            'style' => true,
            'id' => true,
            'data-reveal' => true,
            'data-reveal-larger' => true,
        ],
        'input' => [
            'type' => true,
            'name' => true,
            'id' => true,
            'value' => true,
            'class' => true,
            'data-open' => true,
            'readonly' => true,
            'data-acym-translate' => true,
            'data-rs' => true,
            'onchange' => true,
            'data-reveal' => true,
            'data-reveal-larger' => true,
        ],
        'span' => ['class' => true, 'aria-hidden' => true],
        'button' => [
            'type' => true,
            'class' => true,
            'data-close' => true,
            'data-type' => true,
            'aria-label' => true,
            'data-open' => true,
        ],
        'select' => [
            'id' => true,
            'name' => true,
            'class' => true,
        ],
        'optgroup' => ['label' => true],
        'option' => ['value' => true, 'selected' => true, 'disabled' => true],
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

    const ALLOWED_HTML_INTRO = [
        'i' => [
            'class' => true,
        ],
        'b' => [
            'class' => true,
        ],
        'p' => [
            'class' => true,
        ],
        'strong' => [
            'class' => true,
        ],
        'span' => [
            'class' => true,
        ],
    ];
}
