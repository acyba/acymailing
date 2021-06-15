<?php

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Libraries\acymParameter;

class acySubscriptionFormWidget extends \Elementor\Widget_Base
{
    var $displayMode;
    var $lists;
    var $listsPlacement;
    var $fields = [];
    var $displayTextMode;
    var $subscriberInfo;
    var $posts;
    var $replaceMessage;
    var $unsubButton;
    var $alignment;
    var $includeJavascript;
    var $settings;
    var $borderTypes;

    public function initParams()
    {
        $this->displayMode = [
            'inline' => acym_translation('ACYM_MODE_HORIZONTAL'),
            'vertical' => acym_translation('ACYM_MODE_VERTICAL'),
            'tableless' => acym_translation('ACYM_MODE_TABLELESS'),
        ];


        $listClass = new ListClass();
        $this->lists = $listClass->getAllForSelect(false);

        $this->listsPlacement = [
            'before' => acym_translation('ACYM_BEFORE_FIELDS'),
            'after' => acym_translation('ACYM_AFTER_FIELDS'),
        ];

        $fieldClass = new FieldClass();
        $allFields = $fieldClass->getAll();
        foreach ($allFields as $field) {
            if ($field->id == 2 || $field->active === '0') continue;
            $this->fields[$field->id] = acym_translation($field->name);
        }

        $this->displayTextMode = [
            1 => acym_translation('ACYM_TEXT_INSIDE'),
            0 => acym_translation('ACYM_TEXT_OUTSIDE'),
        ];

        $this->subscriberInfo = [
            1 => acym_translation('ACYM_YES'),
            0 => acym_translation('ACYM_NO'),
        ];

        $posts = acym_trigger('getPosts', [false, 0], 'plgAcymPost');
        foreach ($posts[0] as $post) {
            $this->posts[$post[0]] = $post[1];
        }

        $this->replaceMessage = [
            'replace' => acym_translation('ACYM_SUCCESS_REPLACE'),
            'replacetemp' => acym_translation('ACYM_SUCCESS_REPLACE_TEMP'),
            'toptemp' => acym_translation('ACYM_SUCCESS_TOP_TEMP'),
            'standard' => acym_translation('ACYM_SUCCESS_STANDARD'),
        ];

        $this->unsubButton = [
            '0' => acym_translation('ACYM_NO'),
            '1' => acym_translation('ACYM_CONNECTED_USER_SUBSCRIBED'),
            '2' => acym_translation('ACYM_ALWAYS'),
        ];

        $this->alignment = [
            'none' => acym_translation('ACYM_DEFAULT'),
            'left' => acym_translation('ACYM_LEFT'),
            'center' => acym_translation('ACYM_CENTER'),
            'right' => acym_translation('ACYM_RIGHT'),
        ];

        $this->includeJavascript = [
            'header' => acym_translation('ACYM_IN_HEADER'),
            'module' => acym_translation('ACYM_ON_THE_MODULE'),
        ];

        $this->borderTypes = [
            'solid' => acym_translation('ACYM_SOLID'),
            'dotted' => acym_translation('ACYM_DOTTED'),
            'dashed' => acym_translation('ACYM_DASHED'),
            'double' => acym_translation('ACYM_DOUBLE'),
            'groove' => acym_translation('ACYM_GROOVE'),
            'ridge' => acym_translation('ACYM_RIDGE'),
            'inset' => acym_translation('ACYM_INSET'),
            'outset' => acym_translation('ACYM_OUTSET'),
        ];
    }

    public function get_name()
    {
        return 'acy_sub_form';
    }

    public function get_title()
    {
        return acym_translation('ACYM_ACYMAILING_SUBSCRIPTION_FORM');
    }

    public function get_icon()
    {
        return 'fa fa-envelope';
    }

    public function get_categories()
    {
        return ['acymailing'];
    }

    protected function _register_controls()
    {
        $this->initParams();

        //Main option
        $this->startControlsSection('main_options', acym_translation('ACYM_MAIN_OPTIONS'));
        $this->getText('title', acym_translation('ACYM_TITLE'), 'Receive our newsletters');
        $this->getSimpleSelect('mode', acym_translation('ACYM_DISPLAY_MODE'), $this->displayMode, 'inline');
        $this->getText('subtext', acym_translation('ACYM_SUBSCRIBE_TEXT'), 'Subscribe');
        $this->getText('subtextlogged', acym_translation('ACYM_SUBSCRIBE_TEXT_LOGGED_IN'), 'Subscribe');
        $this->end_controls_section();

        //Lists option
        $this->startControlsSection('lists_options', acym_translation('ACYM_LISTS_OPTIONS'));
        $this->getSelect('hiddenlists', acym_translation('ACYM_AUTO_SUBSCRIBE_TO'), $this->lists, '', true);
        $this->getSelect('displists', acym_translation('ACYM_DISPLAYED_LISTS'), $this->lists, '', true);
        $this->getSelect('listschecked', acym_translation('ACYM_LISTS_CHECKED_DEFAULT'), $this->lists, '', true);
        $this->getSelect('listposition', acym_translation('ACYM_DISPLAY_LISTS'), $this->listsPlacement, 'before');
        $this->end_controls_section();

        //Fields option
        $this->startControlsSection('fields_options', acym_translation('ACYM_FIELDS_OPTIONS'));
        $this->getSelect('fields', acym_translation('ACYM_FIELDS_TO_DISPLAY'), $this->fields, '', true);
        $this->getSimpleSelect('textmode', acym_translation('ACYM_TEXT_MODE'), $this->displayTextMode, '1');
        $this->getSimpleSelect('userinfo', acym_translation('ACYM_FORM_AUTOFILL_ID'), $this->subscriberInfo, '1');
        $this->end_controls_section();

        //Terms and policy option
        $this->startControlsSection('terms_condition_options', acym_translation('ACYM_TERMS_POLICY_OPTIONS'));
        $this->getSimpleSelect('termscontent', acym_translation('ACYM_TERMS_CONDITIONS'), $this->posts, '');
        $this->getSimpleSelect('privacypolicy', acym_translation('ACYM_PRIVACY_POLICY'), $this->posts, '');
        $this->end_controls_section();

        //Subscribe option
        $this->startControlsSection('subscribe_options', acym_translation('ACYM_SUBSCRIBE_OPTIONS'));
        $this->getSimpleSelect('successmode', acym_translation('ACYM_SUCCESS_MODE'), $this->replaceMessage, 'replace');
        $this->getText('confirmation_message', acym_translation('ACYM_CONFIRMATION_MESSAGE'));
        $this->getText('redirect', acym_translation('ACYM_REDIRECT_LINK'));
        $this->end_controls_section();

        //Unsubscribe option
        $this->startControlsSection('unsubscribe_options', acym_translation('ACYM_UNSUBSCRIBE_OPTIONS'));
        $this->getSimpleSelect('unsub', acym_translation('ACYM_DISPLAY_UNSUB_BUTTON'), $this->unsubButton, '0');
        $this->getText('unsubtext', acym_translation('ACYM_UNSUBSCRIBE_TEXT'), 'Unsubscribe');
        $this->getText('unsubredirect', acym_translation('ACYM_REDIRECT_LINK_UNSUB'));
        $this->end_controls_section();

        //Advanced option
        $this->startControlsSection('advanced_options', acym_translation('ACYM_ADVANCED_OPTIONS'));
        $this->getText('introtext', acym_translation('ACYM_INTRO_TEXT'));
        $this->getText('posttext', acym_translation('ACYM_POST_TEXT'));
        $this->getSimpleSelect('alignment', acym_translation('ACYM_ALIGNMENT'), $this->alignment, 'none');
        $this->getSimpleSelect('includejs', acym_translation('ACYM_MODULE_JS'), $this->includeJavascript, 'header');
        $this->getText('source', acym_translation('ACYM_SOURCE'), 'elementor_subscription_form');
        $this->end_controls_section();

        // Style zone
        $this->startControlsSection('global_options', acym_translation('ACYM_GLOBAL_OPTIONS'), \Elementor\Controls_Manager::TAB_STYLE);
        $this->getColor('background_color', acym_translation('ACYM_BACKGROUND_COLOR'));
        $this->getColor('text_color', acym_translation('ACYM_TEXT_COLOR'));
        $this->end_controls_section();

        $this->startControlsSection('button_options', acym_translation('ACYM_BUTTON'), \Elementor\Controls_Manager::TAB_STYLE);
        $this->getColor('button_background_color', acym_translation('ACYM_BACKGROUND_COLOR'));
        $this->getColor('button_text_color', acym_translation('ACYM_TEXT_COLOR'));
        $this->getColor('button_border_color', acym_translation('ACYM_BORDER_COLOR'));
        $this->getSimpleSelect('button_border_type', acym_translation('ACYM_BORDER_TYPE'), $this->borderTypes, 'solid');
        $this->getNumber('button_border_size', acym_translation('ACYM_BORDER_SIZE'));
        $this->getNumber('button_border_radius', acym_translation('ACYM_RADIUS'));
        $this->end_controls_section();
    }

    private function startControlsSection($option, $label, $type = null)
    {
        $this->start_controls_section(
            $option,
            [
                'label' => $label,
                'tab' => empty($type) ? \Elementor\Controls_Manager::TAB_CONTENT : $type,
            ]
        );
    }

    private function getSelect($option, $label, $values, $default, $multiple = false)
    {
        $this->add_control(
            $option,
            [
                'label' => $label,
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $values,
                'default' => $default,
                'multiple' => $multiple,
                'label_block' => true,
            ]
        );
    }

    private function getSimpleSelect($option, $label, $values, $default)
    {
        $this->add_control(
            $option,
            [
                'label' => $label,
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $values,
                'default' => $default,
                'label_block' => true,
            ]
        );
    }

    private function getText($option, $label, $default = '')
    {
        $data = [
            'label' => $label,
            'type' => \Elementor\Controls_Manager::TEXT,
            'input_type' => 'text',
            'label_block' => true,
        ];

        if (!empty($default)) $data['default'] = $default;

        $this->add_control(
            $option,
            $data
        );
    }

    private function getColor($option, $label, $default = '')
    {
        $data = [
            'label' => $label,
            'type' => \Elementor\Controls_Manager::COLOR,
            'global' => [
                'active' => false,
            ],
        ];

        if (!empty($default)) $data['default'] = $default;

        $this->add_control(
            $option,
            $data
        );
    }

    private function getNumber($option, $label, $default = '')
    {
        $data = [
            'label' => $label,
            'type' => \Elementor\Controls_Manager::NUMBER,
            'label_block' => false,
            'min' => 0,
        ];

        if (!empty($default)) $data['default'] = $default;

        $this->add_control(
            $option,
            $data
        );
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $params = new acymParameter($settings);
        $render = acym_renderForm($params);

        echo $render;
    }
}
