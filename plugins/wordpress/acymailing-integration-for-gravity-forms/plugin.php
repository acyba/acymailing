<?php

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
        $this->pluginDescription->features = '[]';
        $this->pluginDescription->description = '- Add AcyMailing lists on your forms';

        $this->propertyLabels = [
            'acymDisplayedList' => acym_translation('ACYM_DISPLAYED_LISTS'),
            'acymCheckedList' => acym_translation('ACYM_LISTS_CHECKED_DEFAULT'),
            'acymAutoSubList' => acym_translation('ACYM_AUTO_SUBSCRIBE_TO'),
        ];
    }

    public function onAcymInitWordpressAddons()
    {
        include_once 'customFieldGF.php';
        add_action('gform_field_standard_settings', [$this, 'subscriptionFormSettings'], 10, 2);
        add_action('gform_editor_js', [$this, 'subscriptionFormScript']);
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
