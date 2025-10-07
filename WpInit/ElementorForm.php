<?php

namespace AcyMailing\WpInit;

use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\ListClass;

class ElementorForm extends \ElementorPro\Modules\Forms\Classes\Action_Base
{
    public function get_name()
    {
        return 'acymailing';
    }

    public function get_label()
    {
        return 'AcyMailing';
    }

    public function register_settings_section($widget)
    {
        // Check if an id is provided because this function is called many times sometimes there is no id (and not the rest: settings, form_fields,...)
        // So when we try to access fields data that is not currently existing a fatal error appears
        if ($widget->get_id()) {
            $fields = ['' => ''];
            foreach ($widget->get_data('settings')['form_fields'] as $field) {
                if (!isset($field['custom_id']) || !isset($field['field_label'])) {
                    continue;
                }
                $fields[$field['custom_id']] = $field['field_label'];
            }

            $widget->start_controls_section('section_acymailing', [
                'label' => 'AcyMailing',
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]);

            $listsClass = new ListClass();
            $lists = $listsClass->getAllWithIdName();

            $widget->add_control('acym_selectLists', [
                'label' => acym_translation('ACYM_LISTS_SUMMARY'),
                'label_block' => true,
                'description' => acym_translation('ACYM_SELECT_LISTS_USERS_SUBSCRIBE'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $lists,
                'multiple' => true,
                'default' => '',
            ]);

            $widget->add_control('acym_nameField', [
                'label' => acym_translation('ACYM_NAME_SUMMARY'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => $fields,
            ]);

            $widget->add_control('acym_emailField', [
                'label' => acym_translation('ACYM_EMAIL'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => $fields,
                'description' => acym_translation('ACYM_RELOAD_PAGE_TO_SEE_NEW_FIELDS'),
            ]);

            $widget->add_control('acym_confirmUsers', [
                'label' => acym_translation('ACYM_IMPORT_USERS_AS_CONFIRMED'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => acym_translation('ACYM_YES'),
                'label_off' => acym_translation('ACYM_NO'),
                'default' => true,
            ]);

            $widget->end_controls_section();
        }
    }

    public function on_export($element)
    {
        unset($element['acym_confirmUsers']);
        unset($element['acym_selectLists']);
        unset($element['acym_nameField']);
        unset($element['acym_emailField']);
    }

    public function run($record, $ajax_handler)
    {
        $settings = $record->get('form_settings');
        $data = $record->get('sent_data');

        if (empty($data[$settings['acym_emailField']])) {
            return;
        }

        $userClass = new UserClass();
        $newUser = new \stdClass();

        $newUser->name = $data[$settings['acym_nameField']];
        $newUser->email = $data[$settings['acym_emailField']];
        $newUser->creation_date = date('Y-m-d H:i:s');
        $newUser->confirmed = $settings['acym_confirmUsers'] === 'yes';

        $user = $userClass->getOneByEmail($newUser->email);
        if (!empty($user)) {
            $newUser->id = $user->id;
        }

        // We do that because Elementor submit the form via ajax and in ajax mode WordPress always return true to the function is_admin()
        $config = acym_config();
        if ($config->get('require_confirmation', 1) == 1) {
            $userClass->forceConfAdmin = true;
        }

        $userId = $userClass->save($newUser);

        if (!empty($settings['acym_selectLists'])) {
            $userClass->subscribe($userId, $settings['acym_selectLists'], true, true);
        }
    }
}
