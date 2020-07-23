<?php

class acyElementor extends acyHook
{
    public function __construct()
    {
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'addAcyScriptElementor']);
    }

    public function addAcyScriptElementor()
    {
        wp_enqueue_script('select2lib', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6/js/select2.full.min.js', ['jquery']);
        wp_enqueue_script('acym_script_widget_article_elementor', ACYM_JS.'widget.min.js?v='.time(), ['jquery', 'select2lib'], false, true);
        wp_enqueue_script(
            'acymailing-compatibility-elementor',
            ACYM_JS.'libraries/elementor.min.js',
            [],
            false,
            true

        );
        wp_enqueue_style('acym_style_widget_article_elementor', ACYM_CSS.'libraries/elementor.min.css?v='.time());
    }
}

$acyElementor = new acyElementor();
