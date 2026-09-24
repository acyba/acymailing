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
            'rel' => true,
            'aria-label' => true,
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

    const ALLOWED_HTML_FIELD_NAME = [
        'b' => [],
        'br' => [],
        'strong' => [],
        'em' => [],
        'i' => [],
        'span' => [
            'class' => true,
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

    const ALLOWED_HTML_NOTIFICATION = [
        'a' => [
            'href' => true,
            'target' => true,
            'id' => true,
            'class' => true,
            'title' => true,
        ],
        'b' => [],
        'br' => [],
        'i' => [],
        'p' => [
            'class' => true,
            'title' => true,
        ],
        'pre' => [],
        'strong' => [],
    ];

    const ALLOWED_HTML_MESSAGE = [
        'a' => [
            'href' => true,
            'target' => true,
            'id' => true,
            'class' => true,
            'title' => true,
        ],
        'b' => [],
        'br' => [],
        'div' => [
            'class' => true,
        ],
        'i' => [],
        'li' => [],
        'p' => [
            'class' => true,
            'title' => true,
        ],
        'pre' => [],
        'span' => [
            'class' => true,
            'data-acym-email-id' => true,
        ],
        'strong' => [],
        'ul' => [],
    ];

    const ALLOWED_HTML_CHECKBOX_LABEL = [
        'div' => [
            'class' => true,
        ],
        'span' => [
            'class' => true,
        ],
        'input' => [
            'class' => true,
            'type' => true,
            'name' => true,
            'value' => true,
        ],
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

    const ALLOWED_HTML_PARAM_FIELD = [
        'input' => [
            'type' => true,
            'name' => true,
            'value' => true,
        ],
        'select' => [
            'class' => true,
            'id' => true,
            'name' => true,
            'multiple' => true,
        ],
        'option' => [
            'value' => true,
            'selected' => true,
            'disabled' => true,
        ],
    ];
}
