<?php

namespace AcyMailing\WpInit;

defined('ABSPATH') || die('Restricted Access');

class Beaver
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
        wp_enqueue_script(
            'select2lib',
            ACYM_JS.'libraries/select2-full.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'select2-full.min.js'),
            [
                'jquery',
            ],
            '{__VERSION__}',
            [
                'in_footer' => false,
            ]
        );
        wp_enqueue_script(
            'acym_script_widget_article_beaver',
            ACYM_JS.'widget.min.js?v='.time(),
            [
                'jquery',
                'select2lib',
            ],
            '{__VERSION__}',
            [
                'in_footer' => true,
            ]
        );
        // CSRF token for the article-search AJAX (dynamics/trigger requires acym_checkToken)
        wp_add_inline_script(
            'acym_script_widget_article_beaver',
            'var acym_widget_nonce = '.json_encode(wp_create_nonce('acymnonce')).';',
            'before'
        );
    }
}
