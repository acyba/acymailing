<?php

use AcyMailing\Classes\ListClass;

if (!defined('ABSPATH')) {
    exit;
}

class GF_Field_Acy extends GF_Field
{
    public $type = 'acy';
    public $failed_validation;
    public $validation_message;

    /**
     * This function allows to customize the label in the editor of the field that we insert
     *
     * @return mixed
     */
    public function get_form_editor_field_title()
    {
        return acym_translation('ACYM_ACYMAILING_LISTS');
    }

    /**
     * This function allows to place the field in the editor, here for example the field will be placed in the advanced fields
     *
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
     *
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
        $id = (int)$this->id;
        $formId = $form['id'];

        $checkboxes = '';
        if (isset($this->acymDisplayedList)) {
            $listClass = new ListClass();
            $lists = $listClass->getAllForSelect(true, 0, true, true);

            if (!empty($lists)) {
                $acymCheckedList = $this->acymCheckedList ?? [];

                foreach ($this->acymDisplayedList as $listId) {
                    if (!empty($this->acymAutoSubList) && in_array($listId, $this->acymAutoSubList)) {
                        continue;
                    }

                    $uniqueId = 'gform_acy_list_sub_'.$formId.'_'.$id.'_'.$listId;
                    $checkboxes .= '<input type="checkbox" class="acy_list_checkbox_'.esc_attr($formId).'_'.esc_attr($id).'" id="'.esc_attr($uniqueId).'" value="'.intval($listId).'" '.checked(
                            in_array($listId, $acymCheckedList),
                            true,
                            false
                        ).'>';
                    $checkboxes .= '<label for="'.esc_attr($uniqueId).'" style="margin-left:5px;">'.esc_html($lists[$listId]).'</label><br>';
                }
            }
        }

        // It is THIS field, named input_{id}, that Gravity Forms will automatically detect and save
        $hiddenId = 'input_'.$formId.'_'.$id;
        $hiddenValue = esc_attr(implode(',', array_unique(array_merge((array)($this->acymAutoSubList ?? []), $acymCheckedList))));
        $input = '<input type="hidden" name="input_'.$id.'" id="'.$hiddenId.'" value="'.$hiddenValue.'">';
        $input .= '<br>'.$checkboxes;
        // Input hidden to access auto sub lists
        $hiddenAutoSubId = 'acy_hidden_lists_'.$formId.'_'.$id;
        $initAutoSubLists = array_unique(array_merge($this->acymAutoSubList ?? [], $acymCheckedList ?? []));
        $autoSubLists = implode(',', $initAutoSubLists);
        $input .= '<input type="hidden" name="acy_hidden_lists" id="'.esc_attr($hiddenAutoSubId).'" value="'.esc_attr($autoSubLists).'"><br>';

        // Script to populate hidden field with values to save with user selection and autosub values
        $input .= '<script>(function(){
                function sync(){
                    const ids = [];
                    document.querySelectorAll(\'.acy_list_checkbox_'.$formId.'_'.$id.':checked\')
                            .forEach(function(el){
                                ids.push(el.value); 
                             });
                    let tmpIds = [];
                    if(\'\' != document.getElementById(\''.$hiddenAutoSubId.'\').value){
                        tmpIds = document.getElementById(\''.$hiddenAutoSubId.'\').value.split(\',\');
                    }
                    let tmpLists = ids.concat(tmpIds).join(\',\');
                    document.getElementById(\''.$hiddenId.'\').value = tmpLists;
                }
                document.addEventListener(\'change\', function(e){
                    if (e.target.classList.contains(\'acy_list_checkbox_'.$formId.'_'.$id.'\')){
					    sync();	
                    }
                });
            })();</script>';

        return $input;
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
        return true;
    }

    // What GF will record in the entry (automatically called up upon submission)
    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead)
    {
        return is_array($value) ? implode(',', array_map('intval', $value)) : sanitize_text_field($value);
    }

    // Clear display in the entry details / notifications
    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen')
    {
        if (empty($value)) {
            return '';
        }
        $listClass = new ListClass();
        $lists = $listClass->getAllForSelect(true, 0, true, true);
        $names = [];
        foreach (array_filter(explode(',', $value)) as $id) {
            if (isset($lists[$id])) {
                $names[] = $lists[$id];
            }
        }

        return implode(', ', $names);
    }
}

GF_Fields::register(new GF_Field_Acy());
