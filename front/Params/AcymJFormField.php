<?php

namespace AcyMailing\Params;

use Joomla\CMS\Form\FormField;

if (!class_exists('AcymJFormField')) {
    if ('{__CMS__}' === 'Joomla') {
        require_once __DIR__.DIRECTORY_SEPARATOR.'AcymJFormFieldJoomla.php';
        class_alias('AcyMailing\Params\AcymJFormFieldJoomla', 'AcyMailing\Params\AcymJFormField');
    } else {
        require_once __DIR__.DIRECTORY_SEPARATOR.'AcymJFormFieldWordPress.php';
        class_alias('AcyMailing\Params\AcymJFormFieldWordPress', 'AcyMailing\Params\AcymJFormField');
    }
}
