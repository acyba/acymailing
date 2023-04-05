<?php

namespace AcyMailing\Controllers\Mails;

trait Automation
{
    public function deleteMailAutomation()
    {
        $mailClass = $this->currentClass;
        $mailId = acym_getVar('int', 'id', 0);

        if (!empty($mailId)) $mailClass->delete($mailId);
        exit;
    }

    public function duplicateMailAutomation()
    {
        $mailClass = $this->currentClass;
        $mailId = acym_getVar('int', 'id', 0);
        $prevMail = acym_getVar('int', 'previousId');

        if (!empty($prevMail)) $mailClass->delete($prevMail);

        if (empty($mailId)) {
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_NOT_FOUND', acym_translation('ACYM_ID')), [], false);
        }

        $newMail = $mailClass->duplicateMail($mailId, $mailClass::TYPE_AUTOMATION);

        if (empty($newMail)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_DUPLICATE_EMAIL'), [], false);
        }

        acym_sendAjaxResponse('', ['newMail' => $newMail]);
    }
}
