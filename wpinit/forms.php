<?php

class acyForms extends acyHook
{
    var $formToDisplay = [];
    var $formClass;

    public function __construct()
    {
        $isPreview = acym_getVar('bool', 'acym_preview', false);
        if ($isPreview) return;
        $this->formClass = acym_get('class.form');
        add_action('wp_head', [$this, 'prepareFormsToDisplay']);
        add_action('wp_footer', [$this, 'displayForms']);
        add_action('init', [$this, 'registerShortcodes']);
    }

    public function prepareFormsToDisplay()
    {
        $forms = $this->formClass->getAllFormsToDisplay();

        if (empty($forms)) return;

        $menu = acym_getMenu();
        if (empty($menu)) return;

        foreach ($forms as $form) {
            if (!empty($form->pages) && (in_array($menu->ID, $form->pages) || in_array('all', $form->pages))) {
                $this->formToDisplay[] = $this->formClass->renderForm($form);
            }
        }

        if (!empty($this->formToDisplay)) acym_initModule();
    }

    public function displayForms()
    {
        if (empty($this->formToDisplay)) return;

        echo implode('', $this->formToDisplay);
    }

    public function registerShortcodes()
    {
        add_shortcode('acymailing_form_shortcode', [$this, 'replaceShortcode']);
    }

    public function replaceShortcode($params)
    {
        extract(
            shortcode_atts(
                [
                    'id' => 0,
                ],
                $params
            )
        );

        if (empty($params['id'])) return;

        $form = $this->formClass->getOneById($params['id']);

        if (empty($form) || empty($form->active)) return;

        return $this->formClass->renderForm($form);
    }
}

$acyForms = new acyForms();
