<?php

namespace AcyMailing\Init;

class acyBeaver
{

    public function __construct()
    {
        add_action('fl_builder_after_render_module', [$this, 'beaverBuilderInit'], 10, 1);
        add_action('fl_builder_after_render_ajax_layout_html', [$this, 'addAcyscriptBeaver']);
    }

    public function beaverBuilderInit($widget)
    {
        if (empty($widget->settings->widget) || $widget->settings->widget !== 'acym_subscriptionform_widget') return;

        $this->addAcyscriptBeaver();
    }

    public function addAcyscriptBeaver()
    {
        wp_enqueue_script('select2lib', ACYM_JS.'libraries/select2-full.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'select2-full.min.js'), ['jquery']);
        wp_enqueue_script('acym_script_widget_article_beaver', ACYM_JS.'widget.min.js?v='.time(), ['jquery', 'select2lib'], false, true);
    }
}

$acyBeaver = new acyBeaver();
