<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Helpers\WorkflowHelper;
use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Stats\GlobalStats;
use AcyMailing\Controllers\Stats\Detailed;
use AcyMailing\Controllers\Stats\ClickMap;
use AcyMailing\Controllers\Stats\LinksDetails;
use AcyMailing\Controllers\Stats\UserLinksDetails;
use AcyMailing\Controllers\Stats\Lists;

class StatsController extends acymController
{
    use GlobalStats;
    use Detailed;
    use ClickMap;
    use LinksDetails;
    use UserLinksDetails;
    use Lists;

    var $selectedMailIds = [];
    var $multiLanguageMailAdded = [];
    var $generatedMailAdded = [];

    public function __construct()
    {
        parent::__construct();

        $this->defaulttask = 'globalStats';
        $this->breadcrumb[acym_translation('ACYM_STATISTICS')] = acym_completeLink('stats');
        $this->loadScripts = [
            'all' => ['datepicker', 'thumbnail'],
        ];
    }

    public function call($task)
    {
        $task = $this->storeAndGetTask($task);
        parent::call($task);
    }

    private function storeAndGetTask($task)
    {
        acym_session();

        $tasksToStore = [
            'globalStats',
            'detailedStats',
            'clickMap',
            'linksDetails',
            'userClickDetails',
            'statsByList',
        ];

        if ($this->taskCalled == 'listing' && empty($_SESSION['stats_task'])) {
            return 'globalStats';
        }

        if ((empty($this->taskCalled) || $this->taskCalled == 'listing') && !empty($_SESSION['stats_task']) && in_array($_SESSION['stats_task'], $tasksToStore)) {
            return $_SESSION['stats_task'];
        }

        if (!empty($this->taskCalled) && !in_array($this->taskCalled, $tasksToStore) && method_exists($this, $this->taskCalled)) {
            return $this->taskCalled;
        } elseif (!empty($this->taskCalled) && $this->taskCalled != 'listing' && in_array($this->taskCalled, $tasksToStore)) {
            $_SESSION['stats_task'] = $this->taskCalled;

            return $task;
        } elseif (!empty($_SESSION['stats_task']) && method_exists($this, $_SESSION['stats_task'])) {
            return $_SESSION['stats_task'];
        } else {
            return $this->defaulttask;
        }
    }

    public function searchSentMail()
    {
        $idsSelected = acym_getVar('string', 'id', '');
        if (!empty($idsSelected)) {
            $idsSelected = explode(',', $idsSelected);
            $mailClass = new MailClass();
            $mails = $mailClass->getByIds($idsSelected);
            $data = [];
            if (!empty($mails)) {
                $mails = $mailClass->decode($mails);
                foreach ($mails as $mail) {
                    $data[] = [
                        'value' => $mail->id,
                        'text' => $mail->name,
                    ];
                }
            }

            echo json_encode($data);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');

        $mailStatClass = new MailStatClass();
        $mails = $mailStatClass->getAllMailsForStats($search);

        foreach ($mails as $oneMail) {
            $return[] = [$oneMail->id, $oneMail->name];
        }

        echo json_encode($return);
        exit;
    }

    public function prepareDefaultPageInfo(&$data, $needMailId = false)
    {
        $data['workflowHelper'] = new WorkflowHelper();

        $overrideFilterMailIds = false;

        //If we unselect all email in the dropdown the getVarFiltersListing is not resetting correctly the selected mails ids
        if (acym_getVar('string', 'task', '') == 'listing' && empty(acym_getVar('array', 'mail_ids', []))) $overrideFilterMailIds = true;

        $data['selectedMailid'] = $this->getVarFiltersListing('array', 'mail_ids', [], $overrideFilterMailIds);

        if ($needMailId && empty($data['selectedMailid'])) {
            $this->globalStats();

            return false;
        }

        $mailStatClass = new MailStatClass();
        $data['sentMails'] = $mailStatClass->getAllMailsForStats();
        $data['show_date_filters'] = true;
        $data['page_title'] = false;

        if (count($data['selectedMailid']) == 1) {
            $overrideFilterMailIdVersion = !empty(acym_getVar('array', 'mail_ids', []));

            $versionMailSelected = $this->getVarFiltersListing('int', 'mail_id_version', 0, $overrideFilterMailIdVersion);
            if (!empty($versionMailSelected)) $data['selectedMailid'] = [$versionMailSelected];
        }

        $mailClass = new MailClass();
        if (count($data['selectedMailid']) == 1) {
            $data['mailInformation'] = $mailClass->getOneById($data['selectedMailid'][0]);
            $campaignClass = new CampaignClass();
            $data['isAbTest'] = $campaignClass->isAbTestMail($data['selectedMailid'][0]);
        }

        if (!empty($data['selectedMailid'])) {
            $this->generatedMailAdded = $mailClass->getAutomaticMailIds($data['selectedMailid']);
            $data['selectedMailid'] = array_merge($data['selectedMailid'], $this->generatedMailAdded);
        }
        if (count($data['selectedMailid']) > 1) {
            $this->multiLanguageMailAdded = $mailClass->getMultilingualMailIds($data['selectedMailid']);
            $data['selectedMailid'] = array_merge($data['selectedMailid'], $this->multiLanguageMailAdded);
            $data['no_click_map'] = true;
        }

        if (empty($data['selectedMailid'])) {
            $this->selectedMailIds = [];
        } else {
            $this->selectedMailIds = $data['selectedMailid'];
        }

        acym_arrayToInteger($this->selectedMailIds);

        return true;
    }
}
