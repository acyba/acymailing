<?php

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;

class GF_Field_Acy extends GF_Field
{

    public $type = 'acy';
    public $failed_validation;
    public $validation_message;

    /**
     * This function allows to customize the label in the editor of the field that we insert
     * @return mixed
     */
    public function get_form_editor_field_title()
    {
        return acym_translation('ACYM_ACYMAILING_LISTS');
    }

    /**
     * This function allows to place the field in the editor, here for example the field will be placed in the advanced fields
     * @return array
     */
    public function get_form_editor_button()
    {
        return [
            'group' => 'advanced_fields',
            'text' => $this->get_form_editor_field_title(),
        ];
    }

    /**
     * Here we can list all the parameters we want for our field, when you click on it in the editor
     * @return array
     */
    public function get_form_editor_field_settings()
    {
        return [
            'label_setting',
        ];
    }

    /**
     * @param        $form
     * @param string $value
     * @param null   $entry
     *
     *  This function will generate the html code for the field in the editor and in the front-end
     *
     * @return string
     */
    public function get_field_input($form, $value = '', $entry = null)
    {
        //We get the hidden lists
        $autoSubLists = isset($this->acymAutoSubList) ? implode(',', $this->acymAutoSubList) : '';
        $inputHidden = '<input type="hidden" name="acy_hidden_lists" value="'.$autoSubLists.'">';

        //For the two others parameters: the lists that we displays and the one that are checked
        $checkboxes = '';
        if (isset($this->acymDisplayedList)) {
            $listClass = new ListClass();
            $lists = $listClass->getAllForSelect();

            if (empty($lists)) return $inputHidden;

            $acymCheckedList = isset($this->acymCheckedList) ? $this->acymCheckedList : [];

            //We generate the checkboxes
            foreach ($this->acymDisplayedList as $listId) {
                if (!empty($this->acymAutoSubList) && in_array($listId, $this->acymAutoSubList)) continue;
                $checked = in_array($listId, $acymCheckedList) ? 'checked' : '';
                $uniqueId = 'gform_acy_list_sub_'.$listId;
                //this line allows to know if we are on the editor or the front-end
                $isDisabled = $this->is_form_editor() ? 'disabled' : '';
                $checkboxes .= '<input type="checkbox" id="'.$uniqueId.'" name="acy_list_sub[]" value="'.$listId.'" '.$checked.' '.$isDisabled.'>';
                $checkboxes .= '<label for="'.$uniqueId.'">'.$lists[$listId].'</label> <br>';
            }
        }

        return '<br>'.$checkboxes.$inputHidden;
    }

    /**
     * @param $value
     * @param $form
     *  This function is triggered when the form is validated in the front-end, so here we can create and subscribe our user
     *
     * @return bool
     */
    public function validate($value, $form)
    {
        //first we get the email input name to get its value
        $user = new stdClass();
        foreach ($form['fields'] as $field) {
            if ($field->type == 'email') {
                $user->email = acym_getVar('string', 'input_'.$field->id, '');
                break;
            }
        }
        //If we don't find it we do nothing
        if (empty($user->email) || !acym_isValidEmail($user->email, true)) {
            $this->failed_validation = true;
            $this->validation_message = acym_translation('ACYM_YOU_DONT_HAVE_EMAIL_FIELD_OR_EMAIL_FIELD_EMPTY');

            return true;
        }

        $hiddenLists = acym_getVar('string', 'acy_hidden_lists', '');
        $checkedLists = acym_getVar('array', 'acy_list_sub', []);

        $userClass = new UserClass();
        $alreadyExists = $userClass->getOneByEmail($user->email);

        if (!empty($alreadyExists->id)) {
            $user->id = $alreadyExists->id;
        }

        $isNew = empty($user->id);

        if ($isNew) $user->source = 'Gravity forms - form Id: '.$form['id'];

        $user->id = $userClass->save($user);
        if (!empty($userClass->errors)) $this->validation_message = $userClass->errors;

        $hiddenLists = empty($hiddenLists) ? [] : explode(',', $hiddenLists);
        acym_arrayToInteger($hiddenLists);

        $lists = array_merge($hiddenLists, $checkedLists);
        $lists = array_unique($lists);
        $subscribed = $userClass->subscribe($user->id, $lists);

        $config = acym_config();
        if (empty($user->confirmed) && $config->get('require_confirmation', 1) == 1) {
            if ($userClass->confirmationSentSuccess) {
                $msg = 'ACYM_CONFIRMATION_SENT';
            } else {
                $msg = $userClass->confirmationSentError;
            }
        } else {
            if ($subscribed) {
                $msg = 'ACYM_SUBSCRIPTION_OK';
            } else {
                $msg = 'ACYM_ALREADY_SUBSCRIBED';
            }
        }

        $userClass->sendNotification(
            $user->id,
            $isNew ? 'acy_notification_create' : 'acy_notification_subform'
        );

        // Replace tags inside the $msg and redirect link
        $replace = [];
        foreach ($user as $oneProp => $oneVal) {
            $replace['{user:'.$oneProp.'}'] = $oneVal;
        }

        $msg = str_replace(array_keys($replace), $replace, acym_translation($msg));

        $this->validation_message = $msg;

        return true;
    }
}

GF_Fields::register(new GF_Field_Acy());
