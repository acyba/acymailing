<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Plugins\Installed;
use AcyMailing\Controllers\Plugins\Available;

class PluginsController extends acymController
{
    var $tabs, $types, $level, $errors;

    use Installed;
    use Available;

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_ADD_ONS')] = acym_completeLink('plugins');
        $this->loadScripts = [
            'available' => ['vue-applications' => ['available_plugins']],
            'installed' => ['datepicker', 'vue-prism-editor', 'vue-applications' => ['custom_view', 'installed_plugins']],
        ];

        $this->setDefaultTask('installed');

        $this->tabs = [
            'installed' => acym_translation('ACYM_MY_ADD_ONS'),
            'available' => acym_translation('ACYM_AVAILABLE_ADD_ONS'),
        ];

        $this->types = [
            '' => acym_translation('ACYM_ANY_CATEGORY'),
            'Files management' => acym_translation('ACYM_FILES_MANAGEMENT'),
            'E-commerce solutions' => acym_translation('ACYM_E_COMMERCE_SOLTIONS'),
            'Content management' => acym_translation('ACYM_CONTENT_MANAGEMENT'),
            'Subscription system' => acym_translation('ACYM_SUBSCRIPTION_SYSTEM'),
            'User management' => acym_translation('ACYM_USERS_MANAGEMENT'),
            'Events management' => acym_translation('ACYM_EVENTS_MANAGEMENT'),
            'Others' => acym_translation('ACYM_OTHER'),
        ];

        $this->level = [
            '' => acym_translation('ACYM_ACYMAILING_LEVEL'),
            'starter' => 'Starter',
            'essential' => 'Essential',
            'enterprise' => 'Enterprise',
        ];

        $this->errors = [
            'NOT_ALLOWED' => 'ACYM_NOT_ALLOWED_LEVEL',
            'MISSING_DOMAIN' => 'ACYM_MISSING_DOMAIN',
            'NOT_FOUND' => 'ACYM_ADD_ON_NOT_FOUND',
        ];
    }
}
