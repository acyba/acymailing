<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailboxClass;
use AcyMailing\Classes\RuleClass;
use AcyMailing\Helpers\BounceHelper;
use AcyMailing\Helpers\CronHelper;
use AcyMailing\Helpers\MailboxHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\SplashscreenHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\UpdateHelper;
use AcyMailing\Helpers\WorkflowHelper;
use AcyMailing\Libraries\acymController;
use AcyMailing\Types\DelayType;

class BouncesController extends acymController
{
    private $runBounce = false;
    private $mailboxReport = [];

    public function __construct()
    {
        $this->defaulttask = 'bounces';
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_MAILBOX_ACTIONS')] = acym_completeLink('bounces');
        $this->storeRedirectListing();
    }

    public function listing()
    {
        $this->storeRedirectListing(true);
    }

    public function storeRedirectListing($fromListing = false)
    {
        $variableName = 'ctrl_mailboxes_stored';
        acym_session();
        $taskToStore = [
            '',
            'bounces',
            'mailboxes',
        ];
        $currentTask = acym_getVar('string', 'task', '');
        if (!in_array($currentTask, $taskToStore) && !$fromListing) {
            return;
        }

        if ((empty($currentTask) || !in_array($currentTask, $taskToStore)) && !empty($_SESSION[$variableName])) {
            $taskToGo = is_array($_SESSION[$variableName]) ? $_SESSION[$variableName]['task'] : $_SESSION[$variableName];
            $link = acym_completeLink('bounces&task='.$taskToGo, false, true);
            if($this->runBounce){
                $link .= '&runBounce=1';
            }

            acym_redirect($link);
        } else {
            if (empty($currentTask) || !in_array($currentTask, $taskToStore)) {
                $currentTask = 'bounces';
            }
            $_SESSION[$variableName] = $currentTask;
        }

        $taskToCall = is_array($currentTask) ? $currentTask['task'] : $currentTask;
        if ($fromListing && method_exists($this, $taskToCall)) {
            $this->$taskToCall();
        }
    }

    public function bounces()
    {
        $this->currentClass = new RuleClass();
        $splashscreenHelper = new SplashscreenHelper();
        $data = [];


        $data['workflowHelper'] = new WorkflowHelper();
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'splashscreen');
            $data['isEnterprise'] = false;
        }

        $this->prepareToolbar($data);

        parent::display($data);
    }

    public function prepareToolbar(&$data, $element = 'bounces')
    {
        $toolbarHelper = new ToolbarHelper();
        if ($element === 'bounces') {
            $toolbarHelper->addButton('ACYM_CONFIGURE', ['data-task' => 'config', 'id' => 'acym__bounce__button__config', 'type' => 'button'], 'settings');
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
        } else {
            $toolbarHelper = new ToolbarHelper();
            $toolbarHelper->addSearchBar($data['search'], 'mailboxes_search', 'ACYM_SEARCH');
            $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'mailboxAction'], 'add', true);
            $data['toolbar'] = $toolbarHelper;
        }

        $data['toolbar'] = $toolbarHelper;
    }

    public function rule()
    {
        $ruleClass = new RuleClass();
        acym_setVar('layout', 'rule');
        $ruleId = acym_getVar('int', 'id', 0);
        $listsClass = new ListClass();

        if (!empty($ruleId)) {
            $rule = $ruleClass->getOneById($ruleId);
            $this->breadcrumb[acym_translation($rule->name)] = acym_completeLink('bounces&task=rule&id='.$ruleId);
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW')] = acym_completeLink('bounces&task=rule');
            $rule = new \stdClass();
            $rule->name = '';
            $rule->active = 1;
            $rule->regex = '';
            $rule->executed_on = [];
            $rule->action_message = [];
            $rule->action_user = [];
            $rule->increment_stats = 0;
            $rule->execute_action_after = 0;
        }

        $data = [
            'id' => $ruleId,
            'lists' => $listsClass->getAllWithIdName(),
            'rule' => $rule,
        ];

        parent::display($data);
    }

    public function applyRule()
    {
        $this->storeRule();
        $this->rule();
    }

    public function saveRule()
    {
        $this->storeRule();
        $this->listing();
    }

    /**
     * Save the rule on click
     */
    public function storeRule()
    {
        $rule = acym_getVar('array', 'bounce');

        $ruleClass = new RuleClass();

        $rule['executed_on'] = !empty($rule['executed_on']) ? json_encode($rule['executed_on']) : '[]';

        if (!empty($rule['action_user'])) {
            if (in_array('subscribe_user', $rule['action_user'])) {
                $rule['action_user']['subscribe_user_list'] = $rule['subscribe_user_list'];
            }
        }
        unset($rule['subscribe_user_list']);

        if (!empty($rule['action_message']) && !in_array('forward_message', $rule['action_message'])) {
            unset($rule['action_message']['forward_to']);
        }

        if (empty($rule['id'])) {
            $rule['ordering'] = $ruleClass->getOrderingNumber() + 1;
        }

        $ruleObject = new \stdClass();
        $ruleObject->executed_on = '[]';
        $ruleObject->action_message = '[]';
        $ruleObject->action_user = '[]';

        foreach ($rule as $column => $value) {
            acym_secureDBColumn($column);
            if (is_array($value) || is_object($value)) {
                $ruleObject->$column = json_encode($value);
            } else {
                $ruleObject->$column = strip_tags($value);
            }
        }

        $res = $ruleClass->save($ruleObject);

        if (!$res) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            acym_setVar('id', $res);
        }
    }

    public function setOrdering()
    {
        $order = json_decode(acym_getVar('string', 'order'));
        $i = 1;
        $error = false;
        foreach ($order as $rule) {
            $query = 'UPDATE #__acym_rule SET `ordering` = '.intval($i).' WHERE `id` = '.intval($rule);
            $error = acym_query($query) >= 0 ? false : true;
            $i++;
        }
        if ($error) {
            echo 'error';
        } else {
            echo 'updated';
        }
        exit;
    }

    //__START__enterprise_
    public function process()
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
        $cronHelper->messages[] = $nbMessagesReport;
        $cronHelper->detailMessages = $bounceHelper->messages;
        $cronHelper->saveReport();

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

    public function saveconfig()
    {
        $this->_saveconfig();

        return $this->listing();
    }

    public function _saveconfig()
    {
        acym_checkToken();

        $newConfig = acym_getVar('array', 'config', [], 'POST');
        if (!empty($newConfig['bounce_username'])) {
            $newConfig['bounce_username'] = acym_punycode($newConfig['bounce_username']);
        }

        //We set the next date...
        $newConfig['auto_bounce_next'] = min($this->config->get('auto_bounce_last', time()), time()) + $newConfig['auto_bounce_frequency'];

        $status = $this->config->save($newConfig);

        if ($status) {
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'info');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        }

        $this->config->load();
    }

    //Display bounce chart...
    public function chart()
    {
        acym_setVar('layout', 'chart');

        parent::display();
    }

    public function test()
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

        if ($bounceHelper->init()) {
            if ($bounceHelper->connect()) {
                $this->runBounce = true;
            } else {
                $errors = $bounceHelper->getErrors();
                if (!empty($errors)) {
                    acym_enqueueMessage($errors, 'error');
                    $errorString = implode(' ', $errors);
                    $port = $this->config->get('bounce_port', '');
                    if (preg_match('#certificate#i', $errorString) && !$this->config->get('bounce_certif', false)) {
                        //Self signed certificate issue
                        acym_enqueueMessage(acym_translationSprintf('ACYM_YOU_MAY_TURN_ON_OPTION', '<i>'.acym_translation('ACYM_SELF_SIGNED_CERTIFICATE').'</i>'), 'warning');
                    } elseif (!empty($port) && !in_array($port, ['993', '143', '110'])) {
                        //Not the right port... ?
                        acym_enqueueMessage(acym_translation('ACYM_BOUNCE_WRONG_PORT'), 'warning');
                    }
                }
            }
        }

        $this->listing();
    }

    public function reinstall()
    {
        $ruleClass = new RuleClass();
        $ruleClass->cleanTable();

        $updateHelper = new UpdateHelper();
        $updateHelper->installBounceRules();

        return $this->listing();
    }

    public function config()
    {
        acym_redirect(acym_completeLink('configuration', false, true));
    }

    public function delete()
    {
        $rulesSelected = acym_getVar('array', 'elements_checked');

        $ruleClass = new RuleClass();
        $ruleClass->delete($rulesSelected);

        $this->listing();
    }

    public function passSplash()
    {
        $splashscreenHelper = new SplashscreenHelper();
        $splashscreenHelper->setDisplaySplashscreenForViewName('bounces', 0);

        $this->listing();
    }

    private function prepareMailboxesActions(&$data)
    {
        if (empty($data['allMailboxes'])) {
            return;
        }

        foreach ($data['allMailboxes'] as $key => $oneMailbox) {
            $data['allMailboxes'][$key]->actionsRendered = [];

            $actions = json_decode($oneMailbox->actions, true);
            if (empty($actions)) {
                continue;
            }

            // We build the actions to display in the listing
            $actionsRendered = [];
            foreach ($actions as $action) {
                acym_trigger('onAcymMailboxActionSummaryListing', [&$action, &$actionsRendered]);
            }

            $data['allMailboxes'][$key]->actionsRendered = $actionsRendered;
        }
    }

    public function prepareMailboxesListing(&$data)
    {
        // Prepare the pagination
        $mailboxesPerPage = $data['pagination']->getListLimit();
        $page = $this->getVarFiltersListing('int', 'mailboxes_pagination_page', 1);
        $status = $data['status'];

        // Get the matching mailboxes
        $matchingMailboxes = $this->getMatchingElementsFromData(
            [
                'ordering' => $data['ordering'],
                'search' => $data['search'],
                'elementsPerPage' => $mailboxesPerPage,
                'offset' => ($page - 1) * $mailboxesPerPage,
                'ordering_sort_order' => $data['orderingSortOrder'],
                'status' => $status,
            ],
            $status,
            $page
        );

        // End pagination
        $totalElement = $matchingMailboxes['total'];
        $data['allStatusFilters'] = [
            'all' => $matchingMailboxes['total']->total,
            'active' => $matchingMailboxes['total']->totalActive,
            'inactive' => $matchingMailboxes['total']->total - $matchingMailboxes['total']->totalActive,
        ];
        $data['pagination']->setStatus($totalElement->total, $page, $mailboxesPerPage);
        $data['allMailboxes'] = $matchingMailboxes['elements'];
    }

    private function getAllParamsRequest(&$data)
    {
        $data['search'] = $this->getVarFiltersListing('string', 'mailboxes_search', '');
        $data['status'] = $this->getVarFiltersListing('string', 'mailboxes_status', '');
        $data['ordering'] = $this->getVarFiltersListing('string', 'mailboxes_ordering', 'id');
        $data['orderingSortOrder'] = $this->getVarFiltersListing('string', 'mailboxes_ordering_sort_order', 'desc');
    }

    public function mailboxes()
    {
        acym_setVar('layout', 'mailboxes');
        acym_setVar('task', 'mailboxes');
        $this->currentClass = new MailboxClass();

        $data = [
            'pagination' => new PaginationHelper(),
            'workflowHelper' => new WorkflowHelper(),
        ];
        $this->getAllParamsRequest($data);
        $this->prepareMailboxesListing($data);
        $this->prepareMailboxesActions($data);
        $this->prepareToolbar($data, 'mailboxes');

        parent::display($data);
    }

    private function mailboxDoListingAction($action)
    {
        $mailboxClass = new MailboxClass();
        if (!method_exists($mailboxClass, $action)) {
            return;
        }

        $mailboxActionSelected = acym_getVar('int', 'elements_checked');

        if (empty($mailboxActionSelected)) {
            return;
        }

        $mailboxClass->$action($mailboxActionSelected);

        $this->mailboxes();
    }

    public function duplicateMailboxAction()
    {
        $this->mailboxDoListingAction('duplicate');
    }

    public function deleteMailboxAction()
    {
        $this->mailboxDoListingAction('delete');
    }

    public function mailboxAction()
    {
        $mailboxClass = new MailboxClass();
        acym_setVar('layout', 'mailbox_action');
        $mailboxId = acym_getVar('int', 'mailboxId', 0);
        $listsClass = new ListClass();

        if (!empty($mailboxId)) {
            $mailboxAction = $mailboxClass->getOneById($mailboxId);
            $this->breadcrumb[acym_translation($mailboxAction->name)] = acym_completeLink('bounces&task=mailboxAction&mailboxId='.$mailboxId);
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW')] = acym_completeLink('bounces&task=mailboxAction');
            $mailboxAction = new \stdClass();
            $mailboxAction->name = '';
            $mailboxAction->active = 0;
            $mailboxAction->frequency = 900;
            $mailboxAction->description = '';

            $mailboxAction->server = '';
            $mailboxAction->username = '';
            $mailboxAction->password = '';
            $mailboxAction->connection_method = 'imap';
            $mailboxAction->secure_method = 'ssl';
            $mailboxAction->port = '';
            $mailboxAction->self_signed = 1;

            $mailboxAction->delete_wrong_emails = 0;
            $mailboxAction->conditions = [
                'sender' => '',
                'specific' => '',
                'groups' => '',
                'lists' => '',
                'subject' => '',
                'subject_text' => '',
                'subject_regex' => '',
                'subject_remove' => 1,
            ];

            $mailboxAction->actions = [];
            $mailboxAction->senderfrom = 0;
            $mailboxAction->senderto = 0;
        }

        if (empty($mailboxAction->conditions['groups'])) {
            $mailboxAction->conditions['groups'] = [];
        }

        if (empty($mailboxAction->conditions['lists'])) {
            $mailboxAction->conditions['lists'] = [];
        }

        acym_trigger('onAcymMailboxActionDefine', [&$actions]);

        $actionOptions = ['' => acym_translation('ACYM_CHOOSE_ACTION')];
        $actionParameters = '';

        foreach ($actions as $key => $oneAction) {
            $actionOptions[$key] = $oneAction->name;
            $actionParameters .= '<div class="acym__mailbox__edition__action__one__parameters '.$key.' margin-top-1">'.$oneAction->option.'</div>';
        }

        $initialAction = acym_select(
            $actionOptions,
            'acym_action[__num__][action]',
            '',
            [
                'class' => 'acym__select acym__mailbox__edition__action__one__choice',
                'acym-data-infinite' => '',
            ]
        );
        $initialAction .= $actionParameters;

        $data = [
            'mailboxId' => $mailboxId,
            'mailboxAction' => $mailboxAction,
            'delayType' => new DelayType(),
            'groups' => acym_getGroups(),
            'lists' => $listsClass->getAllWithIdName(),
            'initialAction' => $initialAction,
        ];

        parent::display($data);
    }

    public function applyMailboxAction()
    {
        $this->storeMailboxAction();
        $this->mailboxAction();
    }

    public function saveMailboxAction()
    {
        $this->storeMailboxAction();
        $this->listing();
    }

    public function storeMailboxAction()
    {
        $mailbox = acym_getVar('array', 'mailbox', []);
        $mailboxClass = new MailboxClass();
        $mailboxObject = new \stdClass();

        foreach ($mailbox as $column => $value) {
            acym_secureDBColumn($column);
            if (is_array($value) || is_object($value)) {
                $mailboxObject->$column = json_encode($value);
            } else {
                $mailboxObject->$column = $value;
            }
        }

        $actions = acym_getVar('array', 'acym_action', []);
        $mailboxObject->actions = [];
        foreach ($actions as $oneAction) {
            if (empty($oneAction['action'])) {
                continue;
            }

            $mailboxObject->actions[] = [$oneAction['action'] => $oneAction[$oneAction['action']]];
        }
        $mailboxObject->actions = json_encode($mailboxObject->actions);

        if (!empty($mailboxObject->password) && trim($mailboxObject->password, '*') === '') {
            unset($mailboxObject->password);
        }

        $mailboxId = $mailboxClass->save($mailboxObject);

        if (empty($mailboxId)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'));
            acym_setVar('mailboxId', $mailboxId);
        }
    }

    function exceptionsErrorHandler($severity, $message, $filename, $lineno)
    {
        $this->mailboxReport[] = $message.' in '.$filename.' on line '.$lineno;
    }

    public function testMailboxAction()
    {
        $mailbox = acym_getVar('array', 'mailbox', []);

        if (empty($mailbox)) {
            acym_sendAjaxResponse(acym_translation('ACYM_NO_CONFIGURATION'), [], false);
        }

        $mailbox = (object)$mailbox;

        // If this is not a new mailbox, we need to get the password from the database
        if (!empty($mailbox->id)) {
            $mailboxClass = new MailboxClass();
            $mailboxFromDatabase = $mailboxClass->getOneById($mailbox->id);

            // We check if the password has not changed
            if ($mailbox->password === str_repeat('*', strlen($mailboxFromDatabase->password))) {
                $mailbox->password = $mailboxFromDatabase->password;
            }
        }

        set_error_handler([$this, 'exceptionsErrorHandler']);

        $mailboxHelper = new MailboxHelper();
        if ($mailboxHelper->isConnectionValid($mailbox)) {
            acym_sendAjaxResponse(acym_translation('ACYM_CONNECTION_SUCCESSFUL'));
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_CONNECTION_FAILED'), ['report' => $this->mailboxReport], false);
        }
    }
}
