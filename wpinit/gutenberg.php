<?php

namespace AcyMailing\Init;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Libraries\acymParameter;

class acyGutenberg
{
    public $lists;
    public $fields;

    public function __construct()
    {
        add_filter('block_categories_all', [$this, 'registerAcymCategory'], 1, 2);
        $this->registerBlock();
    }

    public function registerBlock()
    {
        if (!function_exists('register_block_type')) return;

        $listClass = new ListClass();
        $this->lists = $listClass->getAllForSelect(false);

        $fieldClass = new FieldClass();
        $allFields = $fieldClass->getAll();
        $fields = [];
        foreach ($allFields as $field) {
            if ($field->id == 2 || intval($field->active) === 0) continue;
            $field->name = acym_translation($field->name);
            $fields[$field->id] = $field;
        }
        $this->fields = $fields;

        $this->registerBlockSubscription();
        $this->registerBlockProfile();
        $this->registerBlockArchive();
    }

    public function registerBlockSubscription()
    {
        $posts = acym_trigger('getPosts', [false, 0], 'plgAcymPost');

        wp_register_script(
            'gutenberg-acymailing-subscription-form',
            ACYM_JS.'gutenberg/subscription.min.js?time='.time()
        );
        wp_add_inline_script(
            'gutenberg-acymailing-subscription-form',
            'var acym_lists = '.json_encode($this->lists).';
            var ACYM_JS_TXT = '.acym_getJSMessages().';
            var acym_fields = '.json_encode($this->fields).';
            var acym_posts = '.json_encode($posts)
        );

        $basicAttribute = [
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
        ];
        $moreAttributes = [];
        if (acym_isMultilingual()) {
            foreach (acym_getMultilingualLanguages() as &$language) {
                $moreAttributes['subtext_'.$language->language] = ['type' => 'string', 'default' => 'Subscribe'];
                $moreAttributes['subtextlogged_'.$language->language] = ['type' => 'string', 'default' => 'Subscribe'];
                $moreAttributes['confirmation_message_'.$language->language] = ['type' => 'string', 'default' => ''];
            }
        } else {
            $moreAttributes['subtext'] = [
                'type' => 'string',
                'default' => 'Subscribe',
            ];
            $moreAttributes['subtextlogged'] = [
                'type' => 'string',
                'default' => 'Subscribe',
            ];
            $moreAttributes['confirmation_message'] = [
                'type' => 'string',
                'default' => '',

            ];
        }
        $attributes = array_merge($basicAttribute, $moreAttributes);

        register_block_type(
            'acymailing/subscription-form',
            [
                'apiVersion' => 2,
                'editor_script' => 'gutenberg-acymailing-subscription-form',
                'render_callback' => [$this, 'renderCallback'],
                'attributes' => $attributes,
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
        if (!array_key_exists('subtext', $block_attributes)) {
            $moreBlockAttributes = [];
            $currentLanguage = strtolower('subtext_'.acym_getLanguageTag());
            $currentLanguageLogged = strtolower('subtextlogged_'.acym_getLanguageTag());
            $confirmMessageLangue = strtolower('confirmation_message_'.acym_getLanguageTag());
            $moreBlockAttributes['subtext'] = $block_attributes[$currentLanguage];
            $moreBlockAttributes['subtextlogged'] = $block_attributes[$currentLanguageLogged];
            $moreBlockAttributes['confirmation_message'] = $block_attributes[$confirmMessageLangue];
            $block_attributes = array_merge($block_attributes, $moreBlockAttributes);
        }
        $params = new acymParameter($block_attributes);

        return acym_renderForm(
            $params,
            ['disableButtons' => strpos(acym_currentURL(), 'block-renderer') !== false]
        );
    }

    public function registerBlockProfile()
    {
        if (!function_exists('register_block_type')) return;

        wp_register_script(
            'gutenberg-acymailing-profile',
            ACYM_JS.'gutenberg/profile.min.js?time='.time()
        );
        wp_add_inline_script(
            'gutenberg-acymailing-profile',
            'var acym_lists_profile = '.json_encode($this->lists).';
            var ACYM_JS_TXT = '.acym_getJSMessages().';
            var acym_fields_profile = '.json_encode($this->fields)
        );

        register_block_type(
            'acymailing/profile',
            [
                'apiVersion' => 2,
                'editor_script' => 'gutenberg-acymailing-profile',
                'render_callback' => [$this, 'renderCallbackProfile'],
                'attributes' => [
                    'title' => [
                        'type' => 'string',
                        'default' => 'Profile',
                    ],
                    'lists' => [
                        'type' => 'array',
                    ],
                    'listsdropdown' => [
                        'type' => 'array',
                    ],
                    'listschecked' => [
                        'type' => 'array',
                    ],
                    'hiddenlists' => [
                        'type' => 'array',
                    ],
                    'fields' => [
                        'type' => 'array',
                    ],
                    'introtext' => [
                        'type' => 'string',
                    ],
                    'posttext' => [
                        'type' => 'string',
                    ],
                    'source' => [
                        'type' => 'string',
                        'default' => 'gutenberg_profile',
                    ],
                ],
            ]
        );
    }


    public function renderCallbackProfile($block_attributes, $content)
    {
        $params = new acymParameter($block_attributes);

        return acym_renderFormProfile(
            $params,
            ['disableButtons' => strpos(acym_currentURL(), 'block-renderer') !== false]
        );
    }

    public function registerBlockArchive()
    {
        if (!function_exists('register_block_type')) return;

        wp_register_script(
            'gutenberg-acymailing-archive',
            ACYM_JS.'gutenberg/archive.min.js?time='.time()
        );
        wp_add_inline_script(
            'gutenberg-acymailing-archive',
            'var acym_lists_archive = '.json_encode($this->lists).';
            var ACYM_JS_TXT = '.acym_getJSMessages()
        );

        register_block_type(
            'acymailing/archive',
            [
                'apiVersion' => 2,
                'editor_script' => 'gutenberg-acymailing-archive',
                'render_callback' => [$this, 'renderCallbackArchive'],
                'attributes' => [
                    'title' => [
                        'type' => 'string',
                        'default' => 'See all newsletters',
                    ],
                    'archiveNbNewslettersPerPage' => [
                        'type' => 'string',
                        'default' => '20',
                    ],
                    'lists' => [
                        'type' => 'array',
                    ],
                    'popup' => [
                        'type' => 'array',
                    ],
                    'displayUserListOnly' => [
                        'type' => 'array',
                    ],
                ],
            ]
        );
    }


    public function renderCallbackArchive($block_attributes, $content)
    {
        $params = new acymParameter($block_attributes);

        return acym_renderFormArchive(
            $params,
            ['disableButtons' => strpos(acym_currentURL(), 'block-renderer') !== false]
        );
    }
}

$acyGutenberg = new acyGutenberg();
