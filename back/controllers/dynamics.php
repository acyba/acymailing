<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\PluginHelper;
use AcyMailing\Libraries\acymController;

class DynamicsController extends acymController
{
    public function replaceDummy()
    {
        $mailClass = new MailClass();
        $mailId = acym_getVar('int', 'mailId', 0);
        if (!empty($mailId)) {
            $email = $mailClass->getOneById($mailId);
        }

        if (empty($email)) {
            $email = new \stdClass();
            $email->id = 0;
            $email->name = '';
            $email->subject = '';
            $email->from_name = '';
            $email->from_email = '';
            $email->reply_to_name = '';
            $email->reply_to_email = '';
            $email->bcc = '';
            $email->links_language = '';
        }

        $language = acym_getVar('string', 'language', 'main');
        if (!empty($language)) {
            if ($language === 'main') {
                $language = $this->config->get('multilingual_default', ACYM_DEFAULT_LANGUAGE);
            }
            $email->links_language = $language;
        }


        $email->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
        $email->creator_id = acym_currentUserId();
        $email->thumbnail = '';
        $email->drag_editor = '1';
        $email->type = $mailClass::TYPE_STANDARD;
        $email->settings = '';
        $email->stylesheet = '';
        $email->attachments = '';

        // This is only the dynamic text/content code
        $email->body = acym_getVar('string', 'code', '', '', ACYM_ALLOWHTML);
        // This is the whole editor current content
        $email->previewBody = acym_getVar('string', 'previewBody', '', '', ACYM_ALLOWHTML);

        @acym_trigger('replaceContent', [&$email, false]);

        $userClass = new UserClass();
        $userEmail = acym_currentUserEmail();
        $user = $userClass->getOneByEmail($userEmail);

        if (empty($user)) {
            $user = new \stdClass();
            $user->email = $userEmail;
            $user->name = acym_currentUserName();
            $user->cms_id = acym_currentUserId();
            $user->confirmed = 0;
            $user->source = acym_isAdmin() ? 'Back-end' : 'Front-end';

            $userClass->checkVisitor = false;
            $user->id = $userClass->save($user);
        }

        @acym_trigger('replaceUserInformation', [&$email, &$user, false]);

        acym_sendAjaxResponse(
            '',
            [
                'content' => $email->body,
                'custom_view' => !empty($email->custom_view),
            ]
        );
    }

    public function trigger()
    {
        $plugin = acym_getVar('cmd', 'plugin', '');
        $trigger = acym_getVar('cmd', 'trigger', '');
        if (empty($plugin) || empty($trigger)) exit;

        $shortcode = acym_getVar('string', 'shortcode', '');

        $defaultValues = new \stdClass();

        $shortcode = trim($shortcode, '{}');
        $separatorPosition = strpos($shortcode, ':');
        if (false !== $separatorPosition) {
            $pluginSubType = substr($shortcode, 0, $separatorPosition);
            $shortcode = substr($shortcode, $separatorPosition + 1);
            $pluginHelper = new PluginHelper();
            $defaultValues = $pluginHelper->extractTag($shortcode);
            $defaultValues->defaultPluginTab = $pluginSubType;
        }

        if (empty((array)$defaultValues) && $trigger === 'insertionOptions') {
            $rawDefaultValues = $this->config->get('dcontent_default_'.$plugin);

            if (!empty($rawDefaultValues)) {
                $defaultValues = json_decode($rawDefaultValues, true);
                unset($defaultValues['id']);
                $defaultValues = (object)$defaultValues;
            }
        }

        acym_trigger($trigger, [$defaultValues], $plugin);
        exit;
    }
}
