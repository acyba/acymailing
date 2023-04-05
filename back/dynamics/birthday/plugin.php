<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\FieldClass;

require_once __DIR__.DIRECTORY_SEPARATOR.'BirthdayAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'BirthdayCampaignType.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'BirthdayFollowup.php';

class plgAcymBirthday extends acymPlugin
{
    use BirthdayAutomationTriggers;
    use BirthdayCampaignType;
    use BirthdayFollowup;

    public function onAcymProcessFilter_birthday(&$query, $options, $num = null)
    {
        if ($options['plugin'] !== get_class($this)) return;

        $dateToCheck = $this->processDateToCheck($options);

        $fieldClass = new FieldClass();
        $birthdayField = $fieldClass->getOneById($options['field']);
        if (empty($birthdayField)) return;

        $query->join['birthday_field'.$num] = '#__acym_user_has_field AS uf'.$num.' ON uf'.$num.'.user_id = user.id';
        $query->where[] = 'uf'.$num.'.field_id = '.intval($birthdayField->id);
        $query->where[] = 'uf'.$num.'.value LIKE '.acym_escapeDB('%'.date_format($dateToCheck, '-m-d'));
    }

    public function getBirthdayField(&$availableFields)
    {
        $fieldClass = new FieldClass();
        $dateFields = $fieldClass->getFieldsByType(['date']);
        foreach ($dateFields as $oneField) {
            $availableFields[$oneField->id] = acym_translation($oneField->name);
        }
    }

    public function getJsonBirthdayField()
    {
        $availableFields = [];
        $this->getBirthdayField($availableFields);
        echo json_encode(['fields' => $availableFields]);
        exit;
    }
}
