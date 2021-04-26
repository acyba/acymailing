<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\RuleClass;
use AcyMailing\Helpers\BounceHelper;
use AcyMailing\Helpers\CronHelper;
use AcyMailing\Helpers\SplashscreenHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\UpdateHelper;
use AcyMailing\Libraries\acymController;

class BouncesController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_BOUNCE_HANDLING')] = acym_completeLink('bounces');
        $this->currentClass = new RuleClass();
    }

    public function listing()
    {
        $splashscreenHelper = new SplashscreenHelper();
        $data = [];


        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'splashscreen');
            $data['isEnterprise'] = false;
        }

        $this->prepareToolbar($data);

        parent::display($data);
    }

    public function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addButton(acym_translation('ACYM_CONFIGURE'), ['data-task' => 'config', 'id' => 'acym__bounce__button__config', 'type' => 'button'], 'settings');
        $toolbarHelper->addButton(acym_translation('ACYM_RESET_DEFAULT_RULES'), ['data-task' => 'reinstall', 'type' => 'button'], 'repeat');
        $toolbarHelper->addButton(acym_translation('ACYM_RUN_BOUNCE_HANDLING'), ['data-task' => 'test'], 'play_arrow');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit', 'type' => 'submit'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function edit()
    {
        $ruleClass = new RuleClass();
        acym_setVar("layout", "edit");
        $ruleId = acym_getVar("int", "id", 0);
        $listsClass = new ListClass();

        $rule = "";

        if (!empty($ruleId)) {
            $rule = $ruleClass->getOneById($ruleId);
            $this->breadcrumb[acym_translation($rule->name)] = acym_completeLink('bounces&task=edit&id='.$ruleId);
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW')] = acym_completeLink('bounces&task=edit');
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
            "id" => $ruleId,
            "lists" => $listsClass->getAllWithIdName(),
            "rule" => $rule,
        ];

        parent::display($data);
    }

    public function apply()
    {
        $this->saveRule();
        $this->edit();
    }

    public function save()
    {
        $this->saveRule();
        $this->listing();
    }

    /**
     * Save the rule on click
     */
    public function saveRule()
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
                echo '<script type="text/javascript" language="javascript">document.location.href=\''.$url.'\';</script>';
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

        return parent::display();
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
                acym_setVar('run_bounce', true);
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
}
