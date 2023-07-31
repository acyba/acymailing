<?php

trait UserBirthday
{
    public function onAcymGetPluginField(&$availablePlugins)
    {
        if (ACYM_CMS === 'joomla') {
            $availablePlugins[get_class($this)] = acym_translation('ACYM_JOOMLA_USERS');
        }
    }

    public function getBirthdayField(&$availableFields)
    {
        if (!ACYM_J37) {
            return;
        }
        $query = 'SELECT * FROM #__fields WHERE context = "com_users.user" AND type = "calendar"';
        $customFields = acym_loadObjectList($query);
        foreach ($customFields as $customField) {
            $availableFields[$customField->id] = $customField->title;
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
