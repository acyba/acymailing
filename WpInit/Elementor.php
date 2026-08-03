<?php

namespace AcyMailing\WpInit;

defined('ABSPATH') || die('Restricted Access');

class Elementor
{
    public function __construct()
    {
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'addAcyScriptElementor']);
        add_action('elementor/widgets/register', [$this, 'registerWidgets']);
        add_action('elementor/elements/categories_registered', [$this, 'addWidgetCategories']);
        add_action('elementor_pro/init', [$this, 'addAcyFormAction']);
    }

    public function addAcyScriptElementor()
    {
        wp_enqueue_script(
            'select2lib',
            ACYM_JS.'libraries/select2-full.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'select2-full.min.js'),
            ['jquery'],
            '{__VERSION__}',
            [
                'in_footer' => false,
            ]
        );
        wp_enqueue_script(
            'acym_script_widget_article_elementor',
            ACYM_JS.'widget.min.js?v='.time(),
            ['jquery', 'select2lib'],
            '{__VERSION__}',
            [
                'in_footer' => true,
            ]
        );
        // CSRF token for the article-search AJAX (dynamics/trigger requires acym_checkToken)
        wp_add_inline_script(
            'acym_script_widget_article_elementor',
            'var acym_widget_nonce = '.json_encode(wp_create_nonce('acymnonce')).';',
            'before'
        );
        wp_enqueue_script(
            'acymailing-compatibility-elementor',
            ACYM_JS.'libraries/elementor.min.js',
            [],
            '{__VERSION__}',
            [
                'in_footer' => true,
            ]
        );
        wp_enqueue_style(
            'acym_style_widget_article_elementor',
            ACYM_CSS.'libraries/elementor.min.css?v='.time(),
            [],
            '{__VERSION__}',
            [
                'in_footer' => true,
            ]
        );
    }

    public function registerWidgets()
    {
        include_once ACYM_WIDGETS.'subscriptionform'.DS.'elementor.php';
        \Elementor\Plugin::instance()->widgets_manager->register(new \acySubscriptionFormWidget());
    }

    public function addWidgetCategories($elements_manager)
    {
        $elements_manager->add_category('acymailing', [
            'title' => 'AcyMailing',
        ]);
    }

    public function addAcyFormAction()
    {
        $acymailing = new ElementorForm();
        \ElementorPro\Plugin::instance()->modules_manager->get_modules('forms')->add_form_action($acymailing->get_name(), $acymailing);
    }
}
