<?php

namespace AcyMailing\Controllers\Campaigns;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\QueueClass;
use AcyMailing\Classes\SegmentClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Controllers\AutomationController;
use AcyMailing\Controllers\MailsController;
use AcyMailing\Controllers\SegmentsController;
use AcyMailing\Helpers\EditorHelper;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\PluginHelper;
use AcyMailing\Helpers\WorkflowHelper;
use AcyMailing\Types\UploadfileType;
use stdClass;


trait Edition
{
    public function newEmail()
    {
        acym_setVar('layout', 'new_email');

        $listClass = new ListClass();
        $mailClass = new MailClass();
        if (acym_isAdmin()) {
            $returnUrl = urlencode(base64_encode(acym_completeLink('campaigns')));
            $data = [
                'lists' => $listClass->getAllForSelect(),
                'campaign_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=now'),
                'campaign_auto_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=auto'),
                'followup_link' => acym_completeLink('campaigns&task=edit&step=followupTrigger'),
                'campaign_scheduled_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=scheduled'),
                'welcome_email_link' => acym_completeLink('mails&task=edit&type='.$mailClass::TYPE_WELCOME.'&list_id={dataid}&type_editor=acyEditor&return='.$returnUrl),
                'unsubscribe_email_link' => acym_completeLink('mails&task=edit&type='.$mailClass::TYPE_UNSUBSCRIBE.'&list_id={dataid}&type_editor=acyEditor&return='.$returnUrl),
            ];
        } else {
            global $Itemid;
            $itemId = empty($Itemid) ? '' : '&Itemid='.$Itemid;
            $returnUrl = urlencode(base64_encode(acym_frontendLink('frontcampaigns'.$itemId)));

            $welcomeUnsub = '&list_id={dataid}&type_editor=acyEditor'.$itemId.'&return='.$returnUrl;
            $data = [
                'lists' => $listClass->getAllForSelect(true, acym_currentUserId()),
                'campaign_link' => acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type=now'.$itemId),
                'campaign_scheduled_link' => acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type=scheduled'.$itemId),
                'welcome_email_link' => acym_frontendLink('frontmails&task=edit&type='.$mailClass::TYPE_WELCOME.$welcomeUnsub),
                'unsubscribe_email_link' => acym_frontendLink('frontmails&task=edit&type='.$mailClass::TYPE_UNSUBSCRIBE.$welcomeUnsub),
            ];
        }
        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    private function prepareSegmentDisplay(&$data, $sendingParams)
    {
        $data['menuClass'] = $this->menuClass;
        $data['displaySegmentTab'] = !empty($sendingParams) && array_key_exists('segment', $sendingParams);
    }

    public function chooseTemplate()
    {
        acym_setVar('layout', 'choose_email');
        acym_setVar('step', 'chooseTemplate');
        $pagination = new PaginationHelper();

        // Get filters data
        $campaignId = acym_getVar('int', 'id', 0);
        $campaignClass = new CampaignClass();
        $searchFilter = $this->getVarFiltersListing('string', 'mailchoose_search', '');
        $tagFilter = $this->getVarFiltersListing('string', 'mailchoose_tag', '');
        $ordering = $this->getVarFiltersListing('string', 'mailchoose_ordering', 'creation_date');
        $orderingSortOrder = $this->getVarFiltersListing('string', 'mailchoose_ordering_sort_order', 'DESC');
        $campaign = $campaignClass->getOneByIdWithMail($campaignId);
        $campaignType = $this->getVarFiltersListing('string', 'campaign_type', 'now');

        $this->setTaskListing($campaignType == 'auto' ? 'campaigns_auto' : 'campaigns');

        if (!empty($campaign)) {
            $this->breadcrumb[acym_escape($campaign->name)] = '';
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW_CAMPAIGN')] = '';
        }

        // Get pagination data
        $mailsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'mailchoose_pagination_page', 1);

        $mailClass = new MailClass();
        $matchingMails = $mailClass->getMatchingElements([
            'ordering' => $ordering,
            'ordering_sort_order' => $orderingSortOrder,
            'search' => $searchFilter,
            'elementsPerPage' => $mailsPerPage,
            'offset' => ($page - 1) * $mailsPerPage,
            'tag' => $tagFilter,
            'onlyStandard' => true,
            'creator_id' => $this->setFrontEndParamsForTemplateChoose(),
        ]);

        // Prepare the pagination
        $pagination->setStatus($matchingMails['total'], $page, $mailsPerPage);

        $tagClass = new TagClass();

        $data = [
            'allMails' => $matchingMails['elements'],
            'allTags' => $tagClass->getAllTagsByType('mail'),
            'pagination' => $pagination,
            'search' => $searchFilter,
            'tag' => $tagFilter,
            'ordering' => $ordering,
            'campaignID' => $campaignId,
            'campaign_type' => $campaignType,
        ];
        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, empty($campaign->sending_params) ? false : $campaign->sending_params);

        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    protected function setFrontEndParamsForTemplateChoose()
    {
        return '';
    }

    private function prepareEditCampaign(&$data)
    {
        $campaignId = acym_getVar('int', 'id', 0);
        $mailId = acym_getVar('int', 'from', 0);
        $mailClass = new MailClass();
        $data['mailClass'] = $mailClass;
        // Check autosave only if mail already saved in campaign and not just selected template (from parameter)
        $checkAutosave = empty($mailId);
        $editLink = 'campaigns&task=edit&step=editEmail';

        if (empty($campaignId)) {
            $data['mailInformation'] = new stdClass();
            $data['mailInformation']->id = 0;
            $data['mailInformation']->name = '';
            $data['mailInformation']->tags = [];
            $data['mailInformation']->subject = '';
            $data['mailInformation']->preheader = '';
            $data['mailInformation']->body = '';
            $data['mailInformation']->settings = null;
            $data['mailInformation']->links_language = '';
            $data['mailInformation']->visible = 1;
            $data['mailInformation']->mail_settings = null;

            $editLink .= '&from='.$mailId;
        } else {
            $campaignClass = new CampaignClass();
            $data['mailInformation'] = $campaignClass->getOneByIdWithMail($campaignId);
            if (empty($mailId)) {
                $mailId = $data['mailInformation']->mail_id;
            }
            $editLink .= '&id='.$campaignId;
        }

        if ($mailId == -1 || (empty($campaignId) && empty($mailId))) {
            $data['mailInformation']->name = '';
            $data['mailInformation']->tags = [];
            $data['mailInformation']->subject = '';
            $data['mailInformation']->preheader = '';
            $data['mailInformation']->body = '';
            $data['mailInformation']->settings = null;
            $data['mailInformation']->attachments = [];
            $data['mailInformation']->stylesheet = '';
            $data['mailInformation']->headers = '';
            $data['mailInformation']->from_email = '';
            $data['mailInformation']->from_name = '';
            $data['mailInformation']->reply_to_email = '';
            $data['mailInformation']->reply_to_name = '';
            $data['mailInformation']->mail_settings = null;
            $data['typeEditor'] = 'acyEditor';
        } elseif (!empty($mailId)) {
            $mail = $mailClass->getOneById($mailId);
            if (!acym_isAdmin() && ACYM_CMS == 'joomla' && acym_isPluginActive('sef', 'system')) {
                $mail->body = str_replace(['url(&quot;', '&quot;)'], ["url('", "')"], $mail->body);
            }
            $data['mailInformation']->tags = $mail->tags;
            $data['mailInformation']->subject = $mail->subject;
            $data['mailInformation']->preheader = $mail->preheader;
            $data['mailInformation']->body = $mail->body;
            $data['mailInformation']->settings = $mail->settings;
            $data['mailInformation']->stylesheet = $mail->stylesheet;
            $data['mailInformation']->headers = $mail->headers;
            $data['mailInformation']->attachments = empty($mail->attachments) ? [] : json_decode($mail->attachments);
            $data['mailInformation']->links_language = $mail->links_language;

            $data['mailInformation']->from_email = $mail->from_email;
            $data['mailInformation']->from_name = $mail->from_name;
            $data['mailInformation']->reply_to_email = $mail->reply_to_email;
            $data['mailInformation']->reply_to_name = $mail->reply_to_name;
            $data['mailInformation']->mail_settings = $mail->mail_settings;

            if ($checkAutosave) {
                $data['mailInformation']->autosave = $mail->autosave;
            }
        }
        $data['mailId'] = $mailId;
        $data['campaignID'] = $data['mailInformation']->id;

        $pluginHelper = new PluginHelper();
        $pluginHelper->cleanHtml($data['mailInformation']->body);

        $editLink .= '&type_editor='.$data['typeEditor'];
        $this->breadcrumb[acym_escape(empty($data['mailInformation']->name) ? acym_translation('ACYM_NEW_CAMPAIGN') : $data['mailInformation']->name)] = acym_completeLink(
            $editLink
        );
    }

    private function prepareEditor(&$data)
    {
        $data['editor'] = new EditorHelper();
        $data['editor']->content = $data['mailInformation']->body;
        $data['editor']->autoSave = !empty($data['mailInformation']->autosave) ? $data['mailInformation']->autosave : '';
        if (!empty($data['mailInformation']->settings)) {
            $data['editor']->settings = $data['mailInformation']->settings;
        }

        if (!empty($data['mailInformation']->stylesheet)) {
            $data['editor']->stylesheet = $data['mailInformation']->stylesheet;
        }

        if (empty($data['typeEditor']) && strpos($data['editor']->content, 'acym__wysid__template') !== false) {
            $data['typeEditor'] = 'acyEditor';
        }

        $data['editor']->editor = $data['typeEditor'];
        if ($data['editor']->editor != 'acyEditor' || empty($data['editor']->editor)) {
            if (!isset($data['mailInformation']->stylesheet)) $data['mailInformation']->stylesheet = '';
            $data['needDisplayStylesheet'] = '<input type="hidden" name="editor_stylesheet" value="'.acym_escape($data['mailInformation']->stylesheet).'">';
        } else {
            $data['needDisplayStylesheet'] = '';
        }

        $data['editor']->mailId = empty($data['mailId']) ? 0 : $data['mailId'];

        if ($data['editor']->isDragAndDrop()) {
            $this->loadScripts['edit_email'][] = 'editor-wysid';
            $this->loadScripts['edit_email']['vue-applications'] = ['custom_view'];
        }
    }

    public function prepareMaxUpload(&$data)
    {
        $maxupload = ini_get('upload_max_filesize');
        $maxpost = ini_get('post_max_size');
        $data['maxupload'] = acym_bytes($maxupload) > acym_bytes($maxpost) ? $maxpost : $maxupload;
    }

    private function prepareMultilingual(&$data, $editor = true)
    {
        $data['multilingual'] = 0;

    }

    private function prepareAllMailsForMultilingual(&$data)
    {
        $mailClass = new MailClass();

        $mails = $mailClass->getMultilingualMails($data['mailId']);

        if (empty($mails)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_MULTILINGUAL_MAILS'), 'error');

            return;
        }

        foreach ($mails as $key => $oneMail) {
            $mails[$key] = $this->prepareMailDataSummary($data, $oneMail->id);
        }

        $data['multilingual_mails'] = $mails;
    }

    public function editEmail()
    {
        acym_setVar('layout', 'edit_email');
        acym_setVar('numberattachment', '0');
        acym_setVar('step', 'editEmail');

        $tagClass = new TagClass();

        $data = [
            'containerClass' => $this->stepContainerClass,
            'social_icons' => $this->config->get('social_icons', '{}'),
            'allTags' => $tagClass->getAllTagsByType('mail'),
            'campaign_type' => acym_getVar('string', 'campaign_type', 'now'),
            'typeEditor' => acym_getVar('string', 'type_editor', ''),
            'uploadFileType' => new UploadfileType(),
        ];

        $this->prepareEditCampaign($data);
        $this->prepareEditor($data);
        $this->prepareMaxUpload($data);
        $this->prepareMultilingual($data);
        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, empty($data['mailInformation']->sending_params) ? false : $data['mailInformation']->sending_params);

        $data['before-save'] = $data['editor']->editor != 'acyEditor' ? '' : 'acym-data-before="acym_editorWysidMultilingual.storeCurrentValues(true);"';

        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    public function recipients()
    {
        acym_setVar('layout', 'recipients');
        $campaignId = acym_getVar('int', 'id');
        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        acym_setVar('step', 'recipients');

        if (!empty($campaignId)) {
            $currentCampaign = $campaignClass->getOneByIdWithMail($campaignId);
            $this->breadcrumb[acym_escape($currentCampaign->name)] = acym_completeLink('campaigns&task=edit&step=recipients&id='.$campaignId);
        } else {
            $currentCampaign = new stdClass();
            $this->breadcrumb[acym_translation('ACYM_NEW_CAMPAIGN')] = acym_completeLink('campaigns&task=edit&step=recipients');
        }

        $campaign = [
            'campaignInformation' => $campaignId,
            'currentCampaign' => $currentCampaign,
            'containerClass' => $this->stepContainerClass,
            'entitySelectHelper' => new EntitySelectHelper(),
        ];

        // Get saved data if edition of a campaign
        if (!empty($currentCampaign->mail_id)) {
            $campaignLists = $mailClass->getAllListsByMailId($currentCampaign->mail_id);
            $campaign['campaignListsId'] = array_keys($campaignLists);
            acym_arrayToInteger($campaign['campaignListsId']);
            $campaign['campaignListsSelected'] = json_encode($campaign['campaignListsId']);
        }
        $this->prepareListingClasses($campaign);
        $this->prepareSegmentDisplay($campaign, $campaign['currentCampaign']->sending_params);

        parent::display($campaign);
    }

    public function segment()
    {
        acym_setVar('layout', 'segment');
        acym_setVar('step', 'segment');

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $campaignId = acym_getVar('int', 'id');

        if (empty($campaignId)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), 'error');
            $this->listing();

            return;
        }

        $campaign = $campaignClass->getOneById($campaignId);

        $mail = $mailClass->getOneById($campaign->mail_id);
        $data = [
            'campaign' => $campaign,
            'containerClass' => $this->stepContainerClass,
            'displaySegmentTab' => true,
            'workflowHelper' => new WorkflowHelper(),
        ];


        $this->breadcrumb[acym_escape($mail->name)] = acym_completeLink(acym_completeLink('campaigns&task=edit&step=recipients&id='.$campaign->id));
        parent::display($data);
    }

    public function sendSettings()
    {
        acym_setVar('layout', 'send_settings');
        acym_setVar('step', 'sendSettings');
        $campaignId = acym_getVar('int', 'id');
        $campaignClass = new CampaignClass();
        $campaignInformation = empty($campaignId) ? null : $campaignClass->getOneById($campaignId);

        if (is_null($campaignInformation)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_GET_CAMPAIGN_INFORMATION'), 'error');
            $this->listing();

            return;
        }

        //To know if we create or modify the campaign
        $from = acym_getVar('string', 'from');

        $campaignClass = new CampaignClass();
        $currentCampaign = $campaignClass->getOneByIdWithMail($campaignId);
        $this->breadcrumb[acym_escape($currentCampaign->name)] = acym_completeLink('campaigns&task=edit&step=sendSettings&id='.$campaignId);

        if (!empty($currentCampaign->sent) && empty($currentCampaign->active)) {
            $currentCampaign->sending_date = '';
        }

        $campaign = [];

        $campaign['currentCampaign'] = $currentCampaign;
        $campaign['from'] = $from;
        $campaign['suggestedDate'] = acym_date('1534771620', 'j M Y H:i');
        $campaign['senderInformations'] = new stdClass();
        $campaign['config_values'] = new stdClass();
        $campaign['currentCampaign']->send_now = $currentCampaign->sending_type == $campaignClass::SENDING_TYPE_NOW;
        $campaign['currentCampaign']->send_scheduled = $currentCampaign->sending_type == $campaignClass::SENDING_TYPE_SCHEDULED;
        $campaign['currentCampaign']->send_auto = $currentCampaign->sending_type == $campaignClass::SENDING_TYPE_AUTO;
        $campaign['campaignClass'] = $campaignClass;

        // Handle special emails
        $campaign['currentCampaign']->send_specific = [];
        if (!in_array($currentCampaign->sending_type, $campaignClass::SENDING_TYPES)) {
            acym_trigger('getCampaignSpecificSendSettings', [$currentCampaign->sending_type, $currentCampaign->sending_params, &$campaign['currentCampaign']->send_specific]);
        }

        $campaign['senderInformations']->from_name = empty($currentCampaign->from_name) ? '' : $currentCampaign->from_name;
        $campaign['senderInformations']->from_email = empty($currentCampaign->from_email) ? '' : $currentCampaign->from_email;
        $campaign['senderInformations']->reply_to_name = empty($currentCampaign->reply_to_name) ? '' : $currentCampaign->reply_to_name;
        $campaign['senderInformations']->reply_to_email = empty($currentCampaign->reply_to_email) ? '' : $currentCampaign->reply_to_email;

        $campaign['config_values']->from_name = $this->config->get('from_name', '');
        $campaign['config_values']->from_email = $this->config->get('from_email', '');
        $campaign['config_values']->reply_to_name = $this->config->get('replyto_name', '');
        $campaign['config_values']->reply_to_email = $this->config->get('replyto_email', '');

        $triggers = [];

        acym_trigger('onAcymDeclareTriggers', [&$triggers, &$currentCampaign->sending_params], 'plgAcymTime');
        $triggers = $triggers['classic'];

        $campaign['triggers_select'] = [];
        $campaign['triggers_display'] = [];

        foreach ($triggers as $key => $trigger) {
            $campaign['triggers_select'][$key] = $trigger->name;
            $campaign['triggers_display'][$key] = $trigger->option;
        }

        if (!empty($campaign['currentCampaign']->sending_params) && empty($campaign['currentCampaign']->sending_params['trigger_type'])) {
            foreach (array_keys($triggers) as $oneTrigger) {
                if (!empty($campaign['currentCampaign']->sending_params[$oneTrigger])) $campaign['currentCampaign']->sending_params['trigger_type'] = $oneTrigger;
            }
        }

        $campaign['containerClass'] = $this->stepContainerClass;
        $campaign['langChoice'] = acym_isMultilingual() ? '' : acym_languageOption($campaign['currentCampaign']->links_language, 'senderInformation[links_language]');
        $this->prepareListingClasses($campaign);
        $this->prepareSegmentDisplay($campaign, $campaign['currentCampaign']->sending_params);
        $this->prepareMultilingualOption($campaign);

        parent::display($campaign);
    }

    public function saveEditEmail($ajax = false)
    {
        acym_checkToken();

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $formData = acym_getVar('array', 'mail', []);
        $multilingual = acym_getVar('array', 'multilingual', [], 'REQUEST', ACYM_ALLOWRAW);
        $allowedFields = acym_getColumns('mail');
        $campaignId = acym_getVar('int', 'id', 0);
        $campaignType = acym_getVar('string', 'campaign_type', 'now');

        $types = [
            'now' => $campaignClass::SENDING_TYPE_NOW,
            'auto' => $campaignClass::SENDING_TYPE_AUTO,
            'scheduled' => $campaignClass::SENDING_TYPE_SCHEDULED,
        ];

        acym_trigger('getCampaignTypes', [&$types]);

        if (empty($campaignId)) {
            $mail = new stdClass();
            $mail->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
            $mail->type = $mailClass::TYPE_STANDARD;

            $campaign = new stdClass();
            $campaign->draft = 1;
            $campaign->active = 0;
            $campaign->sending_type = $types[$campaignType];
            $campaign->sent = 0;
            $campaign->sending_params = [];
        } else {
            $campaign = $campaignClass->getOneById($campaignId);
            $mail = $mailClass->getOneById($campaign->mail_id);
        }
        $campaign->visible = acym_getVar('int', 'visible', 1);

        // Get the name and subject
        foreach ($formData as $name => $data) {
            if (!in_array($name, $allowedFields)) {
                continue;
            }
            $mail->{acym_secureDBColumn($name)} = $data;
        }

        // Name is mandatory. If empty copy subject (can't be an empty field)
        if (empty($mail->name)) $mail->name = empty($mail->subject) ? acym_translation('ACYM_CAMPAIGN_NAME') : $mail->subject;

        if (empty($mail->subject)) $mail->subject = acym_translation('ACYM_EMAIL_SUBJECT');

        $mail->body = acym_getVar('string', 'editor_content', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->settings = acym_getVar('string', 'editor_settings', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->stylesheet = acym_getVar('string', 'editor_stylesheet', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->headers = acym_getVar('string', 'editor_headers', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->from_email = acym_getVar('string', 'from_email', '', 'REQUEST');
        $mail->from_name = acym_getVar('string', 'from_name', '', 'REQUEST');
        $mail->reply_to_email = acym_getVar('string', 'reply_to_email', '', 'REQUEST');
        $mail->reply_to_name = acym_getVar('string', 'reply_to_name', '', 'REQUEST');
        $mail->drag_editor = strpos($mail->body, 'acym__wysid__template') === false ? 0 : 1;
        $mail->attachments = empty($mail->attachments) ? [] : json_decode($mail->attachments, true);

        $mainColors = acym_getVar('string', 'main_colors', '', 'REQUEST', ACYM_ALLOWRAW);;
        if (!empty($mail->mail_settings)) {
            $mailSettings = json_decode($mail->mail_settings, false);
        } else {
            $mailSettings = new stdClass();
        }
        $mailSettings->mainColors = $mainColors;
        $mail->mail_settings = json_encode($mailSettings);

        $mail->tags = acym_getVar('array', 'template_tags', []);

        $mailController = new MailsController();
        $mailController->setAttachmentToMail($mail);

        if (!empty($multilingual)) {
            if (!empty($multilingual['main']['subject'])) $mail->subject = $multilingual['main']['subject'];
            if (!empty($multilingual['main']['preview'])) $mail->preheader = $multilingual['main']['preview'];
            if (!empty($multilingual['main']['content'])) $mail->body = $multilingual['main']['content'];
            if (!empty($multilingual['main']['content'])) $mail->settings = $multilingual['main']['settings'];
            if (!empty($multilingual['main']['content'])) $mail->stylesheet = $multilingual['main']['stylesheet'];
            $mail->links_language = $this->config->get('multilingual_default');
            unset($multilingual['main']);
        }

        if ($mailID = $mailClass->save($mail)) {
            if (acym_getVar('string', 'nextstep', '') == 'listing') {
                acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            if (!empty($mailClass->errors)) {
                acym_enqueueMessage($mailClass->errors, 'error');
            }

            if (!$ajax) {
                $this->listing();
            }

            return false;
        }

        if (!empty($multilingual)) {
            foreach ($multilingual as $langCode => $translation) {
                if (empty($translation['subject'])) {
                    $mailClass->delete($mailClass->getTranslationId($mailID, $langCode));
                    continue;
                }

                unset($mail->id);
                $translationId = $mailClass->getTranslationId($mailID, $langCode);
                if (!empty($translationId)) {
                    $mail->id = $translationId;
                }

                $mail->subject = $translation['subject'];
                $mail->preheader = $translation['preview'];
                $mail->body = $translation['content'];
                $mail->links_language = $langCode;
                $mail->language = $langCode;
                $mail->parent_id = $mailID;
                $mail->settings = $translation['settings'];
                $mail->stylesheet = $translation['stylesheet'];

                $mailClass->save($mail);
            }
        }

        $campaign->mail_id = $mailID;
        $campaign->id = $campaignClass->save($campaign);

        if ($ajax) {
            return $campaign->id;
        }

        acym_setVar('id', $campaign->id);

        return $this->edit();
    }

    public function saveRecipients()
    {
        $allLists = json_decode(acym_getVar('string', 'acym__entity_select__selected'));
        $allListsUnselected = json_decode(acym_getVar('string', 'acym__entity_select__unselected'));
        $campaignId = acym_getVar('int', 'id');
        $addSegmentStep = acym_getVar('int', 'add_segment_step');

        $campaignClass = new CampaignClass();
        $currentCampaign = $campaignClass->getOneByIdWithMail($campaignId);

        if ($currentCampaign->sent && !$currentCampaign->active) {
            $mailStatClass = new MailStatClass();
            $listClass = new ListClass();
            $mailStat = $mailStatClass->getOneRowByMailId($currentCampaign->mail_id);
            $mailStat->total_subscribers = $listClass->getTotalSubCount($allLists);
            $mailStatClass->save($mailStat);
        } elseif (!empty($currentCampaign->mail_id)) {
            $campaignClass->manageListsToCampaign($allLists, $currentCampaign->mail_id, $allListsUnselected);
            if (acym_getVar('string', 'nextstep', '') == 'listing') {
                acym_enqueueMessage(acym_translationSprintf('ACYM_LIST_IS_SAVED', $currentCampaign->name));
            }
        }

        if (!empty($addSegmentStep)) {
            if (!isset($currentCampaign->sending_params['segment'])) {
                $currentCampaign->sending_params['segment'] = [];
            }
            $campaignClass->save($currentCampaign);
            acym_setVar('nextstep', 'segment');
            $this->segment();

            return;
        }

        if (isset($currentCampaign->sending_params['segment'])) unset($currentCampaign->sending_params['segment']);
        $campaignClass->save($currentCampaign);

        $this->edit();
    }

    public function saveSegment()
    {
        $segmentSelected = acym_getVar('int', 'segment_selected', 0);
        $filters = acym_getVar('array', 'acym_action', []);
        $campaignId = acym_getVar('int', 'id', 0);

        if (empty($campaignId)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            $this->listing();

            return;
        }

        $segmentSavedId = acym_getVar('int', 'saved_segment_id', 0);

        if (!empty($segmentSavedId)) {
            $segmentSelected = $segmentSavedId;
        }

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);

        if (empty($campaign)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            $this->listing();

            return;
        }

        if (empty($segmentSelected) && empty($filters)) {
            $campaign->sending_params['segment'] = [];
        } elseif (!empty($segmentSelected)) {
            $campaign->sending_params['segment'] = ['segment_id' => $segmentSelected];
        } else {
            $campaign->sending_params['segment'] = ['filters' => (object)$filters['filters']];
        }

        $campaign->sending_params['segment']['invert'] = acym_getVar('string', 'invert', 0);

        $campaignClass->save($campaign);

        $this->edit();
    }

    public function saveSendSettings()
    {
        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $campaignId = acym_getVar('int', 'id');
        $senderInformation = acym_getVar('', 'senderInformation');
        $sendingDate = acym_getVar('string', 'sendingDate');
        $sendingType = acym_getVar('string', 'sending_type', $campaignClass::SENDING_TYPE_NOW);
        $sendingParams = acym_getVar('array', 'sending_params', []);
        $specificSendingParams = [];
        $isScheduled = $campaignClass::SENDING_TYPE_SCHEDULED == $sendingType;

        $currentCampaign = $campaignClass->getOneById($campaignId);

        if (empty($currentCampaign)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_DOESNT_EXISTS'), 'error');

            $this->listing();

            return;
        }

        if ($campaignClass::SENDING_TYPE_AUTO == $sendingType) {
            $triggerType = acym_getVar('string', 'acym_triggers', '');
            if (empty($triggerType)) {
                acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
                $this->listing();

                return;
            }

            $needConfirmToSend = acym_getVar('int', 'need_confirm', 0);

            $specificSendingParams = [
                $triggerType => acym_getVar('array', $triggerType, ''),
                'need_confirm_to_send' => $needConfirmToSend,
                'trigger_type' => $triggerType,
            ];

            $startDate = acym_getVar('string', 'start_date', 0);
            if (!empty($startDate)) {
                $specificSendingParams['start_date'] = acym_date(acym_getTime($startDate), 'Y-m-d H:i:s', false);
            }

            if (!empty($currentCampaign->sending_params['number_generated'])) {
                $specificSendingParams['number_generated'] = $currentCampaign->sending_params['number_generated'];
            }

            // Clean old settings saved in the campaign
            $triggers = [];
            $fakeSettings = [];
            acym_trigger('onAcymDeclareTriggers', [&$triggers, &$fakeSettings], 'plgAcymTime');
            $settings = array_merge(array_keys($triggers['classic']), ['trigger_type', 'need_confirm_to_send']);
            foreach ($settings as $oneSetting) {
                unset($currentCampaign->sending_params[$oneSetting]);
            }
        }

        // Handle special emails
        if (!in_array($sendingType, $campaignClass::SENDING_TYPES)) {
            $specialSendings = [];
            acym_trigger('saveCampaignSpecificSendSettings', [$currentCampaign->sending_type, &$specialSendings]);
            if (!empty($specialSendings)) {
                $specificSendingParams = $specialSendings[0];
            }
        }

        if (!empty($currentCampaign->mail_id)) {
            $currentMail = $mailClass->getOneById($currentCampaign->mail_id);
        }

        $currentCampaign->sending_type = $sendingType;
        if (empty($currentCampaign->sending_params)) $currentCampaign->sending_params = [];
        $currentCampaign->sending_params = array_merge($currentCampaign->sending_params, $sendingParams, $specificSendingParams);

        if (empty($currentMail) || empty($senderInformation)) {
            $this->listing();

            return;
        }

        $currentMail->from_name = $senderInformation['from_name'];
        $currentMail->from_email = $senderInformation['from_email'];
        $currentMail->reply_to_name = $senderInformation['reply_to_name'];
        $currentMail->reply_to_email = $senderInformation['reply_to_email'];
        $currentMail->bcc = $senderInformation['bcc'];
        $currentMail->tracking = $senderInformation['tracking'];
        $currentMail->translation = empty($senderInformation['translation']) ? '' : $senderInformation['translation'];
        if (isset($senderInformation['links_language'])) $currentMail->links_language = $senderInformation['links_language'];

        $mailClass->save($currentMail);

        if ($isScheduled && !empty($sendingDate)) {
            $currentCampaign->sending_date = acym_date(acym_getTime($sendingDate), 'Y-m-d H:i:s', false);
            if ($currentCampaign->sending_date < acym_date('now', 'Y-m-d H:i:s', false)) acym_enqueueMessage(acym_translation('ACYM_BE_CAREFUL_SENDING_DATE_IN_PAST'), 'warning');
        }

        if ($campaignClass->save($currentCampaign)) {
            if (acym_getVar('string', 'nextstep', '') == 'listing') {
                acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            if (!empty($campaignClass->errors)) {
                acym_enqueueMessage($campaignClass->errors, 'error');
            }

            $this->listing();

            return;
        }

        $this->edit();
    }

    /**
     * Needed for the steps system
     */
    public function saveSummary()
    {
        $this->edit();
    }

    public function summary()
    {
        acym_setVar('step', 'summary');
        acym_setVar('layout', 'summary');

        $data = [
            'mailClass' => new MailClass(),
            'campaignClass' => $this->currentClass,
            'containerClass' => $this->stepContainerClass,
        ];

        $this->prepareCurrentUserSummary($data);
        if (!$this->prepareCampaignSummary($data)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_GET_CAMPAIGN_INFORMATION'), 'error');
            $this->listing();

            return;
        }
        $data['mailInformation'] = $this->prepareMailDataSummary($data, $data['campaignInformation']->mail_id);
        $this->prepareReceiversSummary($data);
        $this->prepareMultilingual($data, false);
        $this->prepareAllMailsForMultilingual($data);
        $this->prepareListingClasses($data);
        $this->prepareSegmentData($data);
        $this->prepareSegmentDisplay($data, $data['campaignInformation']->sending_params);

        $this->breadcrumb[$data['campaignInformation']->name] = acym_completeLink('campaigns&task=edit&step=summary&id='.$data['campaignInformation']->id);
        parent::display($data);
    }

    private function prepareSegmentData(&$data)
    {
        if (empty($data['campaignInformation']->sending_params['segment'])) return;

        $segmentParams = $data['campaignInformation']->sending_params['segment'];

        $segmentController = new SegmentsController();

        if (!empty($segmentParams['segment_id'])) {
            $segmentClass = new SegmentClass();
            $segment = $segmentClass->getOneById($segmentParams['segment_id']);

            $data['segment'] = [
                'name' => $segment->name,
                'count' => $segmentController->countSegmentById($segment->id, $data['listsIds'], false),
            ];
        } else {
            $data['segment'] = [
                'name' => acym_translation('ACYM_YOUR_CUSTOM_SEGMENT'),
                'count' => $segmentController->countSegmentByParams($segmentParams, $data['listsIds']),
            ];
        }
    }

    protected function prepareCurrentUserSummary(&$data)
    {
        $userClass = new UserClass();
        $currentUserEmail = acym_currentUserEmail();
        $data['receiver'] = $userClass->getOneByEmail($currentUserEmail);
        if (empty($data['receiver'])) {
            $receiver = new stdClass();
            $receiver->email = $currentUserEmail;
            $newID = $userClass->save($receiver);
            $data['receiver'] = $userClass->getOneById($newID);
        }
    }

    protected function prepareCampaignSummary(&$data): bool
    {
        $campaignId = acym_getVar('int', 'id');
        $campaign = empty($campaignId) ? null : $this->currentClass->getOneByIdWithMail($campaignId);
        if (is_null($campaign)) return false;

        $campaign->isAuto = $campaign->sending_type == $this->currentClass->getConstAuto();

        $startDate = '';
        if ($campaign->isAuto) {
            $textToDisplay = new stdClass();
            $textToDisplay->triggers = $campaign->sending_params;
            acym_trigger('onAcymDeclareSummary_triggers', [&$textToDisplay], 'plgAcymTime');
            $textToDisplay = $textToDisplay->triggers;
            if (!empty($campaign->sending_params['start_date'])) {
                $startDate = $campaign->sending_params['start_date'];
            }
        }

        $data['automatic'] = [
            'isAuto' => $campaign->isAuto,
            'text' => empty($textToDisplay)
                ? ''
                : acym_translation('ACYM_THIS_WILL_GENERATE_CAMPAIGN_AUTOMATICALLY').' '.acym_strtolower(
                    $textToDisplay[$textToDisplay['trigger_type']]
                ),
            'startDate' => $startDate,
        ];
        $data['campaignInformation'] = $campaign;
        $data['mailId'] = $campaign->mail_id;

        return true;
    }

    protected function prepareMailDataSummary($data, $mailId)
    {
        $mailData = $data['mailClass']->getOneById($mailId);
        $mailData->from_name = empty($mailData->from_name) ? $this->config->get('from_name') : $mailData->from_name;
        $mailData->from_email = empty($mailData->from_email) ? $this->config->get('from_email') : $mailData->from_email;

        $useFromInReply = $this->config->get('from_as_replyto');
        $replytoName = $this->config->get('replyto_name');
        $replytoEmail = $this->config->get('replyto_email');

        if (!empty($mailData->reply_to_name)) {
            $replytoName = $mailData->reply_to_name;
        } elseif ($useFromInReply != 0 || empty($replytoName)) {
            $replytoName = $this->config->get('from_name');
        }

        if (!empty($mailData->reply_to_email)) {
            $replytoEmail = $mailData->reply_to_email;
        } elseif ($useFromInReply != 0 || empty($replytoEmail)) {
            $replytoEmail = $this->config->get('from_email');
        }

        $mailData->reply_to_name = $replytoName;
        $mailData->reply_to_email = $replytoEmail;

        acym_trigger('replaceContent', [&$mailData, false]);
        acym_trigger('replaceUserInformation', [&$mailData, &$data['receiver'], false]);

        $editorHelper = new EditorHelper();
        $mailData->settings = json_decode($mailData->settings, true);
        $mailData->stylesheet .= $editorHelper->getSettingsStyle($mailData->settings);

        return $mailData;
    }

    protected function prepareReceiversSummary(&$data)
    {
        $nbSubscribers = 0;
        $campaignLists = $data['mailClass']->getAllListsWithCountSubscribersByMailIds([$data['campaignInformation']->mail_id]);
        $listsIds = [];

        if (!empty($campaignLists)) {
            foreach ($campaignLists as $oneList) {
                $listsIds[] = $oneList->list_id;
            }
            if (empty($data['campaignInformation']->sending_params)) {
                $listClass = new ListClass();
                $nbSubscribers = $listClass->getSubscribersCount($listsIds);
            } else {
                $campaignClass = new CampaignClass();
                $nbSubscribers = $campaignClass->countUsersCampaign($data['campaignInformation']->id);
            }
        }

        $data['listsReceiver'] = $campaignLists;
        $data['listsIds'] = $listsIds;
        $data['nbSubscribers'] = $nbSubscribers;

        if (!empty($data['campaignInformation']->sent) && !empty($data['campaignInformation']->active)) {
            $isSegmentExcluded = !empty($data['campaignInformation']->sending_params['segment']) && $data['campaignInformation']->sending_params['segment']['invert'] === 'exclude';

            $queueClass = new QueueClass();
            $data['mailInformation']->sending_params = $data['campaignInformation']->sending_params;
            $automationHelper = $queueClass->getMailReceivers($data['mailInformation'], true);
            $whereFlagPosition = array_search('user.automation LIKE "%a-1a%"', $automationHelper->where);
            if ($whereFlagPosition !== false && $isSegmentExcluded) {
                $automationHelper->where[$whereFlagPosition] = 'user.automation NOT LIKE "%a-1a%"';
            }
            $data['receiversNew'] = acym_loadResult($automationHelper->getQuery(['COUNT(DISTINCT `user`.id)']));

            $automationHelper = $queueClass->getMailReceivers($data['mailInformation'], false);
            $whereFlagPosition = array_search('user.automation LIKE "%a-1a%"', $automationHelper->where);
            if ($whereFlagPosition !== false && $isSegmentExcluded) {
                $automationHelper->where[$whereFlagPosition] = 'user.automation NOT LIKE "%a-1a%"';
            }
            $data['receiversAll'] = acym_loadResult($automationHelper->getQuery(['COUNT(DISTINCT `user`.id)']));
        }
    }

    public function tests()
    {
        $campaignClass = new CampaignClass();
        acym_setVar('step', 'tests');
        acym_setVar('layout', 'tests');
        $campaignId = acym_getVar('int', 'id', 0);

        $campaign = $campaignClass->getOneByIdWithMail($campaignId);

        if (empty($campaign->id)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_GET_CAMPAIGN_INFORMATION'), 'error');
            $this->listing();

            return;
        }

        $testEmails = acym_getVar('array', 'test_emails', [acym_currentUserEmail()]);
        foreach ($testEmails as $oneEmail) {
            $defaultEmails[$oneEmail] = $oneEmail;
        }

        $data = [
            'id' => $campaign->id,
            'test_emails' => $defaultEmails,
            'upgrade' => !acym_level(ACYM_ESSENTIAL),
            'version' => 'enterprise',
        ];
        if (!acym_isAcyCheckerInstalled()) {
            $lists = $campaignClass->getListsByMailId($campaign->mail_id);
            $listClass = new ListClass();
            $data['recipients'] = $listClass->getTotalSubCount($lists);
        }

        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, $campaign->sending_params);

        $this->breadcrumb[acym_escape($campaign->name)] = acym_completeLink('campaigns&task=edit&step=tests&id='.$campaign->id);
        parent::display($data);
    }

    public function saveTests()
    {
        $this->edit();
    }
}
