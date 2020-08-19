<?php

class plgAcymContactform7 extends acymPlugin
{
    var $propertyLabels;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->pluginDescription->name = 'Contact Form 7';
        $this->installed = acym_isExtensionActive('contact-form-7/wp-contact-form-7.php');

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
        include_once 'fonctions.php';
    }

    // Add handler to display tag content in form
    public function addFormTagAcymsub()
    {
        wpcf7_add_form_tag(
            ['acymsub', 'acymsub*'],
            'addFormTagAcymsubHandler',
            ['name-attr' => true]
        );
    }

    // Add tag in form generator
    public function addTagGeneratorAcymsub()
    {
        $tag_generator = WPCF7_TagGenerator::get_instance();
        $tag_generator->add(
            'acymsub',
            acym_translation('ACYM_ACYMAILING_LISTS'),
            'addTagGeneratorAcymsubHandler'
        );
    }

    // Validation filter
    public function acymsubValidationFilter($result, $tag)
    {
        $name = $tag->name;
        $is_required = $tag->is_required();
        $value = isset($_POST[$name]) ? (array)$_POST[$name] : [];
        $hiddenValue = isset($_POST['acymhiddenlists_'.$name]) ? $_POST['acymhiddenlists_'.$name] : '';
        if ($is_required && empty($value) && empty($hiddenValue)) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
        }

        return $result;
    }

    // Load JS for contact form 7 tag generator
    public function adminEnqueueScripts($hook_suffix)
    {
        $this->loadJavascript('acymcontactform');
    }


    public function displayAcymsub($tag)
    {
        $this->loadJavascript('acymcontactform');
        $this->loadCSS('acymcontactform');

        if (empty($tag->name)) return;

        $class = wpcf7_form_controls_class($tag->type);
        $tagName = $tag->name;

        $validationError = wpcf7_get_validation_error($tagName);
        if ($validationError) {
            $class .= ' wpcf7-not-valid';
        }

        $class = $tag->get_class_option($class);

        $listClass = acym_get('class.list');
        $listNames = $listClass->getAllForSelect();

        $detailsLists = $this->prepareLists($tag->values);
        $acymSubmitUrl = htmlspecialchars_decode(acym_rootURI().acym_addPageParam('frontusers&task=subscribe', true, true));

        $data = [
            'tag' => $tag,
            'class' => $class,
            'tagName' => $tagName,
            'validationError' => $validationError,
            'listNames' => $listNames,
            'detailsLists' => $detailsLists,
            'acymSubmitUrl' => $acymSubmitUrl,
        ];

        return $this->includeView('acymsubDisplay', $data);
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

        $listClass = acym_get('class.list');
        $lists = $listClass->getAllWIthoutManagement();
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

        echo $this->includeView('acymsubParameters', $data);
    }
}
