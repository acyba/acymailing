<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Campaigns\Actions;
use AcyMailing\Controllers\Campaigns\AutoCampaigns;
use AcyMailing\Controllers\Campaigns\Edition;
use AcyMailing\Controllers\Campaigns\Followup;
use AcyMailing\Controllers\Campaigns\ListEmails;
use AcyMailing\Controllers\Campaigns\Listing;
use AcyMailing\Controllers\Campaigns\Tests;

class CampaignsController extends acymController
{
    use Listing;
    use Followup;
    use ListEmails;
    use AutoCampaigns;
    use Edition;
    use Actions;
    use Tests;

    private $stepContainerClass = '';

    public function __construct()
    {
        $this->defaulttask = 'campaigns';
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_EMAILS')] = acym_completeLink('campaigns');
        $this->loadScripts = [
            'recipients' => ['vue-applications' => ['entity_select']],
            'send_settings' => ['datepicker'],
            'summary' => ['vue-applications' => ['modal_users_summary']],
            'segment' => ['datepicker', 'vue-applications' => ['modal_users_summary']],
        ];

        if (acym_isAdmin()) {
            $this->stepContainerClass = 'xxlarge-9';
        }

        acym_setVar('edition', '1');
        acym_header('X-XSS-Protection:0');
        $this->storeRedirectListing();
    }

    public function cancelDashboardAndGetCampaignsAjax()
    {
        $campaignId = acym_getVar('int', 'id');
        $campaignClass = new CampaignClass();

        if (empty($campaignId)) {
            acym_sendAjaxResponse(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), [], false);
        }

        $campaign = new \stdClass();
        $campaign->id = $campaignId;
        $campaign->active = 0;
        $campaign->draft = 1;

        $campaignId = $campaignClass->save($campaign);
        if (empty($campaignId)) {
            acym_sendAjaxResponse(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED'), [], false);
        }

        $campaigns = $campaignClass->getCampaignForDashboard();

        if (empty($campaigns)) {
            $result = '<h1 class="acym__dashboard__active-campaigns__none">'.acym_translation('ACYM_NONE_OF_YOUR_CAMPAIGN_SCHEDULED_GO_SCHEDULE_ONE').'</h1>';
            acym_sendAjaxResponse('', ['content' => $result]);
        }

        $result = '';

        foreach ($campaigns as $campaign) {
            $result .= '<div class="cell grid-x acym__dashboard__active-campaigns__one-campaign">
                        <a class="acym__dashboard__active-campaigns__one-campaign__title medium-4 small-12" href="'.acym_completeLink(
                    'campaigns&task=edit&step=editEmail&id='
                ).$campaign->id.'">'.$campaign->name.'</a>
                        <div class="acym__dashboard__active-campaigns__one-campaign__state medium-2 small-12 acym__background-color__blue text-center"><span>'.acym_translation(
                    'ACYM_SCHEDULED'
                ).' : '.acym_getDate($campaign->sending_date, 'ACYM_DATE_FORMAT_LC3').'</span></div>
                        <div class="medium-6 small-12"><p id="'.$campaign->id.'" class="acym__dashboard__active-campaigns__one-campaign__action acym__color__dark-gray">'.acym_translation(
                    'ACYM_CANCEL_SCHEDULING'
                ).'</p></div>
                    </div>
                    <hr class="cell small-12">';
        }

        acym_sendAjaxResponse('', ['content' => $result]);
    }

    public function countNumberOfRecipients()
    {
        $listsSelected = acym_getVar('array', 'listsSelected', []);
        if (empty($listsSelected)) {
            echo 0;
            exit;
        }

        $listClass = new ListClass();
        echo $listClass->getTotalSubCount($listsSelected);
        exit;
    }

    public function deleteAttachmentAjax()
    {
        $mailId = acym_getVar('int', 'mail', 0);
        $attachmentId = acym_getVar('int', 'id', 0);

        if (!empty($mailId) && $attachmentId >= 0) {
            $mailClass = new MailClass();

            if ($mailClass->deleteOneAttachment($mailId, $attachmentId)) {
                acym_sendAjaxResponse(acym_translation('ACYM_ATTACHMENT_WELL_DELETED'));
            }
        }

        acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_DELETE_ATTACHMENT'), [], false);
    }

    public function test()
    {
        $campaignId = acym_getVar('int', 'id', 0);

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);

        if (empty($campaign)) {
            acym_sendAjaxResponse(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), [], false);
        }

        $mailerHelper = new MailerHelper();
        $mailerHelper->autoAddUser = true;
        $mailerHelper->report = false;

        $report = [];
        $success = true;
        $testNote = acym_getVar('string', 'test_note', '');

        $testEmails = explode(',', acym_getVar('string', 'test_emails'));
        foreach ($testEmails as $oneAddress) {
            if (!$mailerHelper->sendOne($campaign->mail_id, $oneAddress, true, $testNote)) {
                $success = false;
            }

            if (!empty($mailerHelper->reportMessage)) {
                $report[] = $mailerHelper->reportMessage;
            }
        }

        acym_sendAjaxResponse(implode('<br/>', $report), [], $success);
    }

    public function saveAjax()
    {
        $result = $this->saveEditEmail(true);
        if ($result) {
            acym_sendAjaxResponse('', ['result' => $result]);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_SAVING'), [], false);
        }
    }

    public function saveAsTmplAjax()
    {
        $mailController = new MailsController();
        $isWellSaved = $mailController->store(true);
        acym_sendAjaxResponse($isWellSaved ? '' : acym_translation('ACYM_ERROR_SAVING'), ['result' => $isWellSaved], $isWellSaved);
    }

    /**
     * Search user emails to suggest (autocomplete on send a test)
     */
    public function searchTestReceiversAjax()
    {
        $search = acym_getVar('string', 'search', '');
        $userClass = new UserClass();
        $users = $userClass->getUsersLikeEmail($search);

        $return = [];
        foreach ($users as $oneUser) {
            $return[] = [$oneUser->id, $oneUser->email];
        }
        echo json_encode($return);
        exit;
    }
}
