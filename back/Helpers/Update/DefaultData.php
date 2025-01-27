<?php

namespace AcyMailing\Helpers\Update;

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

trait DefaultData
{
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

        $query = "INSERT INTO `#__acym_rule` (`id`, `name`, `description`, `ordering`, `regex`, `executed_on`, `action_message`, `action_user`, `active`, `increment_stats`, `execute_action_after`) VALUES ";
        $query .= "(1, 'ACYM_LIST_UNSUBSCRIBE_HANDLING', 'ACYM_LIST_UNSUBSCRIBE_HANDLING_DESC', 1, 'Please unsubscribe user ID \\\\d+', '[\"body\"]', '[\"delete_message\"]', '[\"unsubscribe_user\"]', 1, 0, 0),";
        $query .= "(2, 'ACYM_SUPPRESSION_LIST', 'ACYM_SUPPRESSION_LIST_DESC', 2, 'suppression list', '[\"body\"]', '[\"delete_message\"]', '[\"unsubscribe_user\",\"block_user\",\"empty_queue_user\"]', 1, 1, 0),";
        $query .= "(3, 'ACYM_ACTION_REQUIRED', 'ACYM_ACTION_REQUIRED_DESC', 3, 'action *requ|verif', '[\"subject\"]', '{\"0\":\"delete_message\",\"1\":\"forward_message\",\"forward_to\":\"".$forwardEmail."\"}', '[]', 1, 0, 0),";
        $query .= "(4, 'ACYM_ACKNOWLEDGMENT_RECEIPT_SUBJECT', 'ACYM_ACKNOWLEDGMENT_RECEIPT_SUBJECT_DESC', 4, '(out|away) *(of|from)|vacation|vacanze|vacaciones|holiday|absen|congés|recept|acknowledg|thank you for|Auto *Response|Incident|Automati|Ticket|Resposta *automática', '[\"subject\"]', '[\"delete_message\"]', '[]', 1, 0, 0),";
        $query .= "(5, 'ACYM_FEEDBACK_LOOP', 'ACYM_FEEDBACK_LOOP_DESC', 5, 'feedback|staff@hotmail.com|complaints@.{0,15}email-abuse.amazonses.com|complaint about message', '[\"senderInfo\",\"subject\"]', '[\"save_message\",\"delete_message\"]', '[\"unsubscribe_user\",\"block_user\",\"empty_queue_user\"]', 1, 0, 0),";
        $query .= "(6, 'ACYM_FEEDBACK_LOOP_BODY', 'ACYM_FEEDBACK_LOOP_BODY_DESC', 6, 'Feedback-Type.{1,5}abuse', '[\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"unsubscribe_user\"]', 1, 1, 0),";
        $query .= "(7, 'ACYM_MAILBOX_FULL', 'ACYM_MAILBOX_FULL_DESC', 7, '((mailbox|mailfolder|storage|quota|space|inbox) *(is)? *(over)? *(exceeded|size|storage|allocation|full|quota|maxi))|status(-code)? *(:|=)? *5.2.2|quota-issue|not *enough.{1,20}space|((over|exceeded|full|exhausted) *(allowed)? *(mail|storage|quota))|Space shortage', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(8, 'ACYM_BLOCKED_GOOGLE_GROUPS', 'ACYM_BLOCKED_GOOGLE_GROUPS_DESC', 8, 'message *rejected *by *Google *Groups', '[\"body\"]', '[\"delete_message\"]', '[]', 1, 1, 0),";
        $query .= "(9, 'ACYM_REJECTED', 'ACYM_REJECTED_DESC', 9, 'rejected *your *message|email *provider *rejected *it', '[\"body\"]', '[\"delete_message\"]', '[]', 1, 1, 0),";
        $query .= "(10, 'ACYM_MAILBOX_DOESNT_EXIST_1', 'ACYM_MAILBOX_DOESNT_EXIST_1_DESC', 10, '(Invalid|no such|unknown|bad|des?activated|inactive|unrouteable) *(mail|destination|recipient|user|address|person)|bad-mailbox|inactive-mailbox|not listed in.{1,20}directory|RecipNotFound|(user|mailbox|address|recipients?|host|account|domain) *(is|has been)? *(error|disabled|failed|unknown|unavailable|not *(found|available)|.{1,30}inactiv)|no *mailbox *here|user does.?n.t have.{0,30}account|no *longer *(active|working|in *use)|mail( *foi)? *desativado', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(11, 'ACYM_MESSAGE_BLOCKED_RECIPIENTS', 'ACYM_MESSAGE_BLOCKED_RECIPIENTS_DESC', 11, 'blocked *by|block *list|look(ed)? *like *spam|spam-related|spam *detected| CXBL | CDRBL | IPBL | URLBL |(unacceptable|banned|offensive|filtered|blocked|unsolicited) *(content|message|e?-?mail)|service refused|(status(-code)?|554) *(:|=)? *5.7.1|administratively *denied|blacklisted *IP|policy *reasons|rejected.{1,10}spam|junkmail *rejected|throttling *constraints|exceeded.{1,10}max.{1,40}hour|comply with required standards|421 RP-00|550 SC-00|550 DY-00|550 OU-00', '[\"body\"]', '{\"0\":\"delete_message\",\"1\":\"forward_message\",\"forward_to\":\"".$forwardEmail."\"}', '[]', 1, 1, 0),";
        $query .= "(12, 'ACYM_MAILBOX_DOESNT_EXIST_2', 'ACYM_MAILBOX_DOESNT_EXIST_2_DESC', 12, 'status(-? ?code)? *(:|=)? *(550)? *5.(1.[1-6]|0.0|4.[0123467])|recipient *address *rejected|does *not *like *recipient|recipient *unknown *to *address|email *account *that *you *tried *to *reach *is *disabled', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(13, 'ACYM_DOMAIN_NOT_EXIST', 'ACYM_DOMAIN_NOT_EXIST_DESC', 13, 'No.{1,10}MX *(record|host)|host *does *not *receive *any *mail|bad-domain|connection.{1,10}mail.{1,20}fail|domain.{1,10}not *exist|fail.{1,10}establish *connection|Unable *to *lookup *DNS *for|Unable *to *connect *to *remote *host', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(14, 'ACYM_TEMPORARY_FAILURES', 'ACYM_TEMPORARY_FAILURES_DESC', 14, 'has.*been.*delayed|delayed *mail|message *delayed|message-expired|temporar(il)?y *(failure|unavailable|disable|offline|unable)|deferred|delayed *([0-9]*) *(hour|minut)|possible *mail *loop|too *many *hops|delivery *time *expired|Action.php: *delayed|status(-code)? *(:|=)? *4.4.6|will continue to be attempted|unable to deliver in.*Status: 4.4.7|Connection *closed *unexpectedly', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(15, 'ACYM_FAILED_PERM', 'ACYM_FAILED_PERM_DESC', 15, 'failed *permanently|permanent.{1,20}(failure|error)|not *accepting *(any)? *mail|does *not *exist|no *valid *route|delivery *failure', '[\"subject\",\"body\"]', '[\"save_message\",\"delete_message\"]', '[\"block_user\"]', 1, 1, 0),";
        $query .= "(16, 'ACYM_ACKNOWLEDGMENT_RECEIPT_BODY', 'ACYM_ACKNOWLEDGMENT_RECEIPT_BODY_DESC', 16, 'vacanc|holiday|vacation|absen|urlaub|ferie|feriado|vacanz|vacaciones|(out *of|not *in|outside)( *the)? *office|automatisch *(generierte)? *Antwort|confirm *we(\\'ve| *have) *received|den *Ferien|automática|RISPOSTA AUTOMATICA|vakantie|recepcionado *(su *)?requerimiento|Kiitos viestistäsi|nicht im Büro|nicht am Arbeitsplatz|automatic *reply|auto(-|mated *|matic *)reply|Email *has *been *recived|ricevuto *il *Suo *messaggio|ticket|poza *biurem|Automatic *respon|Case *Number|Αυτόματη *απάντηση', '[\"body\"]', '[\"delete_message\"]', '[]', 1, 0, 0),";
        $query .= "(17, 'ACYM_FINAL_RULE', 'ACYM_FINAL_RULE_DESC', 17, '.', '[\"senderInfo\",\"subject\"]', '{\"0\":\"delete_message\",\"1\":\"forward_message\",\"forward_to\":\"".$forwardEmail."\"}', '[]', 1, 0, 0);";

        acym_query($query);

        $this->config->save(['bounceVersion' => self::BOUNCE_VERSION]);
    }

    public function installFields()
    {
        $query = "INSERT IGNORE INTO #__acym_field (`id`, `name`, `type`, `value`, `active`, `default_value`, `required`, `ordering`, `option`, `core`, `backend_edition`, `backend_listing`, `frontend_edition`, `frontend_listing`, `namekey`) VALUES 
    (1, 'ACYM_NAME', 'text', NULL, 1, NULL, 0, 1, '{\"error_message\":\"\",\"error_message_invalid\":\"\",\"size\":\"\",\"rows\":\"\",\"columns\":\"\",\"format\":\"\",\"custom_text\":\"\",\"css_class\":\"\",\"authorized_content\":{\"0\":\"all\",\"regex\":\"\"}}', 1, 1, 1, 1, 1, 'acym_name'), 
    (2, 'ACYM_EMAIL', 'text', NULL, 1, NULL, 1, 2, '{\"error_message\":\"\",\"error_message_invalid\":\"\",\"size\":\"\",\"rows\":\"\",\"columns\":\"\",\"format\":\"\",\"custom_text\":\"\",\"css_class\":\"\",\"authorized_content\":{\"0\":\"all\",\"regex\":\"\"}}', 1, 1, 1, 1, 1, 'acym_email');";
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

            $oneMail = $mailClass->getOneByName($templateName, false, MailClass::TYPE_TEMPLATE);
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

    public function installNotifications(): bool
    {
        $searchSettings = [
            'offset' => 0,
            'mailsPerPage' => 9000,
            'key' => 'name',
        ];

        $mailClass = new MailClass();
        $notifications = $mailClass->getMailsByType(MailClass::TYPE_NOTIFICATION, $searchSettings);
        $notifications = $notifications['mails'];
        $user = $this->getCurrentUser();

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

        if (empty($notifications['acy_notification_cms'])) {
            $addNotif[] = [
                'name' => 'acy_notification_cms',
                'subject' => '{subject}',
                'content' => '{body}',
                'drag_editor' => 0,
            ];
        }

        $firstEmail = $mailClass->getOneByName(acym_translation(self::FIRST_EMAIL_NAME_KEY));
        if (empty($firstEmail)) {
            $body = '<div id="acym__wysid__template" class="cell"><table class="body"><tbody><tr><td align="center" class="center acym__wysid__template__content" valign="top" style="background-color: rgb(239, 239, 239); padding: 30px 0px 120px;"><center><table align="center" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td class="acym__wysid__row ui-droppable ui-sortable" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255); min-height: 0px; display: table-cell;"><table class="row acym__wysid__row__element" border="0" cellpadding="0" cellspacing="0" style="z-index: 100; background-color: rgb(238, 238, 238);" bgcolor="#eeeeee"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns" style="height: auto;"><table class="acym__wysid__column" style="min-height: 0px; display: table; height: auto;" border="0" cellpadding="0" cellspacing="0"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group; height: auto;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false" contenteditable="false" id="mce_41"><p style="font-family: Helvetica; font-size: 12px; font-weight: normal; font-style: normal; color: #000000; word-break: break-word; text-align: center; line-height: inherit;"><span style="font-size: 12px;">Having trouble seeing this email?&nbsp;<span class="mceNonEditable acym_dynamic" contenteditable="false" data-dynamic="{readonline}View it online{/readonline}"><a style="text-decoration: none; font-family: Helvetica; font-size: 12px; font-weight: normal; line-height: inherit; font-style: normal; color: #38c2f3;" href="view_it_online_link" target="_blank" rel="noopener"><span class="acym_online">View it online</span></a><em class="acym_remove_dynamic acymicon-close">‍‍</em></span>&nbsp;</span></p></div></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255); z-index: 100;" cellpadding="0" cellspacing="0" border="0"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th" valign="top"><table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0" style="min-height: 0px; display: table;"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="height: 21px; outline-width: 0px;"><span class="acy-editor__space" style="display:block; padding: 0;margin: 0; height: 100%"></span></td></tr><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--image mce-content-body" style="position: relative;" spellcheck="false" contenteditable="false" id="mce_45"><p style="font-family: Helvetica; font-size: 12px; font-weight: normal; font-style: normal; color: #000000; word-break: break-word; line-height: inherit;"><img class="acym__wysid__media__inserted" style="max-width: 100%; height: 85px; box-sizing: border-box; padding: 0px 5px; display: block; margin-left: auto; margin-right: auto; width: 306px;" src="'.ACYM_LIVE.ACYM_UPLOAD_FOLDER.'logo_acymailing_step_email.png" alt="logo_acymailing_step_email.png" height="85" width="306"></p></div></td></tr><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="height: 29px; outline-width: 0px;"><span class="acy-editor__space" style="display:block; padding: 0;margin: 0; height: 100%"></span></td></tr><tr class="acym__wysid__column__element acym__wysid__column__element__separator cursor-pointer ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><hr style="border-bottom: 3px solid rgb(214, 214, 214); width: 24%; border-top: none; border-left: none; border-right: none; size: 3px;" class="acym__wysid__row__separator"></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255); z-index: 100;" cellpadding="0" cellspacing="0" border="0"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th" valign="top"><table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0" style="min-height: 0px; display: table;"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false" contenteditable="false" id="mce_42"><h1 style="font-family: Helvetica; font-size: 20px; font-weight: normal; font-style: normal; color: #000000; line-height: inherit; text-align: left;">Dear&nbsp;<span class="mceNonEditable acym_dynamic" contenteditable="false" data-dynamic="{subscriber:name|part:first|ucfirst}">Admin<em class="acym_remove_dynamic acymicon-close">‍‍</em></span>,</h1></div></td></tr><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false" contenteditable="false" id="mce_43"><p style="word-break: break-word; font-family: Helvetica; font-size: 12px; font-weight: normal; font-style: normal; color: #000000; line-height: inherit; text-align: left;"><span style="color: #000000;"><span style="font-family: Helvetica;">Feel free to drag &amp; drop some content and&nbsp;</span><span style="font-family: Helvetica;">try the AcyMailing editor.</span></span></p><p style="word-break: break-word; font-family: Helvetica; font-size: 12px; font-weight: normal; font-style: normal; color: #000000; line-height: inherit; text-align: left;"><span style="color: #000000; font-family: Helvetica;">Once it is done, click on the "Apply" button.</span></p></div></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255); z-index: 100; padding: 0px;" cellpadding="0" cellspacing="0" border="0"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th" valign="top"><table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0" style="min-height: 0px; display: table;"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--image mce-content-body" style="position: relative;" spellcheck="false" contenteditable="false" id="mce_46"><p style="font-family: Helvetica; font-size: 12px; font-weight: normal; font-style: normal; color: #000000; word-break: break-word; line-height: inherit;"><img class="acym__wysid__media__inserted" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0px 5px; display: block; margin-left: auto; margin-right: auto; width: 580px;" src="'.ACYM_LIVE.ACYM_UPLOAD_FOLDER.'image_mailing_step_email.jpg" alt="image_mailing_step_email.jpg" height="400.68" width="580"></p></div></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#303e47" style="background-color: rgb(48, 62, 71); z-index: 100;" cellpadding="0" cellspacing="0" border="0"><tbody bgcolor="" style="background-color: inherit;"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th" valign="top"><table class="acym__wysid__column" border="0" cellpadding="0" cellspacing="0" style="min-height: 0px; display: table;"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline-width: 0px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false" contenteditable="false" id="mce_44"><p style="font-family: Helvetica; font-size: 12px; font-weight: normal; font-style: normal; color: #000000; text-align: center; word-break: break-word; line-height: inherit;"><span style="color: #ffffff;">If you\'re not interested anymore, you can</span> <span class="mceNonEditable acym_dynamic" data-dynamic="{unsubscribe}unsubscribe{/unsubscribe}" contenteditable="false"><a style="text-decoration: none; font-family: Helvetica; font-size: 12px; font-weight: normal; line-height: inherit; font-style: normal; color: #38c2f3;" target="_blank" href="'.acym_frontendLink(
                    'frontusers&task=unsubscribe&id='.$user->id.'&key='.$user->key.'&'.acym_noTemplate()
                ).'" rel="noopener"><span class="acym_unsubscribe acym_link" style="font-family: Helvetica; font-size: 12px; font-weight: normal; line-height: inherit; font-style: normal; color: #ffffff;">unsubscribe</span></a><em class="acym_remove_dynamic acymicon-close">‍</em></span></p></div></td></tr></tbody></table></th></tr></tbody></table></td></tr></tbody></table></center></td></tr></tbody></table></div>';

            $addNotif[] = [
                'name' => acym_translation(self::FIRST_EMAIL_NAME_KEY),
                'subject' => acym_translation('ACYM_YOUR_FIRST_EMAIL'),
                'content' => $body,
                'settings' => '{"default":{"font-family":"Helvetica"},"p":{"font-family":"Helvetica","font-size":"12px","overridden":{"font-size":true,"line-height":true},"line-height":"inherit"},"a":{"font-family":"Helvetica","font-size":"12px","overridden":{"font-size":true,"line-height":true,"color":true},"line-height":"inherit","color":"#38c2f3"},"span.acym_link":{"font-family":"Helvetica","font-size":"12px","overridden":{"font-size":true,"line-height":true,"color":true},"line-height":"inherit","color":"#ffffff"},"h1":{"font-family":"Helvetica","font-size":"20px","overridden":{"font-size":true,"line-height":true},"line-height":"inherit"}}',
                'type' => MailClass::TYPE_TEMPLATE,
                'thumbnail' => 'thumbnail_first_email.png',
            ];
            $mailingImage = 'image_mailing_step_email.jpg';
            $logoAcymailing = 'logo_acymailing_step_email.png';
            $thumbnailFirstStep = 'thumbnail_first_email.png';

            $uploadFolder = ACYM_ROOT.ACYM_UPLOAD_FOLDER;
            $defaultImagesFolder = ACYM_ROOT.ACYM_MEDIA_FOLDER.'images'.DS.'templates'.DS.'first_email'.DS;

            acym_createFolder($uploadFolder);
            acym_createFolder(ACYM_UPLOAD_FOLDER_THUMBNAIL);

            if (!file_exists($uploadFolder.$mailingImage)) {
                copy(
                    $defaultImagesFolder.$mailingImage,
                    $uploadFolder.$mailingImage
                );
            }
            if (!file_exists($uploadFolder.$logoAcymailing)) {
                copy(
                    $defaultImagesFolder.$logoAcymailing,
                    $uploadFolder.$logoAcymailing
                );
            }
            if (!file_exists(ACYM_UPLOAD_FOLDER_THUMBNAIL.$thumbnailFirstStep)) {
                copy(
                    $defaultImagesFolder.$thumbnailFirstStep,
                    ACYM_UPLOAD_FOLDER_THUMBNAIL.$thumbnailFirstStep
                );
            }
        }

        if (!empty($addNotif)) {
            foreach ($addNotif as $oneNotif) {
                $notif = new \stdClass();
                $notif->type = $oneNotif['type'] ?? MailClass::TYPE_NOTIFICATION;
                $notif->settings = $oneNotif['settings'] ?? '';
                $notif->drag_editor = $oneNotif['drag_editor'] ?? 1;
                $notif->creator_id = acym_currentUserId();
                $notif->creation_date = date('Y-m-d H:i:s', time() - date('Z'));
                $notif->name = $oneNotif['name'];
                $notif->subject = $oneNotif['subject'];
                $notif->body = $oneNotif['content'];
                $notif->thumbnail = $oneNotif['thumbnail'] ?? '';

                $notif->id = $mailClass->save($notif);
                if (empty($notif->id)) {
                    acym_enqueueMessage(acym_translationSprintf('ACYM_ERROR_INSTALLING_X_TEMPLATE', $notif->name), 'error');

                    return false;
                }

                if (strpos($notif->body, 'view_it_online_link') !== false) {
                    $viewItOnlineLink = 'archive&task=view&id='.$notif->id.'&userid='.$user->id.'-'.$user->key.'&'.acym_noTemplate();
                    $notif->body = str_replace('view_it_online_link', acym_frontendLink($viewItOnlineLink), $notif->body);
                    $mailClass->save($notif);
                }
            }
        }

        return true;
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
        $existingOverrides = $mailClass->getMailsByType(MailClass::TYPE_OVERRIDE, ['offset' => 0, 'mailsPerPage' => 9000, 'key' => 'name']);
        $existingOverrides = $existingOverrides['mails'];

        foreach ($emailOverrides as $oneOverride) {
            if (!empty($existingOverrides[$oneOverride['name']])) continue;

            $mail = new \stdClass();
            $mail->name = $oneOverride['name'];
            $mail->creation_date = date('Y-m-d H:i:s', time() - date('Z'));
            $mail->type = MailClass::TYPE_OVERRIDE;
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
        $mailAutomation->type = MailClass::TYPE_AUTOMATION;
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

    private function getDTextDisplay($dtext, $preview)
    {
        $display = '<span class="acym_dynamic mceNonEditable" contenteditable="false" data-dynamic="'.acym_escape($dtext).'" data-mce-selected="1">';
        $display .= $preview;
        $display .= '<em class="acym_remove_dynamic acymicon-close">&zwj;</em>';
        $display .= '</span>';

        return $display;
    }

    private function getFormatedNotification($content)
    {
        $begining = '<div id="acym__wysid__template" class="cell"><table class="body"><tbody><tr><td align="center" class="center acym__wysid__template__content" valign="top" style="background-color: rgb(239, 239, 239); padding: 40px 0 120px 0;"><center><table align="center" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td class="acym__wysid__row ui-droppable ui-sortable" style="min-height: 0px; display: table-cell;"><table class="row acym__wysid__row__element" bgcolor="#dadada" border="0" cellpadding="0" cellspacing="0"><tbody style="background-color: rgb(218,218,218);" bgcolor="#ffffff"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th"><table class="acym__wysid__column" style="min-height: 0px; display: table;" border="0" cellpadding="0" cellspacing="0"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline: rgb(0,163,254) dashed 0px; outline-offset: -1px;"><span class="acy-editor__space acy-editor__space--focus" style="display: block; padding: 0px; margin: 0px; height: 10px;"></span></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0"><tbody style="background-color: transparent;" bgcolor="#ffffff"><tr><th class="small-12 medium-12 large-12 columns"><table class="acym__wysid__column" style="min-height: 0px; display: table;" border="0" cellpadding="0" cellspacing="0"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline: rgb(0, 163, 254) dashed 0px; outline-offset: -1px;"><div class="acym__wysid__tinymce--text mce-content-body" style="position: relative;" spellcheck="false">';
        $ending = '</div></td></tr></tbody></table></th></tr></tbody></table><table class="row acym__wysid__row__element" bgcolor="#dadada" style="position: relative; z-index: 100; top: 0; left: 0;" border="0" cellpadding="0" cellspacing="0"><tbody style="background-color: rgb(218, 218, 218);" bgcolor="#ffffff"><tr><th class="small-12 medium-12 large-12 columns acym__wysid__row__element__th"><table class="acym__wysid__column" style="min-height: 0px; display: table;" border="0" cellpadding="0" cellspacing="0"><tbody class="ui-sortable" style="min-height: 0px; display: table-row-group;"><tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;"><td class="large-12 acym__wysid__column__element__td" style="outline: rgb(0, 163, 254) dashed 0px; outline-offset: -1px;"><span class="acy-editor__space acy-editor__space--focus" style="display: block; padding: 0px; margin: 0px; height: 10px;"></span></td></tr></tbody></table></th></tr></tbody></table></td></tr></tbody></table></center></td></tr></tbody></table></div>';

        return $begining.$content.$ending;
    }

    private function getCurrentUser()
    {
        $currentCMSId = acym_currentUserId();
        $userClass = new UserClass();
        $user = $userClass->getOneByCMSId($currentCMSId);
        if (!empty($user)) {
            return $user;
        }

        $currentCMSEmail = acym_currentUserEmail();
        $user = $userClass->getOneByEmail($currentCMSEmail);
        if (!empty($user)) {
            return $user;
        }

        $newUser = new \stdClass();
        $newUser->email = $currentCMSEmail;
        $newUser->confirmed = 1;
        $newUser->cms_id = $currentCMSId;
        $newUser->id = $userClass->save($newUser);

        return $userClass->getOneById($newUser->id);
    }
}
