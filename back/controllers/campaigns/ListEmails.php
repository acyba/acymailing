<?php

namespace AcyMailing\Controllers\Campaigns;

use AcyMailing\Classes\MailClass;


trait ListEmails
{
    public function welcome()
    {
        acym_setVar('layout', 'welcome');
        $data = [
            'cleartask' => 'welcome',
            'email_type' => MailClass::TYPE_WELCOME,
            'element_to_display' => lcfirst(acym_translation('ACYM_WELCOME_EMAILS')),
        ];

        $this->prepareWelcomeUnsubListing($data);
        $this->prepareToolbar($data);
        $this->prepareListingClasses($data);

        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    public function unsubscribe()
    {
        acym_setVar('layout', 'unsubscribe');
        $data = [
            'cleartask' => 'unsubscribe',
            'email_type' => MailClass::TYPE_UNSUBSCRIBE,
            'element_to_display' => lcfirst(acym_translation('ACYM_UNSUBSCRIBE_EMAILS')),
        ];

        $this->prepareWelcomeUnsubListing($data);
        $this->prepareToolbar($data);
        $this->prepareListingClasses($data);

        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    private function prepareWelcomeUnsubListing(&$data)
    {
        $this->getAllParamsRequest($data);
        $this->prepareEmailsListing($data, $data['email_type'], 'Mail');
    }
}
