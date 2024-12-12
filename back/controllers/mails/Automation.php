<?php

namespace AcyMailing\Controllers\Mails;

use AcyMailing\Classes\MailClass;

trait Automation
{
    public function deleteMailAutomation()
    {
        $mailClass = new MailClass();
        $mailId = acym_getVar('int', 'id', 0);

        if (!empty($mailId)) $mailClass->delete($mailId);
        exit;
    }

    public function duplicateMailAutomation()
    {
        $mailClass = new MailClass();
        $mailId = acym_getVar('int', 'id', 0);
        $prevMail = acym_getVar('int', 'previousId');

        if (!empty($prevMail)) $mailClass->delete($prevMail);

        if (empty($mailId)) {
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_NOT_FOUND', acym_translation('ACYM_ID')), [], false);
        }

        $newMail = $mailClass->duplicateMail($mailId, MailClass::TYPE_AUTOMATION);

        if (empty($newMail)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_DUPLICATE_EMAIL'), [], false);
        }

        acym_sendAjaxResponse('', ['newMail' => $newMail]);
    }
}
