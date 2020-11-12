<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\EditorHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\MigrationHelper;
use AcyMailing\Helpers\UpdateHelper;
use AcyMailing\Libraries\acymController;
use AcyMailing\Classes\CampaignClass;

class DashboardController extends acymController
{
    public function __construct()
    {
        parent::__construct();

        $this->loadScripts = [
            'walk_through' => ['editor-wysid'],
        ];
    }

    public function listing()
    {
        acym_setVar('layout', 'listing');

        if ($this->migration()) return;
        if ($this->feedback()) return;
        if ($this->walkthrough()) return;

        $campaignClass = new CampaignClass();
        $mailStatClass = new MailStatClass();
        $statsController = new StatsController();

        $data = [];
        $data['page_title'] = true;
        $data['mail_filter'] = '';
        $data['stats_export'] = '';
        $data['selectedMailid'] = '';
        $data['show_date_filters'] = false;
        $data['campaignsScheduled'] = $campaignClass->getCampaignForDashboard();
        $data['sentMails'] = $mailStatClass->getAllMailsForStats();

        $statsController->preparecharts($data);
        $statsController->prepareDefaultRoundCharts($data);
        $statsController->prepareDefaultLineChart($data);

        parent::display($data);
    }

    public function stepSubscribe()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'subscribe',
            'email' => acym_currentUserEmail(),
        ];

        parent::display($data);
    }

    public function saveStepSubscribe()
    {
        $this->_saveWalkthrough(['step' => 'stepEmail']);
        $this->stepEmail();
    }

    public function stepEmail()
    {
        acym_setVar('layout', 'walk_through');

        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);

        $mailClass = new MailClass();
        $updateHelper = new UpdateHelper();

        $mail = empty($walkthroughParams['mail_id']) ? $mailClass->getOneByName(acym_translation($updateHelper::FIRST_EMAIL_NAME_KEY)) : $mailClass->getOneById($walkthroughParams['mail_id']);

        if (empty($mail)) {
            if (!$updateHelper->installNotifications()) {
                $this->stepSubscribe();

                return;
            }
            $mail = $mailClass->getOneByName(acym_translation($updateHelper::FIRST_EMAIL_NAME_KEY));
        }

        $editor = new EditorHelper();
        $editor->content = $mail->body;
        $editor->autoSave = '';
        $editor->settings = $mail->settings;
        $editor->stylesheet = $mail->stylesheet;
        $editor->editor = 'acyEditor';
        $editor->mailId = $mail->id;
        $editor->walkThrough = true;

        $data = [
            'step' => 'email',
            'editor' => $editor,
            'social_icons' => $this->config->get('social_icons', '{}'),
            'mail' => $mail,
        ];

        parent::display($data);
    }

    public function saveAjax()
    {
        $mailController = new MailsController();

        $isWellSaved = $mailController->store(true);
        echo json_encode(['error' => $isWellSaved ? '' : acym_translation('ACYM_ERROR_SAVING'), 'data' => $isWellSaved]);
        exit;
    }

    public function saveStepEmail()
    {
        $mailController = new MailsController();

        $mailId = $mailController->store();

        if (empty($mailId)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            $this->passWalkThrough();
        } else {
            $this->_saveWalkthrough(['step' => 'stepList', 'mail_id' => $mailId]);
            $this->stepList();
        }
    }

    public function stepList()
    {
        acym_setVar('layout', 'walk_through');
        $listClass = new ListClass();
        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);

        $users = empty($walkthroughParams['list_id']) ? [] : $listClass->getSubscribersForList($walkthroughParams['list_id'], 0, 500);
        $usersReturn = [];
        if (!empty($users)) {
            foreach ($users as $user) {
                $usersReturn[] = $user->email;
            }
        }

        if (empty($usersReturn)) $usersReturn[] = acym_currentUserEmail();

        $data = [
            'step' => 'list',
            'users' => $usersReturn,
        ];

        parent::display($data);
    }

    public function saveStepList()
    {
        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
        if (empty($walkthroughParams['list_id'])) {
            $testingList = new \stdClass();
            $testingList->name = acym_translation('ACYM_TESTING_LIST');
            $testingList->visible = 0;
            $testingList->active = 1;
            $testingList->color = '#94d4a6';

            $listClass = new ListClass();
            $listId = $listClass->save($testingList);
        } else {
            $listId = $walkthroughParams['list_id'];
        }

        if (empty($listId)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVE_LIST'), 'error');
            $this->passWalkThrough();

            return;
        }

        $userClass = new UserClass();

        $addresses = acym_getVar('array', 'addresses', []);
        $addresses = array_unique($addresses);
        $wrongAddresses = [];
        foreach ($addresses as $oneAddress) {
            if (!acym_isValidEmail($oneAddress)) {
                $wrongAddresses[] = $oneAddress;
                continue;
            }

            $existing = $userClass->getOneByEmail($oneAddress);
            if (empty($existing)) {
                $newUser = new \stdClass();
                $newUser->email = $oneAddress;
                $newUser->confirmed = 1;
                acym_setVar('acy_source', 'walkthrough');
                $userId = $userClass->save($newUser);
            } else {
                $userId = $existing->id;
            }

            $userClass->subscribe($userId, $listId);
        }

        if (!empty($wrongAddresses)) acym_enqueueMessage(acym_translation_sprintf('ACYM_WRONG_ADDRESSES', implode(', ', $wrongAddresses)), 'warning');

        $nextStep = acym_isLocalWebsite() ? 'stepGmail' : 'stepPhpmail';

        $this->_saveWalkthrough(['step' => $nextStep, 'list_id' => $listId]);
        $this->$nextStep();
    }

    public function stepPhpmail()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'phpmail',
            'userEmail' => acym_currentUserEmail(),
        ];

        parent::display($data);
    }

    public function saveStepPhpmail()
    {
        if (!$this->_saveFrom()) {
            $this->stepPhpmail();

            return;
        }

        $mailerMethod = ['mailer_method' => 'phpmail'];
        if (false === $this->config->save($mailerMethod)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING', 'error'));
            $this->stepPhpmail();

            return;
        }

        if (false === $this->_sendFirstEmail()) {
            $this->stepPhpmail();

            return;
        }

        $this->_saveWalkthrough(['step' => 'stepResult']);
        $this->stepResult();
    }

    public function stepGmail()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'gmail',
            'userEmail' => acym_currentUserEmail(),
        ];

        parent::display($data);
    }

    public function saveStepGmail()
    {
        if (!$this->_saveFrom() || !$this->_saveGmailInformation()) {
            $this->stepGmail();

            return;
        }

        $this->_sendFirstEmail();

        $this->_saveWalkthrough(['step' => 'stepResult']);
        $this->stepResult();
    }

    public function stepResult()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'result',
        ];

        parent::display($data);
    }

    public function saveStepResult()
    {
        $result = acym_getVar('boolean', 'result');

        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);

        $stepFail = acym_isLocalWebsite() || !empty($walkthroughParams['step_fail']) ? 'stepFaillocal' : 'stepFail';

        $nextStep = $result ? 'stepSuccess' : $stepFail;
        $this->_saveWalkthrough(['step' => $nextStep]);

        $this->$nextStep();
    }

    public function stepSuccess()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'success',
        ];

        parent::display($data);
    }

    public function saveStepSuccess()
    {
        $this->passWalkThrough();
    }

    public function stepFaillocal()
    {
        acym_setVar('layout', 'walk_through');
        $data = [
            'step' => 'faillocal',
            'email' => acym_currentUserEmail(),
        ];
        parent::display($data);
    }

    public function saveStepFaillocal()
    {
        $this->_handleContactMe('stepFaillocal');
    }

    public function stepFail()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'fail',
            'email' => acym_currentUserEmail(),
        ];

        parent::display($data);
    }

    public function saveStepFail()
    {
        $choice = acym_getVar('cmd', 'choice', 'gmail');
        if ('gmail' === $choice) {
            $this->_saveGmailInformation();
            $this->_sendFirstEmail();
            $this->_saveWalkthrough(['step' => 'stepResult', 'step_fail' => true]);
            $this->stepResult();
        } else {
            $this->_handleContactMe('stepFail');
        }
    }

    private function _handleContactMe($fromFunction)
    {
        $email = acym_getVar('string', 'email');
        if (empty($email) || !acym_isValidEmail($email)) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_ADD_YOUR_EMAIL'), 'error');
            $this->$fromFunction();

            return;
        }

        if ($fromFunction == 'stepFaillocal' && acym_isLocalWebsite()) {
            $fromMessage = 'ACYM_GMAIL_TRY';
        } elseif ($fromFunction == 'stepFaillocal') {
            $fromMessage = 'ACYM_GMAIL_PHP_TRY';
        } else {
            $fromMessage = 'ACYM_PHP_TRY';
        }

        $handle = curl_init();
        $url = ACYM_UPDATEMEURL.'contact&task=contactme&email='.urlencode($email).'&version='.$this->config->get('version', '6').'&cms='.ACYM_CMS.'&message_key='.$fromMessage;
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($handle);
        curl_close($handle);
        $output = json_decode($output, true);
        if (!empty($output['error'])) {
            acym_enqueueMessage(acym_translation('ACYM_SOMETHING_WENT_WRONG_CONTACT_ON_ACYBA'), 'error');
            $this->passWalkThrough();
        } else {
            $this->_saveWalkthrough(['step' => 'stepSupport']);
            $this->stepSupport();
        }
    }

    public function stepSupport()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'support',
        ];

        parent::display($data);
    }

    public function saveStepSupport()
    {
        $this->passWalkThrough();
    }

    public function passWalkThrough()
    {
        $newConfig = new \stdClass();
        $newConfig->walk_through = 0;
        $this->config->save($newConfig);

        acym_redirect(acym_completeLink('users&task=import', false, true));
    }

    public function preMigration()
    {
        $elementToMigrate = acym_getVar('string', 'element');
        $helperMigration = new MigrationHelper();

        $result = $helperMigration->preMigration($elementToMigrate);

        if (!empty($result['isOk'])) {
            echo $result['count'];
        } else {
            echo 'ERROR : ';
            if (!empty($result['errorInsert'])) {
                echo strtoupper(acym_translation('ACYM_INSERT_ERROR'));
            }
            if (!empty($result['errorClean'])) {
                echo strtoupper(acym_translation('ACYM_CLEAN_ERROR'));
            }

            if (!empty($result['errors'])) {
                echo '<br>';

                foreach ($result['errors'] as $key => $oneError) {
                    echo '<br>'.$key.' : '.$oneError;
                }
            }
        }
        exit;
    }

    public function migrate()
    {
        $elementToMigrate = acym_getVar('string', 'element');
        $helperMigration = new MigrationHelper();
        $functionName = 'do'.ucfirst($elementToMigrate).'Migration';

        $result = $helperMigration->$functionName($elementToMigrate);

        if (!empty($result['isOk'])) {
            echo json_encode($result);
        } else {
            echo 'ERROR : ';
            if (!empty($result['errorInsert'])) {
                echo strtoupper(acym_translation('ACYM_INSERT_ERROR'));
            }
            if (!empty($result['errorClean'])) {
                echo strtoupper(acym_translation('ACYM_CLEAN_ERROR'));
            }

            if (!empty($result['errors'])) {
                echo '<br>';

                foreach ($result['errors'] as $key => $oneError) {
                    echo '<br>'.$key.' : '.$oneError;
                }
            }
        }
        exit;
    }

    public function migrationDone()
    {
        $newConfig = new \stdClass();
        $newConfig->migration = '1';
        $this->config->save($newConfig);

        $updateHelper = new UpdateHelper();
        $updateHelper->installNotifications();
        $updateHelper->installTemplates(true);
        $updateHelper->installOverrideEmails();

        $this->listing();
    }

    private function acym_existsAcyMailing59()
    {
        $allTables = acym_getTables();

        if (in_array(acym_getPrefix().'acymailing_config', $allTables)) {
            $queryVersion = 'SELECT `value` FROM #__acymailing_config WHERE `namekey` LIKE "version"';

            $version = acym_loadResult($queryVersion);

            if (version_compare($version, '5.9.0') >= 0) {
                return true;
            }
        }

        return false;
    }

    public function upgrade()
    {
        acym_setVar('layout', 'upgrade');

        $version = acym_getVar('string', 'version', 'enterprise');

        $data = ['version' => $version];

        parent::display($data);
    }

    public function migration()
    {
        if ($this->config->get('migration') == 0 && acym_existsAcyMailing59()) {
            acym_setVar('layout', 'migrate');
            parent::display();

            return true;
        }

        $newConfig = new \stdClass();
        $newConfig->migration = '1';
        $this->config->save($newConfig);

        return false;
    }

    public function feedback()
    {
        if ('wordpress' !== ACYM_CMS) return false;

        $installDate = $this->config->get('install_date', time());
        $remindme = json_decode($this->config->get('remindme', '[]'), true);

        if ($installDate < time() - 1814400 && !in_array('reviews', $remindme)) {
            $remindme[] = 'reviews';
            $this->config->save(['remindme' => json_encode($remindme)]);

            $this->config = acym_config();
            $remindme = json_decode($this->config->get('remindme', '[]'), true);
            if (in_array('reviews', $remindme)) {
                acym_setVar('layout', 'feedback');
                parent::display();

                return true;
            }
        }

        return false;
    }

    public function walkthrough()
    {
        if ($this->config->get('walk_through') == 1) {
            $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
            if (empty($walkthroughParams['step'])) {
                $this->stepSubscribe();
            } else {
                $this->{$walkthroughParams['step']}();
            }

            return true;
        }

        return false;
    }

    /**
     * Save 'from name' and 'from address mail' on creating email step in walkthrough
     *
     * @return bool
     */
    private function _saveFrom()
    {
        $fromName = acym_getVar('string', 'from_name', 'Test');
        $fromAddress = acym_getVar('string', 'from_address', 'test@test.com');

        $mailClass = new MailClass();
        $updateHelper = new UpdateHelper();

        $firstMail = $mailClass->getOneByName(acym_translation($updateHelper::FIRST_EMAIL_NAME_KEY));

        if (empty($firstMail)) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_REINSTALL_ACYMAILING'), 'error');

            return false;
        }

        $firstMail->from_name = $fromName;
        $firstMail->from_email = $fromAddress;

        if ($this->config->get('replyto_email') === '') {
            $firstMail->reply_to_name = $fromName;
            $firstMail->reply_to_email = $fromAddress;
        }

        $statusSaveMail = $mailClass->save($firstMail);

        if (empty($statusSaveMail)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');

            return false;
        }

        return true;
    }

    /**
     * Save Gmail address and Gmail password on creating email step in walkthrough
     *
     * @return bool
     */
    private function _saveGmailInformation()
    {
        $gmailAddress = acym_getVar('string', 'gmail_address', '');
        $gmailPassword = acym_getVar('string', 'gmail_password', '');

        if (empty($gmailAddress) || empty($gmailPassword)) {
            acym_enqueueMessage(acym_translation('ACYM_EMPTY_ADDRESS_OR_PASSWORD'), 'error');

            return false;
        }

        //We preset common information to make the walkthrough easier as possible!
        $newSmtpConfiguration = [
            'smtp_auth' => '1',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_keepalive' => '1',
            'smtp_port' => '465',
            'smtp_secured' => 'ssl',
            'smtp_username' => $gmailAddress,
            'smtp_password' => $gmailPassword,
            'mailer_method' => 'smtp',
        ];

        if (false === $this->config->save($newSmtpConfiguration)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING', 'error'));

            return false;
        }

        return true;
    }

    /**
     * Send the first email
     *
     * @return bool
     */
    private function _sendFirstEmail()
    {
        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
        $listClass = new ListClass();
        $mailClass = new MailClass();
        $updateHelper = new UpdateHelper();
        $mailerHelper = new MailerHelper();

        $testingList = empty($walkthroughParams['list_id']) ? $listClass->getOneByName(acym_translation('ACYM_TESTING_LIST')) : $listClass->getOneById($walkthroughParams['list_id']);
        $firstMail = empty($walkthroughParams['mail_id']) ? $mailClass->getOneByName(acym_translation($updateHelper::FIRST_EMAIL_NAME_KEY)) : $mailClass->getOneById($walkthroughParams['mail_id']);

        if (empty($testingList)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_RETRIEVE_TESTING_LIST'), 'error');

            return false;
        }

        if (empty($firstMail)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_RETRIEVE_TEST_EMAIL'), 'error');
        }

        $subscribersTestingListIds = $listClass->getSubscribersIdsById($testingList->id);

        $nbSent = 0;
        foreach ($subscribersTestingListIds as $subscriberId) {
            if ($mailerHelper->sendOne($firstMail->id, $subscriberId, true)) $nbSent++;
        }

        return $nbSent !== 0;
    }

    private function _saveWalkthrough($params)
    {
        $newParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
        foreach ($params as $key => $value) {
            $newParams[$key] = $value;
        }
        $this->config->save(['walkthrough_params' => json_encode($newParams)]);
    }

    public function features()
    {
        if (!file_exists(ACYM_NEW_FEATURES_SPLASHSCREEN)) {
            $this->listing();

            return;
        }

        $data = [
            'content' => acym_fileGetContent(ACYM_NEW_FEATURES_SPLASHSCREEN),
        ];

        if (!@unlink(ACYM_NEW_FEATURES_SPLASHSCREEN)) {
            $this->listing();

            return;
        }

        acym_setVar('layout', 'features');

        parent::display($data);
    }
}
