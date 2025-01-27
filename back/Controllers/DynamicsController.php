<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\PluginHelper;
use AcyMailing\Core\AcymController;

class DynamicsController extends AcymController
{
    public function replaceDummy()
    {
        $mailId = acym_getVar('int', 'mailId', 0);
        $code = acym_getVar('string', 'code', '', '', ACYM_ALLOWRAW);
        $previewBody = acym_getVar('string', 'previewBody', '', '', ACYM_ALLOWRAW);

        $pluginHelper = new PluginHelper();
        $email = $pluginHelper->createDummyEmailObject($mailId, $code, $previewBody);

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

        $triggerParams = $this->getTriggerParams($plugin, $trigger);
        acym_trigger($trigger, $triggerParams, $plugin);
        exit;
    }

    private function getTriggerParams(string $plugin, string $trigger): array
    {
        $shortcode = acym_getVar('string', 'shortcode', '');
        $shortcode = trim($shortcode, '{}');

        $separatorPosition = strpos($shortcode, ':');
        if (false !== $separatorPosition) {
            $pluginSubType = substr($shortcode, 0, $separatorPosition);
            $shortcode = substr($shortcode, $separatorPosition + 1);
            $pluginHelper = new PluginHelper();
            $defaultValues = $pluginHelper->extractTag($shortcode);
            $defaultValues->defaultPluginTab = $pluginSubType;

            return [$defaultValues];
        }

        if ($trigger === 'insertionOptions') {
            $rawDefaultValues = $this->config->get('dcontent_default_'.$plugin);

            if (!empty($rawDefaultValues)) {
                $defaultValues = json_decode($rawDefaultValues, true);
                unset($defaultValues['id']);
                unset($defaultValues['from']);
                unset($defaultValues['to']);
                $defaultValues = (object)$defaultValues;

                return [$defaultValues];
            }
        }

        return [];
    }
}
