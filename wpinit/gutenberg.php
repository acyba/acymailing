<?php

namespace AcyMailing\Init;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Libraries\acymParameter;

class acyGutenberg
{
    public function __construct()
    {
        add_filter('block_categories_all', [$this, 'registerAcymCategory'], 1, 2);
        $this->registerBlock();
    }

    public function registerBlock()
    {
        if (!function_exists('register_block_type')) return;

        $listClass = new ListClass();
        $lists = $listClass->getAllForSelect(false);

        $fieldClass = new FieldClass();
        $allFields = $fieldClass->getAll();
        $fields = [];
        foreach ($allFields as $field) {
            if ($field->id == 2 || $field->active === '0') continue;
            $field->name = acym_translation($field->name);
            $fields[$field->id] = $field;
        }

        $posts = acym_trigger('getPosts', [false, 0], 'plgAcymPost');

        wp_register_script(
            'gutenberg-acymailing-subscription-form',
            ACYM_JS.'gutenberg/subscription.min.js?time='.time()
        );
        wp_add_inline_script(
            'gutenberg-acymailing-subscription-form',
            'var acym_lists = '.json_encode($lists).';
            var ACYM_JS_TXT = '.acym_getJSMessages().';
            var acym_fields = '.json_encode($fields).';
            var acym_posts = '.json_encode($posts)
        );

        register_block_type(
            'acymailing/subscription-form',
            [
                'apiVersion' => 2,
                'editor_script' => 'gutenberg-acymailing-subscription-form',
                'render_callback' => [$this, 'renderCallback'],
                'attributes' => [
                    'title' => [
                        'type' => 'string',
                        'default' => 'Receive our newsletters',
                    ],
                    'mode' => [
                        'type' => 'string',
                        'default' => 'tableless',
                    ],
                    'hiddenlists' => [
                        'type' => 'array',
                    ],
                    'displists' => [
                        'type' => 'array',
                    ],
                    'listschecked' => [
                        'type' => 'array',
                    ],
                    'listposition' => [
                        'type' => 'string',
                        'default' => 'before',
                    ],
                    'fields' => [
                        'type' => 'array',
                    ],
                    'textmode' => [
                        'type' => 'string',
                        'default' => '1',
                    ],
                    'subtext' => [
                        'type' => 'string',
                        'default' => 'Subscribe',
                    ],
                    'subtextlogged' => [
                        'type' => 'string',
                        'default' => 'Subscribe',
                    ],
                    'termscontent' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'privacypolicy' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'unsub' => [
                        'type' => 'string',
                        'default' => '0',
                    ],
                    'unsubtext' => [
                        'type' => 'string',
                        'default' => 'Unsubscribe',
                    ],
                    'unsubredirect' => [
                        'type' => 'string',
                    ],
                    'successmode' => [
                        'type' => 'string',
                        'default' => 'replace',
                    ],
                    'confirmation_message' => [
                        'type' => 'string',
                    ],
                    'redirect' => [
                        'type' => 'string',
                    ],
                    'userinfo' => [
                        'type' => 'string',
                        'default' => '1',
                    ],
                    'introtext' => [
                        'type' => 'string',
                    ],
                    'posttext' => [
                        'type' => 'string',
                    ],
                    'alignment' => [
                        'type' => 'string',
                        'default' => 'none',
                    ],
                    'source' => [
                        'type' => 'string',
                        'default' => 'gutenberg_subscription_form',
                    ],
                ],
            ]
        );
    }

    public function registerAcymCategory($categories, $post)
    {
        return array_merge(
            $categories,
            [
                [
                    'slug' => 'acymailing',
                    'title' => 'AcyMailing',
                    'icon' => 'editor-table',
                ],
            ]
        );
    }

    public function renderCallback($block_attributes, $content)
    {
        $params = new acymParameter($block_attributes);

        return acym_renderForm(
            $params,
            ['disableButtons' => strpos(acym_currentURL(), 'block-renderer') !== false]
        );
    }
}

$acyGutenberg = new acyGutenberg();
