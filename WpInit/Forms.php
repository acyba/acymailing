<?php

namespace AcyMailing\WpInit;

defined('ABSPATH') || die('Restricted Access');

use AcyMailing\Classes\FormClass;

class Forms
{
    private array $formToDisplay = [];

    public function __construct()
    {
        $isPreview = acym_getVar('bool', 'acym_preview', false);
        if ($isPreview) return;

        if (acym_level(ACYM_ENTERPRISE)) {
            add_action('wp_head', [$this, 'prepareFormsToDisplay']);
            add_action('wp_footer', [$this, 'displayForms']);
        }
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

        if (!empty($this->formToDisplay)) {
            acym_initModule();
        }
    }

    public function displayForms()
    {
        if (empty($this->formToDisplay)) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated in partial folder "forms", escaped there.
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

        return $this->removeLineBreaks($formClass->renderForm($form, false, true));
    }

    // Themes and page builders running wpautop after the shortcode is replaced would otherwise break the form layout
    private function removeLineBreaks(string $html): string
    {
        $cleanedHtml = preg_replace_callback(
            '#(<textarea\b[^>]*>.*?</textarea>)|>\s*\R\s*</|\s*\R\s*#is',
            function ($matches) {
                if (!empty($matches[1])) return $matches[1];

                return $matches[0][0] === '>' ? '></' : ' ';
            },
            $html
        );

        return $cleanedHtml ?? $html;
    }
}
