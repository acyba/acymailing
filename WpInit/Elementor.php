<?php

namespace AcyMailing\WpInit;

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
        wp_enqueue_script('select2lib', ACYM_JS.'libraries/select2-full.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'select2-full.min.js'), ['jquery']);
        wp_enqueue_script('acym_script_widget_article_elementor', ACYM_JS.'widget.min.js?v='.time(), ['jquery', 'select2lib'], false, true);
        wp_enqueue_script('acymailing-compatibility-elementor', ACYM_JS.'libraries/elementor.min.js', [], false, true);
        wp_enqueue_style('acym_style_widget_article_elementor', ACYM_CSS.'libraries/elementor.min.css?v='.time());
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
