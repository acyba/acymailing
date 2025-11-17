<?php

namespace AcyMailing\WpInit;

use AcyMailing\Classes\FormClass;

class Forms
{
    private array $formToDisplay = [];

    public function __construct()
    {
        $isPreview = acym_getVar('bool', 'acym_preview', false);
        if ($isPreview) return;

        add_action('wp_head', [$this, 'prepareFormsToDisplay']);
        add_action('wp_footer', [$this, 'displayForms']);
        $this->registerShortcodes();
    }

    public function prepareFormsToDisplay()
    {
        $formClass = new FormClass();
        $forms = $formClass->getAllFormsToDisplay();

        if (empty($forms)) return;

        $menu = acym_getMenu();
        if (empty($menu)) return;

        foreach ($forms as $form) {
            if (!empty($form->pages) && (in_array($menu->ID, $form->pages) || in_array('all', $form->pages))) {
                $this->formToDisplay[] = $formClass->renderForm($form);
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

        $formClass = new FormClass();
        $form = $formClass->getOneById($params['id']);

        if (empty($form->active)) return;

        return $formClass->renderForm($form, false, true);
    }
}
