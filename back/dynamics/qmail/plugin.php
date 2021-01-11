<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymQmail extends acymPlugin
{
    const SENDING_METHOD_ID = 'qmail';
    const SENDING_METHOD_NAME = 'Qmail';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = self::SENDING_METHOD_NAME;
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        if ($isMailer) return;
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/qmail.gif',
        ];
    }
}
