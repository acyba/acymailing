<?php

use AcyMailing\Core\AcymPlugin;
use AcyMailing\Classes\ListClass;

class plgAcymContactform7 extends AcymPlugin
{
    private array $propertyLabels;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('contact-form-7/wp-contact-form-7.php');

        $this->pluginDescription->name = 'Contact Form 7';
        $this->pluginDescription->category = 'Subscription system';
        $this->pluginDescription->description = '- Add AcyMailing lists on contact forms';

        $this->propertyLabels = [
            'displayLists' => acym_translation('ACYM_DISPLAYED_LISTS'),
            'defaultLists' => acym_translation('ACYM_LISTS_CHECKED_DEFAULT'),
            'autoLists' => acym_translation('ACYM_AUTO_SUBSCRIBE_TO'),
        ];
    }

    public function onAcymInitWordpressAddons()
    {
        add_action('wpcf7_init', [$this, 'addFormTagAcymsub']);
        add_action('wpcf7_admin_init', [$this, 'addTagGeneratorAcymsub'], 100, 0);
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts'], 20, 1);

        add_filter('wpcf7_validate_acymsub', [$this, 'acymsubValidationFilter'], 10, 2);
        add_filter('wpcf7_validate_acymsub*', [$this, 'acymsubValidationFilter'], 10, 2);

        // Extra fonctions called directly from Contact form 7
        include_once __DIR__.DS.'functions.php';
    }

    // Add handler to display tag content in form
    public function addFormTagAcymsub()
    {
        wpcf7_add_form_tag(['acymsub', 'acymsub*'], 'acym_addFormTagAcymsubHandler', ['name-attr' => true]);
    }

    // Add tag in form generator
    public function addTagGeneratorAcymsub()
    {
        $tag_generator = WPCF7_TagGenerator::get_instance();
        $tag_generator->add(
            'acymsub',
            acym_translation('ACYM_ACYMAILING_LISTS'),
            'acym_addTagGeneratorAcymsubHandler'
        );
    }

    // Filter to block form submission
    public function acymsubValidationFilter($result, $tag)
    {
        $name = $tag->name;
        if (!$tag->is_required()) {
            return $result;
        }

        $value = acym_getVar('array', $name, []);
        $hiddenValue = acym_getVar('string', 'acymhiddenlists_'.$name, '');
        if (empty($value) && empty($hiddenValue)) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
        }

        return $result;
    }

    // Load JS for contact form 7 tag generator
    public function adminEnqueueScripts($hook_suffix)
    {
        $this->loadJavascript('acymcontactform', false, ACYM_PLUGINS_URL.'/'.basename(__DIR__));
    }

    public function displayAcymsub($tag)
    {
        if (empty($tag->name)) return '';

        $submitted = acym_getVar('string', 'acymaction_'.$tag->name, '');

        if (!empty($submitted)) return '';

        // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
        $style = '<link rel="stylesheet" href="'.ACYM_PLUGINS_URL.'/'.basename(__DIR__).DS.'css'.DS.'acymcontactform.css" type="text/css">';
        $this->loadJavascript('acymcontactform', false, ACYM_PLUGINS_URL.'/'.basename(__DIR__));

        $class = wpcf7_form_controls_class($tag->type);
        $tagName = $tag->name;

        $validationError = wpcf7_get_validation_error($tagName);
        if ($validationError) {
            $class .= ' wpcf7-not-valid';
        }

        $class = $tag->get_class_option($class);

        $listClass = new ListClass();
        $listNames = $listClass->getAllForSelect(true, 0, true, true);

        $detailsLists = $this->prepareLists($tag->values);

        $securityKey = '';
        if (!empty($this->config->get('recaptcha_secretkey', ''))) {
            $securityKey = '&seckey='.$this->config->get('security_key', '');
        }

        $acymSubmitUrl = htmlspecialchars_decode(acym_frontendLink('frontusers&task=subscribe'.$securityKey));

        $data = [
            'tag' => $tag,
            'class' => $class,
            'tagName' => $tagName,
            'validationError' => $validationError,
            'listNames' => $listNames,
            'detailsLists' => $detailsLists,
            'acymSubmitUrl' => $acymSubmitUrl,
        ];

        return $style.$this->includeView('acymsubDisplay', $data, __DIR__);
    }

    protected function prepareLists($values)
    {
        $lists = [
            'displayLists' => [],
            'defaultLists' => [],
            'autoLists' => [],
        ];
        foreach ($values as $oneValue) {
            $tmp = explode(':', $oneValue);
            $lists[$tmp[0]] = explode(',', $tmp[1]);
        }

        // Make sure we don't display a list that's in "automatically subscribe to"
        if (!empty($lists['displayLists']) && !empty($lists['autoLists'])) {
            $lists['displayLists'] = array_diff($lists['displayLists'], $lists['autoLists']);
        }

        return $lists;
    }

    public function setAcymsubParameters($contact_form, $args = '')
    {
        $args = wp_parse_args($args, []);

        $listClass = new ListClass();
        $lists = $listClass->getAllWithoutManagement();
        foreach ($lists as $i => $oneList) {
            if ($oneList->active == 0) {
                unset($lists[$i]);
            }
        }

        $data = [
            'lists' => $lists,
            'args' => $args,
            'propertyLabels' => $this->propertyLabels,
        ];

        echo wp_kses(
            $this->includeView('acymsubParameters', $data, __DIR__),
            [
                'div' => [
                    'class' => [],
                ],
                'fieldset' => [],
                'a' => [
                    'href' => [],
                    'target' => [],
                ],
                'legend' => [
                    'class' => [],
                ],
                'table' => [
                    'class' => [],
                ],
                'tbody' => [],
                'tr' => [],
                'th' => [
                    'scope' => [],
                ],
                'td' => [],
                'label' => [
                    'for' => [],
                ],
                'input' => [
                    'type' => [],
                    'name' => [],
                    'id' => [],
                    'class' => [],
                    'placeholder' => [],
                    'value' => [],
                    'data-type' => [],
                    'readonly' => [],
                    'onfocus' => [],
                ],
                'br' => [
                    'class' => [],
                ],
                'p' => [
                    'class' => [],
                ],
                'strong' => [],
                'span' => [
                    'class' => [],
                ],
                'select' => ['name' => [], 'id' => [], 'class' => [], 'multiple' => [], 'onchange' => []],
                'option' => ['value' => [], 'selected' => [], 'disabled' => [], 'data-hidden' => []],
                'optgroup' => ['label' => []],
            ]
        );
    }
}
