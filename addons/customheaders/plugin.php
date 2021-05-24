<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymCustomheaders extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();

        $this->settings = [
            'headers' => [
                'type' => 'multikeyvalue',
                'label' => 'ACYM_CUSTOM_HEADERS',
                'info' => 'ACYM_CUSTOM_EMAIL_HEADERS_DESC',
                'value' => '',
            ],
        ];
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        if (!method_exists($email, 'addCustomHeader')) return;

        $headers = $this->getParam('headers');
        if (empty($headers)) return;

        $headers = json_decode($headers);
        foreach ($headers as $key => $value) {
            if (!is_string($key) || !is_string($value)) continue;
            $email->addCustomHeader($key, $value);
        }
    }
}
