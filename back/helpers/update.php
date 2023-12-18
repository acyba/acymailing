<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\ActionClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\OverrideClass;
use AcyMailing\Classes\PluginClass;
use AcyMailing\Classes\RuleClass;
use AcyMailing\Classes\StepClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Libraries\acymObject;

class UpdateHelper extends acymObject
{
    const FIRST_EMAIL_NAME_KEY = 'ACYM_FIRST_EMAIL_NAME';

    private $bounceVersion = 3;
    public $errors = [];
    public $fromLevel;
    public $fromVersion;

    public function installBounceRules()
    {
        $ruleClass = new RuleClass();
        if ($ruleClass->getOrderingNumber() > 0) {
            return;
        }
        $replyTo = $this->config->get('replyto_email');
        $bounce = $this->config->get('bounce_email');
        $from = $this->config->get('from_email');

        $forwardEmail = $replyTo != $bounce ? $replyTo : $from;
        if (empty($forwardEmail)) {
            $forwardEmail = acym_currentUserEmail();
        }

        $forwardEmail = str_replace('"', '', $forwardEmail);

        $query = "INSERT INTO `#__acym_rule` (`id`, `name`, `ordering`, `regex`, `executed_on`, `action_message`, `action_user`, `active`, `increment_stats`, `execute_action_after`) VALUES ";
        $query .= "(1, 'ACYM_LIST_UNSUBSCRIBE_HANDLING', 1, 'Please unsubscribe user ID \\\\d+', '[\"body\"]', '[\"delete_message\"]', '[\"unsubscribe_user\"]', 1, 0, 0),";
        $query .= "(2, 'ACYM_SUPPRESSION_LIST', 2, 'suppression list', '[\"body\"]', '[\"delete_message\"]', '[\"unsubscribe_user\",\"block_user\",\"empty_queue_user\"]', 1, 1, 0),";
        $query .= "(3, 'ACYM_ACTION_REQUIRED', 3, 'action *requ|verif', '[\"subject\"]', '{\"0\":\"delete_message\",\"1\":\"forward_message\",\"forward_to\":\"".$forwardEmail."\"}', '[]', 1, 0, 0),";
        $query .= "(4, 'ACYM_ACKNOWLEDGMENT_RECEIPT_SUBJECT', 4, '(out|away) *(of|from)|vacation|vacanze|vacaciones|holiday|absen|congés|recept|acknowledg|thank you for|Auto *Response|Incident|Automati', '[\"subject\"]', '[\"delete_message\"]', '[]', 1, 0, 0),";
        $query .= "(5, 'ACYM_FEEDBACK_LOOP', 5, 'feedback|staff@hotmail.com|complaints@.{0,15}email-abuse.amazonses.com|complaint about message', '[\"senderInfo\",\"subject\"]', '[\"save_message\",\"delete_message\"]', '[\"unsubscribe_user\"]', 1, 0, 0),";
        $query .= "(6, 'ACYM_FEEDBACK_LOOP_BODY', 6, 'Feedback-Type.{1,5}abuse', '[\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"unsubscribe_user\"]', 1, 1, 0),";
        $query .= "(7, 'ACYM_MAILBOX_FULL', 7, '((mailbox|mailfolder|storage|quota|space|inbox) *(is)? *(over)? *(exceeded|size|storage|allocation|full|quota|maxi))|status(-code)? *(:|=)? *5.2.2|quota-issue|not *enough.{1,20}space|((over|exceeded|full|exhausted) *(allowed)? *(mail|storage|quota))', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(8, 'ACYM_BLOCKED_GOOGLE_GROUPS', 8, 'message *rejected *by *Google *Groups', '[\"body\"]', '[\"delete_message\"]', '[]', 1, 1, 0),";
        $query .= "(9, 'ACYM_REJECTED', 9, 'rejected *your *message|email *provider *rejected *it', '[\"body\"]', '[\"delete_message\"]', '[]', 1, 1, 0),";
        $query .= "(10, 'ACYM_MAILBOX_DOESNT_EXIST_1', 10, '(Invalid|no such|unknown|bad|des?activated|inactive|unrouteable) *(mail|destination|recipient|user|address|person)|bad-mailbox|inactive-mailbox|not listed in.{1,20}directory|RecipNotFound|(user|mailbox|address|recipients?|host|account|domain) *(is|has been)? *(error|disabled|failed|unknown|unavailable|not *(found|available)|.{1,30}inactiv)|no *mailbox *here|user does.?n.t have.{0,30}account', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(11, 'ACYM_MESSAGE_BLOCKED_RECIPIENTS', 11, 'blocked *by|block *list|look(ed)? *like *spam|spam-related|spam *detected| CXBL | CDRBL | IPBL | URLBL |(unacceptable|banned|offensive|filtered|blocked|unsolicited) *(content|message|e?-?mail)|service refused|(status(-code)?|554) *(:|=)? *5.7.1|administratively *denied|blacklisted *IP|policy *reasons|rejected.{1,10}spam|junkmail *rejected|throttling *constraints|exceeded.{1,10}max.{1,40}hour|comply with required standards|421 RP-00|550 SC-00|550 DY-00|550 OU-00', '[\"body\"]', '{\"0\":\"delete_message\",\"1\":\"forward_message\",\"forward_to\":\"".$forwardEmail."\"}', '[]', 1, 1, 0),";
        $query .= "(12, 'ACYM_MAILBOX_DOESNT_EXIST_2', 12, 'status(-? ?code)? *(:|=)? *(550)? *5.(1.[1-6]|0.0|4.[0123467])|recipient *address *rejected|does *not *like *recipient|recipient *unknown *to *address|email *account *that *you *tried *to *reach *is *disabled', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(13, 'ACYM_DOMAIN_NOT_EXIST', 13, 'No.{1,10}MX *(record|host)|host *does *not *receive *any *mail|bad-domain|connection.{1,10}mail.{1,20}fail|domain.{1,10}not *exist|fail.{1,10}establish *connection', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(14, 'ACYM_TEMPORARY_FAILURES', 14, 'has.*been.*delayed|delayed *mail|message *delayed|message-expired|temporar(il)?y *(failure|unavailable|disable|offline|unable)|deferred|delayed *([0-9]*) *(hour|minut)|possible *mail *loop|too *many *hops|delivery *time *expired|Action.php: *delayed|status(-code)? *(:|=)? *4.4.6|will continue to be attempted|unable to deliver in.*Status: 4.4.7', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(15, 'ACYM_FAILED_PERM', 15, 'failed *permanently|permanent.{1,20}(failure|error)|not *accepting *(any)? *mail|does *not *exist|no *valid *route|delivery *failure', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(16, 'ACYM_ACKNOWLEDGMENT_RECEIPT_BODY', 16, 'vacanc|holiday|vacation|absen|urlaub|ferie|feriado|vacanz|vacaciones|(out of|not in|outside)( *the)? *office', '[\"body\"]', '[\"delete_message\"]', '[]', 1, 0, 0),";
        $query .= "(17, 'ACYM_FINAL_RULE', 17, '.', '[\"senderInfo\",\"subject\"]', '{\"0\":\"delete_message\",\"1\":\"forward_message\",\"forward_to\":\"".$forwardEmail."\"}', '[]', 1, 0, 0);";

        acym_query($query);

        $this->config->save(['bounceVersion' => $this->bounceVersion]);
    }

    //__START__joomla_

    /**
     * Add update site on Joomla to handle auto-update
     */
    public function addUpdateSite($extension = null)
    {
        $newConfig = new \stdClass();
        $newConfig->website = ACYM_LIVE;
        $this->config->save($newConfig);

        // Init extension information
        $object = new \stdClass();
        $object->enabled = 1;
        $object->type = 'extension';
        $object->location = ACYM_UPDATEME_API_URL.'public/updatexml/';

        if (empty($extension)) {
            $object->location .= 'component?extension=acymailing&cms=joomla&version=latest&level={__LEVEL__}';
            $type = 'component';
            $folder = '';
            $element = 'com_acym';

        } else {
            if (strpos($extension, 'mod_') === 0) {
                $type = 'module';
                $folder = '';
                $element = $extension;
            } else {
                $extension = explode('_', $extension);
                $type = 'plugin';
                $folder = $extension[1];
                $element = $extension[2];
            }

            $object->location .= 'extension?version={__VERSION__}&type='.$type.'&folder='.$folder.'&element='.$element;
        }

        // Get the extension
        $extension = acym_loadObject(
            'SELECT `extension_id`, `name` 
                FROM #__extensions 
                WHERE `element` = '.acym_escapeDB($element).' 
                    AND type = '.acym_escapeDB($type).'
                    AND folder = '.acym_escapeDB($folder)
        );
        if (empty($extension)) return false;

        if ($extension->name === 'Acym') {
            $extension->name = 'AcyMailing';
        }
        $object->name = $extension->name;

        // Delete the previous update if any, then add the new one
        acym_query('DELETE FROM #__updates WHERE extension_id = '.intval($extension->extension_id));

        // Test for the presence of acym in database.
        $update_site_id = acym_loadResult('SELECT update_site_id FROM #__update_sites WHERE `type` = "extension" AND `name` = '.acym_escapeDB($extension->name));
        if (empty($update_site_id)) {
            $update_site_id = acym_insertObject('#__update_sites', $object);
        } else {
            $object->update_site_id = $update_site_id;
            acym_updateObject('#__update_sites', $object, 'update_site_id');
        }

        if (empty($update_site_id)) {
            return false;
        }

        $query = 'INSERT IGNORE INTO #__update_sites_extensions (update_site_id, extension_id) values ('.intval($update_site_id).','.intval($extension->extension_id).')';
        acym_query($query);

        return true;
    }

    public function installLanguages()
    {
        $siteLanguages = acym_getLanguages();
        if (!empty($siteLanguages[ACYM_DEFAULT_LANGUAGE])) {
            unset($siteLanguages[ACYM_DEFAULT_LANGUAGE]);
        }

        $installedLanguages = array_keys($siteLanguages);
        if (empty($installedLanguages) || !class_exists('\AcyMailing\Helpers\UpdatemeHelper')) return;

        ob_start();
        $languagesContent = UpdatemeHelper::call('public/download/translations?version=latest&codes='.implode(',', $installedLanguages));
        $warnings = ob_get_clean();
        if (!empty($warnings) && acym_isDebug()) {
            acym_enqueueMessage($warnings, 'warning');
        }

        if (empty($languagesContent) || $languagesContent['status'] === 'error') {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_LOAD_LANGUAGES'), 'error');

            return;
        }

        $decodedLanguages = $languagesContent['translations'];

        $success = [];
        $error = [];
        $errorLoad = [];

        foreach ($decodedLanguages as $code => $content) {
            if (empty($content)) {
                $errorLoad[] = $code;
                continue;
            }

            $path = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.'.ACYM_LANGUAGE_FILE.'.ini';
            if (acym_writeFile($path, $content)) {
                $this->installBackLanguages($code);
                $success[] = $code;
            } else {
                $error[] = acym_translationSprintf('ACYM_FAIL_SAVE_FILE', $path);
            }
        }

        if (!empty($success)) acym_enqueueMessage(acym_translationSprintf('ACYM_TRANSLATION_INSTALLED', implode(', ', $success)));
        if (!empty($error)) acym_enqueueMessage($error, 'error');
        if (!empty($errorLoad)) acym_enqueueMessage(acym_translationSprintf('ACYM_ERROR_LOAD_LANGUAGE', implode(', ', $errorLoad)), 'warning');
    }
    //__END__joomla_

    // Only Joomla : translates the Acy menus on back-end and Joomla menus
    public function installBackLanguages($onlyCode = '')
    {
        if (ACYM_CMS != 'joomla') return;

        $menuStrings = [
            'ACYM_DASHBOARD',
            'ACYM_SUBSCRIBERS',
            'ACYM_CUSTOM_FIELDS',
            'ACYM_LISTS',
            'ACYM_SEGMENTS',
            'ACYM_EMAILS',
            'ACYM_TEMPLATES',
            'ACYM_AUTOMATION',
            'ACYM_QUEUE',
            'ACYM_STATISTICS',
            'ACYM_BOUNCE_HANDLING',
            'ACYM_ADD_ONS',
            'ACYM_CONFIGURATION',
            'ACYM_MENU_PROFILE',
            'ACYM_MENU_PROFILE_DESC',
            'ACYM_MENU_ARCHIVE',
            'ACYM_MENU_ARCHIVE_DESC',
            'ACYM_MENU_LISTS',
            'ACYM_MENU_LISTS_DESC',
            'ACYM_MENU_SUBSCRIBERS',
            'ACYM_MENU_SUBSCRIBERS_DESC',
            'ACYM_MENU_CAMPAIGNS',
            'ACYM_MENU_CAMPAIGNS_DESC',
            'ACYM_SUBSCRIPTION_FORMS',
            'ACYM_EMAILS_OVERRIDE',
        ];

        $siteLanguages = empty($onlyCode) ? array_keys(acym_getLanguages()) : [$onlyCode];

        foreach ($siteLanguages as $code) {

            $path = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.com_acym.ini';
            if (!file_exists($path)) continue;

            $content = file_get_contents($path);
            if (empty($content)) continue;

            // The first key is to translate "Acym" into "AcyMailing blabla" in the Joomla Extension manager
            // The second key is to translate "com_acym" into "AcyMailing" in the Joomla global configuration page
            // DON'T CHANGE THE KEY !!
            $menuFileContent = 'ACYM="AcyMailing"'."\r\n";
            $menuFileContent .= 'COM_ACYM="AcyMailing"'."\r\n";
            $menuFileContent .= 'COM_ACYM_CONFIGURATION="AcyMailing"'."\r\n";

            foreach ($menuStrings as $oneString) {
                preg_match('#[^_]'.$oneString.'="(.*)"#i', $content, $matches);
                if (empty($matches[1])) continue;

                $menuFileContent .= $oneString.'="'.$matches[1].'"'."\r\n";
            }

            $menuPath = ACYM_ROOT.'administrator'.DS.'language'.DS.$code.DS.$code.'.com_acym.sys.ini';

            if (!acym_writeFile($menuPath, $menuFileContent)) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_FAIL_SAVE_FILE', $menuPath), 'error');
            }
        }
    }

    public function installFields()
    {
        $query = "INSERT IGNORE INTO #__acym_field (`id`, `name`, `type`, `value`, `active`, `default_value`, `required`, `ordering`, `option`, `core`, `backend_edition`, `backend_listing`, `frontend_edition`, `frontend_listing`, `access`, `namekey`) VALUES 
    (1, 'ACYM_NAME', 'text', NULL, 1, NULL, 0, 1, '{\"error_message\":\"\",\"error_message_invalid\":\"\",\"size\":\"\",\"rows\":\"\",\"columns\":\"\",\"format\":\"\",\"custom_text\":\"\",\"css_class\":\"\",\"authorized_content\":{\"0\":\"all\",\"regex\":\"\"}}', 1, 1, 1, 1, 1, 'all', 'acym_name'), 
    (2, 'ACYM_EMAIL', 'text', NULL, 1, NULL, 1, 2, '{\"error_message\":\"\",\"error_message_invalid\":\"\",\"size\":\"\",\"rows\":\"\",\"columns\":\"\",\"format\":\"\",\"custom_text\":\"\",\"css_class\":\"\",\"authorized_content\":{\"0\":\"all\",\"regex\":\"\"}}', 1, 1, 1, 1, 1, 'all', 'acym_email');";
        acym_query($query);

        $fieldClass = new FieldClass();
        $fieldClass->insertLanguageField();
    }

    public function installTemplates()
    {
        $mailClass = new MailClass();
        $installedTemplates = 0;

        $templates = acym_getFiles(ACYM_BACK.'templates'.DS, '.zip', false, true);
        foreach ($templates as $oneTemplate) {
            $templateName = substr($oneTemplate, strrpos($oneTemplate, DS) + 1);
            $templateName = substr($templateName, 0, strrpos($templateName, '.'));

            $oneMail = $mailClass->getOneByName($templateName, false, $mailClass::TYPE_TEMPLATE);
            if (!empty($oneMail)) continue;

            $templateFolder = $mailClass->extractTemplate($oneTemplate, false);
            if (empty($templateFolder)) continue;

            if ($mailClass->installExtractedTemplate($templateFolder)) {
                $installedTemplates++;
            }
        }

        if ($installedTemplates === 0) {
            acym_enqueueMessage(acym_translation('ACYM_DEFAULT_TEMPLATES_ALREADY_INSTALL'), 'info');
        }
    }

    private function getDTextDisplay($dtext, $preview)
    {
        $display = '<span class="acym_dynamic mceNonEditable" contenteditable="false" data-dynamic="'.acym_escape($dtext).'" data-mce-selected="1">';
        $display .= $preview;
        $display .= '<em class="acym_remove_dynamic acymicon-close">&zwj;</em>';
        $display .= '</span>';

        return $display;
    }

    private function newAutomationAdmin($title)
    {
        $automationClass = new AutomationClass();
        $stepClass = new StepClass();
        $conditionClass = new ConditionClass();
        $mailClass = new MailClass();
        $actionClass = new ActionClass();


        $adminCreate = new \stdClass();
        $adminCreate->desc = 'ACYM_ADMIN_USER_CREATE_DESC';
        $adminCreate->triggers = '{"user_creation":[""],"type_trigger":"user"}';
        $adminCreate->conditions = '{"type_condition":"user"}';
        $adminCreate->emailTitle = acym_translation('ACYM_SUBSCRIBER_CREATION');
        $adminCreate->emailSubject = acym_translation('ACYM_SUBSCRIBER_CREATION');
        $adminCreate->emailContent = '<h1 style="font-size: 24px;">'.acym_translation('ACYM_HELLO').' '.$this->getDTextDisplay('{subscriber:name|ucfirst}', 'Marc').',</h1>
                    <p>'.acym_translation('ACYM_NEW_USER_ACYMAILING').':</p>
                    <p>'.acym_translation('ACYM_NAME').': '.$this->getDTextDisplay('{subscriber:name|info:current}', 'Roger').'</p>
                    <p>'.acym_translation('ACYM_EMAIL').': '.$this->getDTextDisplay('{subscriber:email|info:current}', 'roger@example.com').'</p>';

        $adminModif = new \stdClass();
        $adminModif->desc = 'ACYM_ADMIN_USER_MODIFICATION_DESC';
        $adminModif->triggers = '{"user_modification":[""],"type_trigger":"user"}';
        $adminModif->conditions = '{"type_condition":"user"}';
        $adminModif->emailTitle = acym_translation('ACYM_SUBSCRIBER_MODIFICATION');
        $adminModif->emailSubject = acym_translation('ACYM_SUBSCRIBER_MODIFICATION');
        $adminModif->emailContent = '<h1 style="font-size: 24px;">'.acym_translation('ACYM_HELLO').' '.$this->getDTextDisplay('{subscriber:name|ucfirst}', 'Marc').',</h1>
                    <p>'.acym_translation('ACYM_USER_MODIFY_ACYMAILING').':</p>
                    <p>'.acym_translation('ACYM_NAME').': '.$this->getDTextDisplay('{subscriber:name|info:current}', 'Roger').'</p>
                    <p>'.acym_translation('ACYM_EMAIL').': '.$this->getDTextDisplay('{subscriber:email|info:current}', 'roger@example.com').'</p>';

        $info = [
            'ACYM_ADMIN_USER_CREATE' => $adminCreate,
            'ACYM_ADMIN_USER_MODIFICATION' => $adminModif,
        ];

        $newAutomation = new \stdClass();
        $newAutomation->name = acym_translation($title);
        $newAutomation->description = acym_translation($info[$title]->desc);
        $newAutomation->active = 0;
        $newAutomation->admin = 1;
        $newAutomation->id = $automationClass->save($newAutomation);
        if (empty($newAutomation->id)) {
            return;
        }

        $newStep = new \stdClass();
        $newStep->name = acym_translation($title);
        $newStep->triggers = $info[$title]->triggers;
        $newStep->automation_id = $newAutomation->id;
        $newStep->id = $stepClass->save($newStep);
        if (empty($newStep->id)) {
            return;
        }

        $newCondition = new \stdClass();
        $newCondition->step_id = $newStep->id;
        $newCondition->conditions = $info[$title]->conditions;
        $newCondition->id = $conditionClass->save($newCondition);
        if (empty($newCondition->id)) {
            return;
        }

        $mailAutomation = new \stdClass();
        $mailAutomation->type = $mailClass::TYPE_AUTOMATION;
        $mailAutomation->drag_editor = 1;
        $mailAutomation->creator_id = acym_currentUserId();
        $mailAutomation->creation_date = date('Y-m-d H:i:s', time() - date('Z'));
        $mailAutomation->name = acym_translation($info[$title]->emailTitle);
        $mailAutomation->subject = acym_translation($info[$title]->emailSubject);
        $mailAutomation->body = $this->getFormatedNotification($info[$title]->emailContent);

        $mailAutomation->id = $mailClass->save($mailAutomation);
        if (empty($mailAutomation->id)) {
            return;
        }

        $newAction = new \stdClass();
        $newAction->condition_id = $newCondition->id;
        $newAction->actions = '[{"acy_add_queue":{"mail_id":"'.intval($mailAutomation->id).'","time":"[time]"}}]';
        $newAction->filters = '{"0":{"1":{"acy_field":{"field":"email","operator":"=","value":"'.acym_currentUserEmail().'"}}},"type_filter":"classic"}';
        $newAction->order = 1;
        $newAction->id = $actionClass->save($newAction);
    }

    public function installDefaultAutomations()
    {
        $this->newAutomationAdmin('ACYM_ADMIN_USER_CREATE');
        $this->newAutomationAdmin('ACYM_ADMIN_USER_MODIFICATION');
    }

    public function installList()
    {
        $listClass = new ListClass();
        $listClass->addDefaultList();
    }

    public function installNotifications()
    {
        $searchSettings = [
            'offset' => 0,
            'mailsPerPage' => 9000,
            'key' => 'name',
        ];

        $mailClass = new MailClass();
        $notifications = $mailClass->getMailsByType($mailClass::TYPE_NOTIFICATION, $searchSettings);
        $notifications = $notifications['mails'];

        $addNotif = [];

        // Email sent with the cron report
        if (empty($notifications['acy_report'])) {
            $addNotif[] = [
                'name' => 'acy_report',
                'subject' => 'AcyMailing Cron Report {mainreport}',
                'content' => '<p>{report}</p><p>{detailreport}</p>',
            ];
        }

        // Confirmation email for double opt-in
        if (empty($notifications['acy_confirm'])) {
            $addNotif[] = [
                'name' => 'acy_confirm',
                'subject' => '{subscriber:name|ucfirst}, {trans:ACYM_PLEASE_CONFIRM_SUBSCRIPTION}',
                'content' => $this->getFormatedNotification(
                    '<h1 style="font-size: 24px;">{trans:ACYM_HELLO} '.$this->getDTextDisplay('{subscriber:name|ucfirst}', 'Marc').',</h1>
                    <p>{trans:ACYM_CONFIRM_MESSAGE}</p>
                    <p>{trans:ACYM_CONFIRM_MESSAGE_ACTIVATE}</p>
                    <p style="text-align: center;"><strong>{confirm}{trans:ACYM_CONFIRM_SUBSCRIPTION}{/confirm}</strong></p>'
                ),
            ];
        }

        if (empty($notifications['acy_notification_create'])) {
            $addNotif[] = [
                'name' => 'acy_notification_create',
                'subject' => acym_translation('ACYM_NOTIFICATION_CREATE_SUBJECT').': {user:email}',
                'content' => $this->getFormatedNotification(
                    '<h1 style="font-size: 24px;">'.acym_translation('ACYM_HELLO').' '.$this->getDTextDisplay('{subscriber:name|ucfirst}', 'Marc').',</h1>
                    <p>'.acym_translation('ACYM_NOTIFICATION_CREATE_BODY').'</p>
                    <p>'.acym_translation('ACYM_NAME').': '.$this->getDTextDisplay('{user:name}', 'Julia').'</p>
                    <p>'.acym_translation('ACYM_EMAIL').': '.$this->getDTextDisplay('{user:email}', 'julia@example.com').'</p>
                    <p>'.acym_translation('ACYM_SOURCE').': '.$this->getDTextDisplay('{user:source}', 'registration_form').'</p>
                    <p>'.acym_translation('ACYM_SUBSCRIPTION').': '.$this->getDTextDisplay('{user:subscription}', 'Newsletters - 2020-11-02 14:38:24').'</p>'
                ),
            ];
        }

        if (empty($notifications['acy_notification_unsub'])) {
            $addNotif[] = [
                'name' => 'acy_notification_unsub',
                'subject' => acym_translation('ACYM_NOTIFICATION_UNSUB_SUBJECT').': {user:email}',
                'content' => $this->getFormatedNotification(
                    '<h1 style="font-size: 24px;">'.acym_translation('ACYM_HELLO').' '.$this->getDTextDisplay('{subscriber:name|ucfirst}', 'Marc').',</h1>
                    <p>'.acym_translation('ACYM_NOTIFICATION_UNSUB_BODY').'</p>
                    <p>'.acym_translation('ACYM_NAME').': '.$this->getDTextDisplay('{user:name}', 'Julia').'</p>
                    <p>'.acym_translation('ACYM_EMAIL').': '.$this->getDTextDisplay('{user:email}', 'julia@example.com').'</p>
                    <p>'.acym_translation('ACYM_LISTS').': '.$this->getDTextDisplay('{lists}', 'Newsletters, Tips').'</p>'
                ),
            ];
        }

        if (empty($notifications['acy_notification_unsuball'])) {
            $addNotif[] = [
                'name' => 'acy_notification_unsuball',
                'subject' => acym_translation('ACYM_NOTIFICATION_UNSUBALL_SUBJECT').': {user:email}',
                'content' => $this->getFormatedNotification(
                    '<h1 style="font-size: 24px;">'.acym_translation('ACYM_HELLO').' '.$this->getDTextDisplay('{subscriber:name|ucfirst}', 'Marc').',</h1>
                    <p>'.acym_translation('ACYM_NOTIFICATION_UNSUBALL_BODY').'</p>
                    <p>'.acym_translation('ACYM_NAME').': '.$this->getDTextDisplay('{user:name}', 'Julia').'</p>
                    <p>'.acym_translation('ACYM_EMAIL').': '.$this->getDTextDisplay('{user:email}', 'julia@example.com').'</p>'
                ),
            ];
        }

        if (empty($notifications['acy_notification_subform'])) {
            $addNotif[] = [
                'name' => 'acy_notification_subform',
                'subject' => acym_translation('ACYM_NOTIFICATION_SUBFORM_SUBJECT').': {user:email}',
                'content' => $this->getFormatedNotification(
                    '<h1 style="font-size: 24px;">'.acym_translation('ACYM_HELLO').' '.$this->getDTextDisplay('{subscriber:name|ucfirst}', 'Marc').',</h1>
                    <p>'.acym_translation('ACYM_NOTIFICATION_SUBFORM_BODY').'</p>
                    <p>'.acym_translation('ACYM_NAME').': '.$this->getDTextDisplay('{user:name}', 'Julia').'</p>
                    <p>'.acym_translation('ACYM_EMAIL').': '.$this->getDTextDisplay('{user:email}', 'julia@example.com').'</p>
                    <p>'.acym_translation('ACYM_SUBSCRIPTION').': '.$this->getDTextDisplay('{user:subscription}', 'Newsletters - 2020-11-02 14:38:24').'</p>'
                ),
            ];
        }

        if (empty($notifications['acy_notification_profile'])) {
            $addNotif[] = [
                'name' => 'acy_notification_profile',
                'subject' => acym_translation('ACYM_NOTIFICATION_PROFILE_SUBJECT').': {user:email}',
                'content' => $this->getFormatedNotification(
                    '<h1 style="font-size: 24px;">'.acym_translation('ACYM_HELLO').' '.$this->getDTextDisplay('{subscriber:name|ucfirst}', 'Marc').',</h1>
                    <p>'.acym_translation('ACYM_NOTIFICATION_PROFILE_BODY').'</p>
                    <p>'.acym_translation('ACYM_NAME').': '.$this->getDTextDisplay('{user:name}', 'Julia').'</p>
                    <p>'.acym_translation('ACYM_EMAIL').': '.$this->getDTextDisplay('{user:email}', 'julia@example.com').'</p>
                    <p>'.acym_translation('ACYM_SUBSCRIPTION').': '.$this->getDTextDisplay('{user:subscription}', 'Newsletters - 2020-11-02 14:38:24').'</p>'
                ),
            ];
        }

        if (empty($notifications['acy_notification_confirm'])) {
            $addNotif[] = [
                'name' => 'acy_notification_confirm',
                'subject' => acym_translation('ACYM_NOTIFICATION_CONFIRM_SUBJECT').': {user:email}',
                'content' => $this->getFormatedNotification(
                    '<h1 style="font-size: 24px;">'.acym_translation('ACYM_HELLO').' '.$this->getDTextDisplay('{subscriber:name|ucfirst}', 'Marc').',</h1>
                    <p>'.acym_translation('ACYM_NOTIFICATION_CONFIRM_BODY').'</p>
                    <p>'.acym_translation('ACYM_NAME').': '.$this->getDTextDisplay('{user:name}', 'Julia').'</p>
                    <p>'.acym_translation('ACYM_EMAIL').': '.$this->getDTextDisplay('{user:email}', 'julia@example.com').'</p>
                    <p>'.acym_translation('ACYM_IP').': '.$this->getDTextDisplay('{user:confirmation_ip}', '127.0.0.1').'</p>
                    <p>'.acym_translation('ACYM_SUBSCRIPTION').': '.$this->getDTextDisplay('{user:subscription}', 'Newsletters - 2020-11-02 14:38:24').'</p>'
                ),
            ];
        }

        $firstEmail = $mailClass->getOneByName(acym_translation(self::FIRST_EMAIL_NAME_KEY));

        if (empty($firstEmail)) {
            $currentCMSId = acym_currentUserId();
            $currentCMSEmail = acym_currentUserEmail();
            $userClass = new UserClass();
            $user = $userClass->getOneByCMSId($currentCMSId);
            if (empty($user)) $user = $userClass->getOneByEmail($currentCMSEmail);
            if (empty($user)) {
                $newUser = new \stdClass();
                $newUser->email = $currentCMSEmail;
                $newUser->confirmed = 1;
                $newUser->cms_id = $currentCMSId;
                $newUser->id = $userClass->save($newUser);
                $user = $userClass->getOneById($newUser->id);
            }

            $linkViewItOnlineFirstTest = 'archive&task=view&id=id_view_it_online_first_test&userid='.$user->id.'-'.$user->key.'&'.acym_noTemplate();
            $body = '<div id="acym__wysid__template" class="cell"><table class="body"><tbody><tr><td align="center" class="center acym__wysid__template__content" valign="top" style="background-color: rgb(245, 245, 245); padding: 30px 0px 120px;"><center><table align="center" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td class="acym__wysid__row ui-droppable ui-sortable" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255); min-height: 0px; display: table-cell;"><table class="row acym__wysid__row__element" border="0" cellpadding="0" cellspacing="0" style="z-index: 100; background-color: rgb(238, 238, 238);" bgcolor="#eeeeee"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns" style="height: auto;"><table class="acym__wysid__column" style="min-height: 0px; display: table; height: auto;" border="0" cellpadding="0" cellspacing="0"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group; height: auto;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false" id="mce_63" contenteditable="false"><p style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: rgb(165, 165, 165); word-break: break-word; text-align: center;" data-mce-style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #000000; word-break: break-word; text-align: center;"><span style="font-size: 12px;" data-mce-style="font-size: 12px;">We need your feedback!</span></p><p style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: rgb(165, 165, 165); word-break: break-word; text-align: center;" data-mce-style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #000000; word-break: break-word; text-align: center;"><span style="font-size: 12px;" data-mce-style="font-size: 12px;">Having trouble seeing this email?&nbsp;<span class="acym_dynamic mceNonEditable" contenteditable="false" data-dynamic="{readonline}View it online{/readonline}"><a style="text-decoration: none;" href="view_it_online_first_test" target="_blank" rel="noopener" data-mce-href="" data-mce-style="text-decoration: none;"><span class="acym_online">View it online</span></a><em class="acym_remove_dynamic acymicon-close">&zwj;‍</em></span>&nbsp;</span></p></div></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255); z-index: 100;" cellpadding="0" cellspacing="0" border="0"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th" valign="top"><table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0" style="min-height: 0px; display: table;"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--image mce-content-body" style="position: relative;" spellcheck="false" id="mce_67" contenteditable="false"><p style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #a5a5a5; word-break: break-word;" data-mce-style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #a5a5a5; word-break: break-word;"><img class="acym__wysid__media__inserted" style="max-width: 100%; height: 85px; box-sizing: border-box; padding: 0px 5px; display: block; margin-left: auto; margin-right: auto; width: 306px;" src="'.ACYM_LIVE.ACYM_UPLOAD_FOLDER.'logo_acymailing_step_email.png" alt="logo_acymailing_step_email.png" height="85" width="306" data-mce-style="max-width: 100%; height: 85px; box-sizing: border-box; padding: 0px 5px; display: block; margin-left: auto; margin-right: auto;"></p></div></td></tr><tr class="acym__wysid__column__element acym__wysid__column__element__separator cursor-pointer ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><hr style="border-bottom: 3px solid rgb(214, 214, 214); width: 24%; border-top: none; border-left: none; border-right: none;" class="acym__wysid__row__separator"></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255); z-index: 100;" cellpadding="0" cellspacing="0" border="0"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th" valign="top"><table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0" style="min-height: 0px; display: table;"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false" id="mce_64" contenteditable="false"><h1 style="font-family: Helvetica; font-size: 34px; font-weight: normal; font-style: normal; color: rgb(0, 164, 255); text-align: center;" data-mce-style="font-family: Helvetica; font-size: 34px; font-weight: normal; font-style: normal; color: #00a4ff; text-align: center;">Dear&nbsp;<span class="acym_dynamic mceNonEditable" contenteditable="false" data-dynamic="{subscriber:name|part:first|ucfirst}">Admin<em class="acym_remove_dynamic acymicon-close">&zwj;‍</em></span>&nbsp;</h1></div></td></tr><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false" id="mce_65" contenteditable="false"><p style="word-break: break-word; font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: rgb(165, 165, 165); text-align: center;" data-mce-style="word-break: break-word; font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #a5a5a5; text-align: center;"><span data-mce-style="color: #a5a5a5; font-family: Helvetica;" style="color: #a5a5a5; font-family: Helvetica;">Amazing, you are going to send your first email!</span></p><p style="word-break: break-word; font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: rgb(165, 165, 165); text-align: center;" data-mce-style="word-break: break-word; font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #a5a5a5; text-align: center;">&nbsp;<br></p><p style="word-break: break-word; font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: rgb(165, 165, 165); text-align: center;" data-mce-style="word-break: break-word; font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #a5a5a5; text-align: center;"><span data-mce-style="color: #a5a5a5; font-family: Helvetica;" style="color: #a5a5a5; font-family: Helvetica;">Feel free to drag &amp; drop some content and&nbsp;</span><span data-mce-style="color: #a5a5a5; font-family: Helvetica;" style="color: #a5a5a5; font-family: Helvetica;">try the AcyMailing editor.</span></p><p style="word-break: break-word; font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: rgb(165, 165, 165); text-align: center;" data-mce-style="word-break: break-word; font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #a5a5a5; text-align: center;"><span data-mce-style="color: #a5a5a5; font-family: Helvetica;" style="color: #a5a5a5; font-family: Helvetica;">Once it is done, click on the "Apply" button.</span></p></div></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255); z-index: 100; padding: 0px;" cellpadding="0" cellspacing="0" border="0"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th" valign="top"><table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0" style="min-height: 0px; display: table;"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--image mce-content-body" style="position: relative;" spellcheck="false" id="mce_68" contenteditable="false"><p style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #a5a5a5; word-break: break-word;" data-mce-style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #a5a5a5; word-break: break-word;"><img class="acym__wysid__media__inserted" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0px 5px; display: block; margin-left: auto; margin-right: auto; width: 580px;" src="'.ACYM_LIVE.ACYM_UPLOAD_FOLDER.'image_mailing_step_email.jpg" alt="image_mailing_step_email.jpg" data-mce-style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block; margin-left: auto; margin-right: auto;" height="401" width="580"></p></div></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#303e47" style="background-color: rgb(48, 62, 71); z-index: 100;" cellpadding="0" cellspacing="0" border="0"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th" valign="top"><table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0" style="min-height: 0px; display: table;"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false" id="mce_66" contenteditable="false"><p style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: rgb(165, 165, 165); text-align: center; word-break: break-word;" data-mce-style="font-family: Helvetica; font-size: 16px; font-weight: normal; font-style: normal; color: #a5a5a5; text-align: center;"><span class="acym_dynamic mceNonEditable" contenteditable="false" data-dynamic="{unsubscribe}Unsubscribe{/unsubscribe}"><a style="text-decoration: none;" href="'.acym_frontendLink(
                    'frontusers&task=unsubscribe&id='.$user->id.'&key='.$user->key.'&'.acym_noTemplate()
                ).'" target="_blank" rel="noopener" data-mce-href="" data-mce-style="text-decoration: none;"><span class="acym_unsubscribe">Unsubscribe</span></a><em class="acym_remove_dynamic acymicon-close">&zwj;‍</em></span></p></div></td></tr></tbody></table></th></tr></tbody></table></td></tr></tbody></table></center></td></tr></tbody></table></div>';

            $addNotif[] = [
                'name' => acym_translation(self::FIRST_EMAIL_NAME_KEY),
                'subject' => acym_translation('ACYM_YOUR_FIRST_EMAIL'),
                'content' => $body,
                'settings' => '{"p":{"font-family":"Helvetica","font-size":"16px"},"#acym__wysid__background-colorpicker":{"background-color":"#f5f5f5"}}',
                'type' => $mailClass::TYPE_TEMPLATE,
                'thumbnail' => 'thumbnail_first_email.png',
            ];
            $mailingImage = 'image_mailing_step_email.jpg';
            $logoAcymailing = 'logo_acymailing_step_email.png';
            $thumbnailFirstStep = 'thumbnail_first_email.png';

            acym_createFolder(ACYM_ROOT.ACYM_UPLOAD_FOLDER);
            acym_createFolder(ACYM_UPLOAD_FOLDER_THUMBNAIL);

            if (!file_exists(ACYM_ROOT.ACYM_UPLOAD_FOLDER.$mailingImage)) {
                copy(
                    ACYM_ROOT.ACYM_MEDIA_FOLDER.'images'.DS.$mailingImage,
                    ACYM_ROOT.ACYM_UPLOAD_FOLDER.$mailingImage
                );
            }
            if (!file_exists(ACYM_ROOT.ACYM_UPLOAD_FOLDER.$logoAcymailing)) {
                copy(
                    ACYM_ROOT.ACYM_MEDIA_FOLDER.'images'.DS.$logoAcymailing,
                    ACYM_ROOT.ACYM_UPLOAD_FOLDER.$logoAcymailing
                );
            }
            if (!file_exists(ACYM_UPLOAD_FOLDER_THUMBNAIL.$thumbnailFirstStep)) {
                copy(
                    ACYM_ROOT.ACYM_MEDIA_FOLDER.'images'.DS.$thumbnailFirstStep,
                    ACYM_UPLOAD_FOLDER_THUMBNAIL.$thumbnailFirstStep
                );
            }
        }

        if (!empty($addNotif)) {
            foreach ($addNotif as $oneNotif) {
                $notif = new \stdClass();
                $notif->type = empty($oneNotif['type']) ? $mailClass::TYPE_NOTIFICATION : $oneNotif['type'];
                $notif->settings = empty($oneNotif['settings']) ? '' : $oneNotif['settings'];
                $notif->drag_editor = 1;
                $notif->creator_id = acym_currentUserId();
                $notif->creation_date = date('Y-m-d H:i:s', time() - date('Z'));
                $notif->name = $oneNotif['name'];
                $notif->subject = $oneNotif['subject'];
                $notif->body = $oneNotif['content'];
                $notif->thumbnail = empty($oneNotif['thumbnail']) ? '' : $oneNotif['thumbnail'];

                $notif->id = $mailClass->save($notif);
                if (empty($notif->id)) {
                    acym_enqueueMessage(acym_translationSprintf('ACYM_ERROR_INSTALLING_X_TEMPLATE', $notif->name), 'error');

                    return false;
                }
                //for the view it online
                if (acym_translation(self::FIRST_EMAIL_NAME_KEY) === $notif->name && !empty($linkViewItOnlineFirstTest)) {
                    $linkViewItOnlineFirstTest = str_replace('id_view_it_online_first_test', $notif->id, $linkViewItOnlineFirstTest);
                    $notif->body = str_replace('view_it_online_first_test', acym_frontendLink($linkViewItOnlineFirstTest), $notif->body);
                    $mailClass->save($notif);
                }
            }
        }

        return true;
    }

    private function getFormatedNotification($content)
    {
        $begining = '<div id="acym__wysid__template" class="cell"><table class="body"><tbody><tr><td align="center" class="center acym__wysid__template__content" valign="top" style="background-color: rgb(239, 239, 239); padding: 40px 0 120px 0;"><center><table align="center" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td class="acym__wysid__row ui-droppable ui-sortable" style="min-height: 0px; display: table-cell;"><table class="row acym__wysid__row__element" bgcolor="#dadada" border="0" cellpadding="0" cellspacing="0"><tbody style="background-color: rgb(218,218,218);" bgcolor="#ffffff"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th"><table class="acym__wysid__column" style="min-height: 0px; display: table;" border="0" cellpadding="0" cellspacing="0"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline: rgb(0,163,254) dashed 0px; outline-offset: -1px;"><span class="acy-editor__space acy-editor__space--focus" style="display: block; padding: 0px; margin: 0px; height: 10px;"></span></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0"><tbody style="background-color: transparent;" bgcolor="#ffffff"><tr><th class="small-12 medium-12 large-12 columns"><table class="acym__wysid__column" style="min-height: 0px; display: table;" border="0" cellpadding="0" cellspacing="0"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline: rgb(0, 163, 254) dashed 0px; outline-offset: -1px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false">';
        $ending = '</div></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#dadada" style="position: relative; z-index: 100; top: 0; left: 0;" border="0" cellpadding="0" cellspacing="0"><tbody style="background-color: rgb(218, 218, 218);" bgcolor="#ffffff"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th"><table class="acym__wysid__column" style="min-height: 0px; display: table;" border="0" cellpadding="0" cellspacing="0"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline: rgb(0, 163, 254) dashed 0px; outline-offset: -1px;"><span class="acy-editor__space acy-editor__space--focus" style="display: block; padding: 0px; margin: 0px; height: 10px;"></span></td></tr></tbody></table></th></tr></tbody></table></td></tr></tbody></table></center></td></tr></tbody></table></div>';

        return $begining.$content.$ending;
    }

    public function installExtensions($report = true)
    {
        $dirs = acym_getFolders(ACYM_BACK.'extensions');
        if (empty($dirs)) return;

        $extensionsToPublish = [
            'acymtriggers',
            'jceacym',
        ];
        $existingExtensions = acym_loadResultArray(
            'SELECT `element` 
            FROM #__extensions 
            WHERE `type` = "plugin" 
                AND `folder` = "system" 
                AND `element` IN ("'.implode('", "', $extensionsToPublish).'")'
        );

        if (!empty($existingExtensions)) {
            $extensionsToPublish = array_diff($extensionsToPublish, $existingExtensions);
        }

        $installer = \JInstaller::getInstance();
        foreach ($dirs as $oneExtension) {
            $extension = ACYM_BACK.'extensions'.DS.$oneExtension;
            if (file_exists($extension)) {
                $installer->install($extension);
                $this->addUpdateSite($oneExtension);
            }
        }

        if (!empty($extensionsToPublish)) {
            acym_query(
                'UPDATE #__extensions 
                SET `enabled` = 1 
                WHERE `type` = "plugin" 
                    AND `folder` = "system" 
                    AND `element` IN ("'.implode('", "', $extensionsToPublish).'")'
            );
        }

        acym_deleteFolder(ACYM_BACK.'extensions', $report);
    }

    public function installAddons()
    {
        $pluginClass = new PluginClass();
        $installedAddons = array_keys($pluginClass->getAll('folder_name'));
        $coreAddons = acym_coreAddons();

        foreach ($coreAddons as $oneAddon) {
            if (in_array($oneAddon->folder_name, $installedAddons)) continue;

            $pluginClass->save($oneAddon);
        }
    }

    public function installOverrideEmails()
    {
        $overrideClass = new OverrideClass();
        $mailClass = new MailClass();
        $currentUserId = acym_currentUserId();

        $emailOverrides = acym_getEmailOverrides();
        $existingOverrides = $mailClass->getMailsByType($mailClass::TYPE_OVERRIDE, ['offset' => 0, 'mailsPerPage' => 9000, 'key' => 'name']);
        $existingOverrides = $existingOverrides['mails'];

        foreach ($emailOverrides as $oneOverride) {
            if (!empty($existingOverrides[$oneOverride['name']])) continue;

            $mail = new \stdClass();
            $mail->name = $oneOverride['name'];
            $mail->creation_date = date('Y-m-d H:i:s', time() - date('Z'));
            $mail->type = $mailClass::TYPE_OVERRIDE;
            $mail->subject = $oneOverride['new_subject'];
            $mail->body = $this->getFormatedNotification($oneOverride['new_body']);
            $mail->drag_editor = 1;
            $mail->creator_id = $currentUserId;
            $mailId = $mailClass->save($mail);

            if (empty($mailId)) continue;

            $override = new \stdClass();
            $override->mail_id = $mailId;
            $override->description = $oneOverride['description'];
            $override->source = $oneOverride['source'];
            $override->active = 0;
            $override->base_subject = json_encode($oneOverride['base_subject']);
            $override->base_body = empty($oneOverride['base_body']) ? '' : json_encode($oneOverride['base_body']);

            $overrideClass->save($override);
        }
    }

    public function updateAddons()
    {
        acym_checkPluginsVersion();

        $pluginClass = new PluginClass();
        $pluginsToUpdate = $pluginClass->getNotUptoDatePlugins();
        foreach ($pluginsToUpdate as $onePlugin) {
            $pluginClass->updateAddon($onePlugin);
        }
    }
}
