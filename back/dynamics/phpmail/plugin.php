<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymPhpmail extends acymPlugin
{
    const SENDING_METHOD_ID = 'phpmail';
    const SENDING_METHOD_NAME = 'PHP Mail Function';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_PHPMAIL_FUNCTION');
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        if ($isMailer) return;
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/phpmail.svg',
            'image_class' => 'acym__selection__card__image__smaller',
        ];
    }
}
