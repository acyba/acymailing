<?php

use AcyMailing\Classes\UserClass;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\ListClass;

class plgAcymGravityforms extends acymPlugin
{
    private $propertyLabels;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('gravityforms/gravityforms.php');

        $this->pluginDescription->name = 'Gravity Forms';
        $this->pluginDescription->category = 'Subscription system';
        $this->pluginDescription->description = '- Add AcyMailing lists on your forms';

        $this->propertyLabels = [
            'acymDisplayedList' => acym_translation('ACYM_DISPLAYED_LISTS'),
            'acymCheckedList' => acym_translation('ACYM_LISTS_CHECKED_DEFAULT'),
            'acymAutoSubList' => acym_translation('ACYM_AUTO_SUBSCRIBE_TO'),
        ];
    }

    public function onAcymInitWordpressAddons()
    {
        include_once __DIR__.DS.'customFieldGF.php';
        add_action('gform_field_standard_settings', [$this, 'subscriptionFormSettings'], 10, 2);
        add_action('gform_editor_js', [$this, 'subscriptionFormScript']);
        add_action('gform_after_submission', [$this, 'onFormProcess'], 10, 2);
    }

    public function onFormProcess($entry, $form)
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
            acym_logError('The email field is empty or there is no email field in form with ID '.$entry['form_id'], 'gravityforms');

            return;
        }

        $hiddenLists = acym_getVar('string', 'acy_hidden_lists', '');
        $checkedLists = acym_getVar('array', 'acy_list_sub', []);

        $hiddenLists = empty($hiddenLists) ? [] : explode(',', $hiddenLists);
        acym_arrayToInteger($hiddenLists);

        $lists = array_merge($hiddenLists, $checkedLists);
        $lists = array_unique($lists);

        $userClass = new UserClass();
        $alreadyExists = $userClass->getOneByEmail($user->email);

        if (!empty($alreadyExists->id)) {
            $user->id = $alreadyExists->id;
        }

        $isNew = empty($user->id);

        if ($isNew) {
            $user->source = 'Gravity forms - form Id: '.$form['id'].' on url'.$entry['source_url'];
        }

        $user->id = $userClass->save($user);
        if (!empty($userClass->errors)) {
            acym_logError('Error while subscribing user '.$user->email.' to lists '.implode(',', $lists)."\n Error: ".json_encode($userClass->errors), 'gravityforms');
        }

        if (!$lists) {
            return;
        }

        $subscribed = $userClass->subscribe($user->id, $lists);

        if (!$subscribed) {
            acym_logError('Error while subscribing user '.$user->email.' to lists '.implode(',', $lists), 'gravityforms');
        }

        $userClass->sendNotification(
            $user->id,
            $isNew ? 'acy_notification_create' : 'acy_notification_subform'
        );
    }

    public function getListsFormated()
    {
        $listClass = new ListClass();

        return $listClass->getAllWithIdName();
    }

    private function getSelectMultiple($propertyName, $label, $lists)
    {
        $data = [
            'property_name' => $propertyName,
            'label' => $label,
            'lists' => $lists,
        ];
        echo $this->includeView('select_multiple', $data, __DIR__);
    }

    public function subscriptionFormSettings($position, $form_id)
    {
        $lists = $this->getListsFormated();
        if ($position == 5 && !empty($lists)) {
            foreach ($this->propertyLabels as $property => $label) {
                $this->getSelectMultiple($property, $label, $lists);
            }
        }
    }

    public function subscriptionFormScript()
    {
        ?>
		<script type='text/javascript'>
            function saveAcyListSelectMultiple(propertyName, select) {
                let values = [];
                jQuery(select).find('option:selected').each(function () {
                    values.push(jQuery(this).val());
                });
                SetFieldProperty(propertyName, values);
            }

            function setAcymFiedsOnLoad(propertyName) {
                if (undefined === field[propertyName]) return;
                jQuery('#' + propertyName).find('option').each(function () {
                    if (field[propertyName].indexOf(jQuery(this).val()) !== -1) jQuery(this).attr('selected', 'true');
                });
            }


            <?php
            foreach ($this->propertyLabels as $property => $label) {
            ?>
            fieldSettings.acy += ', .acym_<?php echo $property; ?>_setting';
            <?php
            }
            ?>

            //adding setting to fields of type "text"
            fieldSettings.acy += ', .acym_displayed_lists_setting';

            //binding to the load field settings event to initialize the checkbox
            jQuery(document).on('gform_load_field_settings', function (event, field, form) {
                <?php
                foreach ($this->propertyLabels as $property => $label) {
                    echo 'setAcymFiedsOnLoad("'.$property.'");';
                }
                ?>
            });
		</script>
        <?php
    }
}
