<?php

use AcyMailing\Core\AcymPlugin;

class plgAcymCustomheaders extends AcymPlugin
{
    public function __construct()
    {
        parent::__construct();

        $this->cms = 'WordPress';
        $this->pluginDescription->name = 'Custom headers';
        $this->pluginDescription->category = 'Content management';
        $this->pluginDescription->description = '- Add custom email headers to your sent emails';

        $this->settings = [
            'headers' => [
                'type' => 'multikeyvalue',
                'label' => 'ACYM_CUSTOM_HEADERS',
                'info' => 'ACYM_CUSTOM_EMAIL_HEADERS_DESC',
                'value' => '',
            ],
        ];
    }

    public function replaceUserInformation(object &$email, ?object &$user, bool $send = true): void
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
