<?php

namespace AcyMailing\Controllers\Bounces;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\RuleClass;
use AcyMailing\Helpers\BounceHelper;
use AcyMailing\Helpers\CronHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\UpdateHelper;
use AcyMailing\Helpers\WorkflowHelper;

trait Listing
{
    public function listing(): void
    {
        $this->storeRedirectListing(true);
    }

    public function prepareToolbar(array &$data, string $element = 'bounces'): void
    {
        $toolbarHelper = new ToolbarHelper();
        if ($element === 'bounces') {
            $toolbarHelper->addButton(
                'ACYM_CONFIGURE',
                [
                    'data-task' => 'config',
                    'id' => 'acym__bounce__button__config',
                    'type' => 'button',
                ],
                'settings'
            );
            $toolbarHelper->addButton(
                'ACYM_RESET_DEFAULT_RULES',
                [
                    'data-task' => 'reinstall',
                    'type' => 'button',
                    'data-confirmation-message' => 'ACYM_ARE_YOU_SURE',
                ],
                'repeat'
            );
            $toolbarHelper->addButton('ACYM_RUN_BOUNCE_HANDLING', ['data-task' => 'test'], 'play_arrow');
            $toolbarHelper->addButton('ACYM_CREATE', ['data-task' => 'rule', 'type' => 'submit'], 'add', true);


            $remindMe = json_decode($this->config->get('remindme', '[]'), true);
            $messageIdentifier = 'bounce_rules_'.UpdateHelper::BOUNCE_VERSION;

            if (!in_array($messageIdentifier, $remindMe) && $this->config->get('bounceVersion', 0) < UpdateHelper::BOUNCE_VERSION) {
                $message = acym_translation('ACYM_NEW_BOUNCE_RULES');
                $message .= '<a href="#" class="acym__do__not__remindme acym__do__not__remindme__info" title="'.$messageIdentifier.'">';
                $message .= acym_translation('ACYM_DO_NOT_REMIND_ME');
                $message .= '</a>';

                acym_enqueueMessage($message, 'info', false);
            }
        } else {
            $toolbarHelper = new ToolbarHelper();
            $toolbarHelper->addSearchBar($data['search'], 'mailboxes_search', 'ACYM_SEARCH');
            $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'mailboxAction'], 'add', true);
            $data['toolbar'] = $toolbarHelper;
        }

        $data['toolbar'] = $toolbarHelper;
    }

    public function bounces(): void
    {
        $data = [];

        //__START__enterprise_
        if (acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'listing');
            $ruleClass = new RuleClass();
            $listsClass = new ListClass();

            $bounceRules = $ruleClass->getAll();

            $finalRule = null;
            foreach ($bounceRules as $key => $oneRule) {
                if ($oneRule->id === RuleClass::FINAL_RULE_ID) {
                    $finalRule = $oneRule;
                    unset($bounceRules[$key]);
                    break;
                }
            }

            if ($finalRule !== null) {
                $bounceRules[] = $finalRule;
            }

            $data = [
                'allRules' => $bounceRules,
                'lists' => $listsClass->getAllWithIdName(),
            ];
        }
        //__END__enterprise_

        $data['workflowHelper'] = new WorkflowHelper();
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'splashscreen');
            $data['isEnterprise'] = false;
        }

        $this->prepareToolbar($data);

        parent::display($data);
    }

    // When one of the raw of the listing is moved we set the ordering
    public function ajaxSetOrdering(): void
    {
        $order = json_decode(acym_getVar('string', 'order'), true);
        if (empty($order)) {
            $order = [];
        }

        $i = 1;
        $error = false;
        foreach ($order as $rule) {
            $query = 'UPDATE #__acym_rule SET `ordering` = '.intval($i).' WHERE `id` = '.intval($rule);
            $error = acym_query($query) < 0 || $error;
            $i++;
        }

        acym_sendAjaxResponse('', [], !$error);
    }

    // Click on the button Run Bounce Handling
    public function test(): void
    {
        $ruleClass = new RuleClass();

        if ($ruleClass->getOrderingNumber() < 1) {
            acym_enqueueMessage(acym_translation('ACYM_NO_RULES'), 'error');

            $this->listing();

            return;
        }

        acym_increasePerf();
        $bounceHelper = new BounceHelper();
        $bounceHelper->report = true;

        if ($bounceHelper->init() && $bounceHelper->connect()) {
            $this->runBounce = true;
        } else {
            $errors = $bounceHelper->getErrors();
            if (!empty($errors)) {
                acym_enqueueMessage($errors, 'error');
                $errorString = implode(' ', $errors);
                $port = $this->config->get('bounce_port');
                if (preg_match('#certificate#i', $errorString) && !$this->config->get('bounce_certif', false)) {
                    //Self signed certificate issue
                    acym_enqueueMessage(acym_translationSprintf('ACYM_YOU_MAY_TURN_ON_OPTION', '<i>'.acym_translation('ACYM_SELF_SIGNED_CERTIFICATE').'</i>'), 'warning');
                } elseif (!empty($port) && !in_array($port, ['993', '143', '110'])) {
                    //Not the right port... ?
                    acym_enqueueMessage(acym_translation('ACYM_BOUNCE_WRONG_PORT'), 'warning');
                }
            }
        }

        $this->listing();
    }

    // Process bounce handling after clicking on the button Run Bounce Handling
    public function process(): void
    {
        acym_increasePerf();

        $bounceHelper = new BounceHelper();
        $bounceHelper->report = true;
        if (!$bounceHelper->init()) {
            return;
        }
        if (!$bounceHelper->connect()) {
            acym_display($bounceHelper->getErrors(), 'error');

            return;
        }
        $disp = "<html>\n<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />\n";
        $disp .= '<title>'.addslashes(acym_translation('ACYM_BOUNCE_PROCESS')).'</title>'."\n";
        $disp .= "<style>body{font-size:12px;font-family: Arial,Helvetica,sans-serif;padding-top:30px;}</style>\n</head>\n<body>";
        echo $disp;

        acym_display(acym_translationSprintf('ACYM_BOUNCE_CONNECT_SUCC', $this->config->get('bounce_username')), 'success');
        $nbMessages = $bounceHelper->getNBMessages();
        $nbMessagesReport = acym_translationSprintf('ACYM_NB_MAIL_MAILBOX', $nbMessages);
        acym_display($nbMessagesReport, 'info');

        //that should not happen as we check it before anyway...
        if (empty($nbMessages)) {
            exit;
        }

        $bounceHelper->handleMessages();

        //Load the cron class to save the report if there is one
        $cronHelper = new CronHelper();
        $cronHelper->saveReport([$nbMessagesReport], $bounceHelper->messages);

        if ($this->config->get('bounce_max', 0) != 0 && $nbMessages > $this->config->get('bounce_max', 0)) {
            //We still have some messages...
            $url = acym_completeLink('bounces&task=process&continuebounce=1', true, true);
            if (acym_getVar('int', 'continuebounce')) {
                //We already started the bounce handling and we should resume it until the end...
                echo '<script type="text/javascript">document.location.href=\''.$url.'\';</script>';
            } else {
                //We should propose to the user to resume the bounce process until the end...
                echo '<div style="padding:20px;"><a href="'.$url.'">'.acym_translation('ACYM_CLICK_HANDLE_ALL_BOUNCES').'</a></div>';
            }
        }

        //We need to finish the current page properly
        echo '</body></html>';
        while ($bounceHelper->obend-- > 0) {
            ob_start();
        }
        exit;
    }

    // Click on the button Reset to default rules
    public function reinstall(): void
    {
        $ruleClass = new RuleClass();
        $ruleClass->cleanTable();

        $updateHelper = new UpdateHelper();
        $updateHelper->installBounceRules();

        $this->listing();
    }

    // Click on the button configuration
    public function config(): void
    {
        acym_redirect(acym_completeLink('configuration', false, true));
    }

    public function delete(): void
    {
        $rulesSelected = acym_getVar('array', 'elements_checked', []);

        if (in_array(RuleClass::FINAL_RULE_ID, $rulesSelected)) {
            acym_enqueueMessage(acym_translation('ACYM_CANNOT_DELETE_FINAL_RULE'), 'error');

            $key = array_search(RuleClass::FINAL_RULE_ID, $rulesSelected);
            if ($key !== false) {
                unset($rulesSelected[$key]);
            }
        }

        $ruleClass = new RuleClass();
        $ruleClass->delete($rulesSelected);

        $this->listing();
    }

    public function setInactive(): void
    {
        $rulesSelected = acym_getVar('array', 'elements_checked', []);
        $ruleClass = new RuleClass();
        $ruleClass->setInactive($rulesSelected);

        $this->listing();
    }

    public function setActive(): void
    {
        $rulesSelected = acym_getVar('array', 'elements_checked', []);
        $ruleClass = new RuleClass();
        $ruleClass->setActive($rulesSelected);

        $this->listing();
    }
}
