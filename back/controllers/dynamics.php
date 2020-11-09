<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\PluginHelper;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Libraries\acymController;

class DynamicsController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultTask('popup');
    }

    public function popup()
    {
        $isAutomation = acym_getVar('string', 'automation');
        $mailId = acym_getVar('int', 'mail_id', 0);

        $js = 'function setTag(tagvalue, element){
                    var $allRows = jQuery(".acym__listing__row__popup");
                    $allRows.removeClass("selected_row");
                    element.addClass("selected_row");
                    window.document.getElementById(\'dtextcode\').value = tagvalue;
               }';
        $js .= 'try{window.parent.previousSelection = window.parent.getPreviousSelection(); }catch(err){window.parent.previousSelection=false; }';

        acym_addScript(true, $js);

        $tab = new TabHelper();


        $plugins = acym_trigger('dynamicText', [$mailId]);

        $data = [
            "type" => acym_getVar('string', 'type', 'news'),
            "plugins" => $plugins,
            "tab" => $tab,
            "automation" => $isAutomation,
            'mail_id' => $mailId,
        ];

        parent::display($data);
    }

    public function replaceDummy()
    {
        $mailId = acym_getVar('int', 'mailId', 0);
        if (!empty($mailId)) {
            $mailClass = new MailClass();
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
        $email->library = '0';
        $email->type = 'standard';
        $email->template = '0';
        $email->settings = '';
        $email->stylesheet = '';
        $email->attachments = '';

        $email->body = acym_getVar('string', 'code', '', '', ACYM_ALLOWHTML);

        @acym_trigger('replaceContent', [&$email]);

        $userClass = new UserClass();
        $userEmail = acym_currentUserEmail();
        $user = $userClass->getOneByEmail($userEmail);

        if (empty($user)) {
            $user = new \stdClass();
            $user->email = acym_currentUserEmail();
            $user->name = acym_currentUserName();
            $user->cms_id = acym_currentUserId();
            $user->confirmed = 0;
            $user->source = 'Back-end';

            $userClass->checkVisitor = false;
            $user->id = $userClass->save($user);
        }

        @acym_trigger('replaceUserInformation', [&$email, &$user, false]);

        echo json_encode(['content' => $email->body]);
        exit;
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

        acym_trigger($trigger, [$defaultValues], $plugin);

        exit;
    }
}
