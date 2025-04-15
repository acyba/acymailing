<?php

namespace AcyMailing\Controllers\Campaigns;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailArchiveClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\ListClass;
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
use AcyMailing\Types\UploadFileType;
use stdClass;

trait Edition
{
    public function newEmail(): void
    {
        acym_setVar('layout', 'new_email');

        $data = [
            'selectedType' => acym_getVar('string', 'email_type', ''),
            'menuClass' => $this->menuClass,
        ];

        $listClass = new ListClass();
        if (acym_isAdmin()) {
            $returnUrl = urlencode(base64_encode(acym_completeLink('campaigns')));
            $favoriteTemplate = $this->config->get('favorite_template', 0);
            $welcomeUnsub = '&list_id={dataid}&type_editor=acyEditor&return='.$returnUrl;

            if (empty($favoriteTemplate)) {
                $data = array_merge(
                    $data,
                    [
                        'lists' => $listClass->getAllForSelect(),
                        'campaign_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=now'),
                        'campaign_test_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=now&abtest=1'),
                        'campaign_auto_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=auto'),
                        'followup_link' => acym_completeLink('campaigns&task=edit&step=followupTrigger'),
                        'campaign_scheduled_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=scheduled'),
                        'welcome_email_link' => acym_completeLink('mails&task=edit&type='.MailClass::TYPE_WELCOME.$welcomeUnsub),
                        'unsubscribe_email_link' => acym_completeLink('mails&task=edit&type='.MailClass::TYPE_UNSUBSCRIBE.$welcomeUnsub),
                    ]
                );
            } else {
                $data = array_merge(
                    $data,
                    [
                        'lists' => $listClass->getAllForSelect(),
                        'campaign_link' => acym_completeLink('campaigns&task=edit&step=editEmail&from='.$favoriteTemplate.'&campaign_type=now'),
                        'campaign_test_link' => acym_completeLink('campaigns&task=edit&step=editEmail&from='.$favoriteTemplate.'&campaign_type=now&abtest=1'),
                        'campaign_auto_link' => acym_completeLink('campaigns&task=edit&step=editEmail&from='.$favoriteTemplate.'&campaign_type=auto'),
                        'followup_link' => acym_completeLink('campaigns&task=edit&step=followupTrigger'),
                        'campaign_scheduled_link' => acym_completeLink('campaigns&task=edit&step=editEmail&from='.$favoriteTemplate.'&campaign_type=scheduled'),
                        'welcome_email_link' => acym_completeLink('mails&task=edit&type='.MailClass::TYPE_WELCOME.'&from='.$favoriteTemplate.$welcomeUnsub),
                        'unsubscribe_email_link' => acym_completeLink('mails&task=edit&type='.MailClass::TYPE_UNSUBSCRIBE.'&from='.$favoriteTemplate.$welcomeUnsub),
                    ]
                );
            }

            $data['extraBlocks'] = [];
            acym_trigger('getNewEmailsTypeBlock', [&$data['extraBlocks']]);
            $data['isNewslettersTab'] = !in_array(
                $data['selectedType'],
                array_merge(
                    [
                        MailClass::TYPE_WELCOME,
                        MailClass::TYPE_UNSUBSCRIBE,
                    ],
                    array_column($data['extraBlocks'], 'email_type')
                )
            );
        } else {
            global $Itemid;
            $itemId = empty($Itemid) ? '' : '&Itemid='.$Itemid;
            $returnUrl = urlencode(base64_encode(acym_frontendLink('frontcampaigns'.$itemId)));

            $welcomeUnsub = '&list_id={dataid}&type_editor=acyEditor&return='.$returnUrl.$itemId;
            $data = array_merge(
                $data,
                [
                    'lists' => $listClass->getAllForSelect(true, acym_currentUserId()),
                    'campaign_link' => acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type=now'.$itemId.'&'.acym_getFormToken()),
                    'campaign_scheduled_link' => acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type=scheduled'.$itemId.'&'.acym_getFormToken()),
                    'welcome_email_link' => acym_frontendLink('frontmails&task=edit&type='.MailClass::TYPE_WELCOME.$welcomeUnsub.'&'.acym_getFormToken()),
                    'unsubscribe_email_link' => acym_frontendLink('frontmails&task=edit&type='.MailClass::TYPE_UNSUBSCRIBE.$welcomeUnsub.'&'.acym_getFormToken()),
                ]
            );
        }

        parent::display($data);
    }

    private function prepareSegmentDisplay(array &$data, array $sendingParams): void
    {
        $data['menuClass'] = $this->menuClass;
        $data['displaySegmentTab'] = !empty($sendingParams) && array_key_exists('segment', $sendingParams);
    }

    public function chooseTemplate(): void
    {
        acym_setVar('layout', 'choose_email');
        acym_setVar('step', 'chooseTemplate');
        $pagination = new PaginationHelper();

        // Get filters data
        $campaignId = acym_getVar('int', 'campaignId', 0);
        $campaignClass = new CampaignClass();
        $searchFilter = $this->getVarFiltersListing('string', 'mailchoose_search', '');
        $tagFilter = $this->getVarFiltersListing('string', 'mailchoose_tag', '');
        $ordering = $this->getVarFiltersListing('string', 'mailchoose_ordering', 'creation_date');
        $orderingSortOrder = $this->getVarFiltersListing('string', 'mailchoose_ordering_sort_order', 'DESC');
        $campaign = $campaignClass->getOneByIdWithMail($campaignId);
        $campaignType = $this->getVarFiltersListing('string', 'campaign_type', 'now');
        $abTest = acym_getVar('bool', 'abtest', false);

        $this->setTaskListing($campaignType);

        if (!empty($campaign)) {
            if (!$campaignClass->hasUserAccess($campaign->id)) {
                die('Access denied for this campaign');
            }
            $this->breadcrumb[$campaign->name] = '';
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW_CAMPAIGN')] = '';
        }

        // Get pagination data
        $mailsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'mailchoose_pagination_page', 1);

        $mailClass = new MailClass();
        $matchingMails = $mailClass->getMatchingElements(
            [
                'ordering' => $ordering,
                'ordering_sort_order' => $orderingSortOrder,
                'search' => $searchFilter,
                'elementsPerPage' => $mailsPerPage,
                'offset' => ($page - 1) * $mailsPerPage,
                'tag' => $tagFilter,
                'creator_id' => $this->setFrontEndParamsForTemplateChoose(),
                'gettingTemplates' => true,
            ]
        );

        // Prepare the pagination
        $pagination->setStatus($matchingMails['total']->total, $page, $mailsPerPage);

        $tagClass = new TagClass();

        $data = [
            'allMails' => $matchingMails['elements'],
            'allTags' => $tagClass->getAllTagsByType(TagClass::TYPE_MAIL),
            'pagination' => $pagination,
            'search' => $searchFilter,
            'tag' => $tagFilter,
            'ordering' => $ordering,
            'campaignID' => $campaignId,
            'campaign_type' => $campaignType,
            'abtest' => $abTest,
        ];
        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, empty($campaign->sending_params) ? [] : $campaign->sending_params);

        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    protected function setFrontEndParamsForTemplateChoose(): int
    {
        return 0;
    }

    private function prepareEditCampaign(array &$data): void
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);
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
            if (!$campaignClass->hasUserAccess($campaignId)) {
                die('Access denied for this campaign');
            }

            if (!empty($mailId)) {
                $campaignClass->resetAbTestVersion($campaignId);
            }

            $data['mailInformation'] = $campaignClass->getOneByIdWithMail($campaignId);
            if (empty($mailId)) {
                $mailId = $data['mailInformation']->mail_id;
            }
            $editLink .= '&campaignId='.$campaignId;

            if (in_array($data['mailInformation']->sending_type, ['birthday', 'woocommerce_cart'])) {
                $this->setTaskListing($data['mailInformation']->sending_type);
            }
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
            if (!acym_isAdmin() && ACYM_CMS === 'joomla' && acym_isPluginActive('sef')) {
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
        if (empty($data['mailInformation']->body)) {
            $data['mailInformation']->body = '';
        }
        $pluginHelper->cleanHtml($data['mailInformation']->body);

        $editLink .= '&type_editor='.$data['typeEditor'];
        $this->breadcrumb[acym_escape(empty($data['mailInformation']->name) ? acym_translation('ACYM_NEW_CAMPAIGN') : $data['mailInformation']->name)] = acym_completeLink(
            $editLink
        );
    }

    private function prepareEditor(array &$data): void
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
        } else {
            $this->loadScripts['edit_email'][] = 'dtextPicker';
        }
    }

    public function prepareMaxUpload(array &$data): void
    {
        $maxupload = ini_get('upload_max_filesize');
        $maxpost = ini_get('post_max_size');
        $data['maxupload'] = acym_bytes($maxupload) > acym_bytes($maxpost) ? $maxpost : $maxupload;
    }

    private function prepareAbTest(array &$data, bool $editor = true): void
    {
        $data['abtest'] = false;

    }

    private function prepareMultilingual(array &$data, bool $editor = true): void
    {
        $data['multilingual'] = 0;

    }

    private function prepareAllMailsForAbtest(array &$data): void
    {
        $mailClass = new MailClass();

        $mails = $mailClass->getParentAndChildMails($data['mailId']);

        if (empty($mails)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_ABTEST_MAILS'), 'error');

            return;
        }

        foreach ($mails as $key => $oneMail) {
            $mails[$key] = $this->prepareMailDataSummary($data, (int)$oneMail->id);
        }

        $data['abtest_mails'] = $mails;
    }

    private function prepareAllMailsForMultilingual(array &$data): void
    {
        $mailClass = new MailClass();

        $mails = $mailClass->getMultilingualMails($data['mailId']);

        if (empty($mails)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_MULTILINGUAL_MAILS'), 'error');

            return;
        }

        foreach ($mails as $key => $oneMail) {
            $mails[$key] = $this->prepareMailDataSummary($data, (int)$oneMail->id);
        }

        $data['multilingual_mails'] = $mails;
    }

    public function editEmail(): void
    {
        acym_setVar('layout', 'edit_email');
        acym_setVar('numberattachment', '0');
        acym_setVar('step', 'editEmail');

        $tagClass = new TagClass();

        $data = [
            'containerClass' => $this->stepContainerClass,
            'social_icons' => $this->config->get('social_icons', '{}'),
            'allTags' => $tagClass->getAllTagsByType(TagClass::TYPE_MAIL),
            'campaign_type' => acym_getVar('string', 'campaign_type', 'now'),
            'typeEditor' => acym_getVar('string', 'type_editor', ''),
            'uploadFileType' => new UploadFileType(),
        ];

        $this->prepareEditCampaign($data);
        $this->prepareEditor($data);
        $this->prepareMaxUpload($data);
        $this->prepareAbTest($data);
        $this->prepareMultilingual($data);
        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, empty($data['mailInformation']->sending_params) ? [] : $data['mailInformation']->sending_params);

        $data['before-save'] = '';

        if ($data['editor']->editor === 'acyEditor') {
            $data['before-save'] = 'acym-data-before="acym_editorWysidVersions.storeCurrentValues(true);acym_editorWysidFormAction.cleanMceInput();"';
        }

        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    public function recipients(): void
    {
        acym_setVar('layout', 'recipients');
        acym_setVar('step', 'recipients');

        $campaignId = acym_getVar('int', 'campaignId');
        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();

        if (empty($campaignId) || !$campaignClass->hasUserAccess($campaignId)) {
            die('Access denied for this campaign');
        }

        $currentCampaign = $campaignClass->getOneByIdWithMail($campaignId);
        $this->breadcrumb[acym_escape($currentCampaign->name)] = acym_completeLink('campaigns&task=edit&step=recipients&campaignId='.$campaignId);

        $campaign = [
            'campaignInformation' => $campaignId,
            'currentCampaign' => $currentCampaign,
            'containerClass' => $this->stepContainerClass,
            'entitySelectHelper' => new EntitySelectHelper(),
        ];

        $campaignLists = $mailClass->getAllListsByMailId($currentCampaign->mail_id);
        $campaign['campaignListsId'] = array_keys($campaignLists);
        acym_arrayToInteger($campaign['campaignListsId']);
        $campaign['campaignListsSelected'] = json_encode($campaign['campaignListsId']);

        $this->prepareListingClasses($campaign);
        $this->prepareSegmentDisplay($campaign, $campaign['currentCampaign']->sending_params);

        parent::display($campaign);
    }

    public function segment(): void
    {
        acym_setVar('layout', 'segment');
        acym_setVar('step', 'segment');

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $campaignId = acym_getVar('int', 'campaignId');

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


        $this->breadcrumb[acym_escape($mail->name)] = acym_completeLink(acym_completeLink('campaigns&task=edit&step=recipients&campaignId='.$campaign->id));
        parent::display($data);
    }

    public function sendSettings(): void
    {
        acym_setVar('layout', 'send_settings');
        acym_setVar('step', 'sendSettings');
        $campaignId = acym_getVar('int', 'campaignId');
        $campaignClass = new CampaignClass();
        $campaignInformation = empty($campaignId) ? null : $campaignClass->getOneById($campaignId);

        if (is_null($campaignInformation)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_GET_CAMPAIGN_INFORMATION'), 'error');
            $this->listing();

            return;
        }

        if (!$campaignClass->hasUserAccess($campaignId)) {
            die('Access denied for this campaign');
        }

        //To know if we create or modify the campaign
        $from = acym_getVar('string', 'from');

        $campaignClass = new CampaignClass();
        $currentCampaign = $campaignClass->getOneByIdWithMail($campaignId);
        $this->breadcrumb[acym_escape($currentCampaign->name)] = acym_completeLink('campaigns&task=edit&step=sendSettings&campaignId='.$campaignId);

        if (!empty($currentCampaign->sent) && empty($currentCampaign->active)) {
            $currentCampaign->sending_date = '';
        }

        $data = [
            'nbSubscribers' => $campaignClass->countUsersCampaign($campaignId, true),
            'from' => $from,
            'suggestedDate' => acym_date('1534771620', 'j M Y H:i'),
            'campaignClass' => $campaignClass,
        ];

        $data['currentCampaign'] = $currentCampaign;
        $data['currentCampaign']->send_now = $currentCampaign->sending_type === CampaignClass::SENDING_TYPE_NOW;
        $data['currentCampaign']->send_scheduled = $currentCampaign->sending_type === CampaignClass::SENDING_TYPE_SCHEDULED;
        $data['currentCampaign']->send_auto = $currentCampaign->sending_type === CampaignClass::SENDING_TYPE_AUTO;

        // Handle special emails
        $data['currentCampaign']->send_specific = [];
        if (!in_array($currentCampaign->sending_type, CampaignClass::SENDING_TYPES)) {
            acym_trigger(
                'getCampaignSpecificSendSettings',
                [
                    $currentCampaign->sending_type,
                    $currentCampaign->sending_params,
                    &$data['currentCampaign']->send_specific,
                ]
            );
        }

        $data['senderInformations'] = new stdClass();
        $data['senderInformations']->from_name = empty($currentCampaign->from_name) ? '' : $currentCampaign->from_name;
        $data['senderInformations']->from_email = empty($currentCampaign->from_email) ? '' : $currentCampaign->from_email;
        $data['senderInformations']->reply_to_name = empty($currentCampaign->reply_to_name) ? '' : $currentCampaign->reply_to_name;
        $data['senderInformations']->reply_to_email = empty($currentCampaign->reply_to_email) ? '' : $currentCampaign->reply_to_email;
        $data['senderInformations']->bounce_email = empty($currentCampaign->bounce_email) ? '' : $currentCampaign->bounce_email;

        $data['config_values'] = new stdClass();
        $data['config_values']->from_name = $this->config->get('from_name');
        $data['config_values']->from_email = $this->config->get('from_email');
        $data['config_values']->reply_to_name = $this->config->get('replyto_name');
        $data['config_values']->reply_to_email = $this->config->get('replyto_email');
        $data['config_values']->bounce_email = $this->config->get('bounce_email');

        $triggers = [];

        acym_trigger('onAcymDeclareTriggers', [&$triggers, &$currentCampaign->sending_params], 'plgAcymTime');
        $triggers = $triggers['classic'];

        $data['triggers_select'] = [];
        $data['triggers_display'] = [];

        foreach ($triggers as $key => $trigger) {
            $data['triggers_select'][$key] = $trigger->name;
            $data['triggers_display'][$key] = $trigger->option;
        }

        if (!empty($data['currentCampaign']->sending_params) && empty($data['currentCampaign']->sending_params['trigger_type'])) {
            foreach (array_keys($triggers) as $oneTrigger) {
                if (!empty($data['currentCampaign']->sending_params[$oneTrigger])) {
                    $data['currentCampaign']->sending_params['trigger_type'] = $oneTrigger;
                }
            }
        }

        $data['containerClass'] = $this->stepContainerClass;
        $data['langChoice'] = acym_isMultilingual() ? '' : acym_languageOption($data['currentCampaign']->links_language, 'senderInformation[links_language]');
        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, $data['currentCampaign']->sending_params);
        $this->prepareMultilingualOption($data);

        parent::display($data);
    }

    public function saveEditEmail(bool $ajax = false): int
    {
        acym_checkToken();

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $formData = acym_getVar('array', 'mail', []);
        $versions = acym_getVar('array', 'versions', [], 'REQUEST', ACYM_ALLOWRAW);
        $versionType = acym_getVar('string', 'version_type', '');
        $allowedFields = acym_getColumns('mail');
        $campaignId = acym_getVar('int', 'campaignId', 0);
        $campaignType = acym_getVar('string', 'campaign_type', 'now');

        $types = [
            'now' => CampaignClass::SENDING_TYPE_NOW,
            'auto' => CampaignClass::SENDING_TYPE_AUTO,
            'scheduled' => CampaignClass::SENDING_TYPE_SCHEDULED,
        ];

        acym_trigger('getCampaignTypes', [&$types]);

        if (empty($campaignId)) {
            $mail = new stdClass();
            $mail->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
            $mail->type = MailClass::TYPE_STANDARD;

            $campaign = new stdClass();
            $campaign->draft = 1;
            $campaign->active = 0;
            $campaign->sending_type = $types[$campaignType];
            $campaign->sent = 0;
            $campaign->sending_params = [];
        } else {
            if (!$campaignClass->hasUserAccess($campaignId)) {
                die('Access denied for this campaign');
            }

            $campaign = $campaignClass->getOneById($campaignId);
            $mail = $mailClass->getOneById($campaign->mail_id);
            $mailArchiveClass = new MailArchiveClass();
            $archive = $mailArchiveClass->getOneByMailId($mail->id);
            if (!empty($archive)) {
                $mailArchiveClass->delete($archive->id);
            }
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

        if (!empty($versions)) {
            if (!empty($versions['main']['subject'])) $mail->subject = $versions['main']['subject'];
            if (!empty($versions['main']['preview'])) $mail->preheader = $versions['main']['preview'];
            if (!empty($versions['main']['content'])) $mail->body = $versions['main']['content'];
            if (!empty($versions['main']['content'])) $mail->settings = $versions['main']['settings'];
            if (!empty($versions['main']['content'])) $mail->stylesheet = $versions['main']['stylesheet'];

            if ($versionType === 'multilingual') {
                $mail->links_language = $this->config->get('multilingual_default');
            }

            unset($versions['main']);
        }

        if ($mailID = $mailClass->save($mail)) {
            if (acym_getVar('string', 'nextstep', '') === 'listing') {
                acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'));
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            if (!empty($mailClass->errors)) {
                acym_enqueueMessage($mailClass->errors, 'error');
            }

            if (!$ajax) {
                $this->listing();
            }

            return 0;
        }

        if (!empty($versions) && in_array($versionType, ['multilingual', 'abtest'])) {
            $abTestSendingParams = empty($campaign->sending_params['abtest']) ? [] : $campaign->sending_params['abtest'];
            foreach ($versions as $code => $version) {
                if (empty($version['subject'])) {
                    if ($versionType === 'multilingual') {
                        $mailClass->delete($mailClass->getTranslationId($mailID, $code));
                    } elseif (!empty($abTestSendingParams[$code])) {
                        $mailClass->delete($abTestSendingParams[$code]);
                    }
                    continue;
                }

                unset($mail->id);
                $versionId = null;
                if ($versionType === 'multilingual') {
                    $versionId = $mailClass->getTranslationId($mailID, $code);
                } elseif (!empty($abTestSendingParams[$code])) {
                    $versionId = $abTestSendingParams[$code];
                }
                if (!empty($versionId)) {
                    $mail->id = $versionId;
                }

                $mail->subject = $version['subject'];
                $mail->preheader = $version['preview'];
                $mail->body = $version['content'];
                $mail->parent_id = $mailID;
                $mail->settings = $version['settings'];
                $mail->stylesheet = $version['stylesheet'];

                if ($versionType === 'multilingual') {
                    $mail->links_language = $code;
                    $mail->language = $code;
                }

                $versionMailId = $mailClass->save($mail);
                $abTestSendingParams[$code] = $versionMailId;
            }

            if ($versionType === 'abtest') {
                $campaign->sending_params['abtest'] = $abTestSendingParams;
            }
        }

        $campaign->mail_id = $mailID;
        $campaign->id = $campaignClass->save($campaign);

        if (!$ajax) {
            acym_setVar('campaignId', $campaign->id);
            $this->edit();
        }

        return (int)$campaign->id;
    }

    public function saveRecipients(): void
    {
        $allLists = json_decode(acym_getVar('string', 'acym__entity_select__selected'));
        $allListsUnselected = json_decode(acym_getVar('string', 'acym__entity_select__unselected'));
        $campaignId = acym_getVar('int', 'campaignId');
        $addSegmentStep = acym_getVar('int', 'add_segment_step');

        $campaignClass = new CampaignClass();

        if (!$campaignClass->hasUserAccess($campaignId)) {
            die('Access denied for this campaign');
        }

        $currentCampaign = $campaignClass->getOneByIdWithMail($campaignId);

        if ((!$currentCampaign->sent || $currentCampaign->active) && !empty($currentCampaign->mail_id)) {
            $campaignClass->manageListsToCampaign($allLists, $currentCampaign->mail_id, $allListsUnselected);
            if (acym_getVar('string', 'nextstep', '') === 'listing') {
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

        acym_setVar('campaignId', $currentCampaign->id);

        $this->edit();
    }

    public function saveSegment(): void
    {
        if (!acym_isAdmin()) {
            die('Access denied for segments');
        }

        $segmentSelected = acym_getVar('int', 'segment_selected', 0);
        $filters = acym_getVar('array', 'acym_action', []);
        $campaignId = acym_getVar('int', 'campaignId', 0);

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

    public function saveSendSettings(): void
    {
        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $campaignId = acym_getVar('int', 'campaignId');

        if (!$campaignClass->hasUserAccess($campaignId)) {
            die('Access denied for this campaign');
        }

        $senderInformation = acym_getVar('', 'senderInformation');
        $sendingDate = acym_getVar('string', 'sendingDate');
        $sendingType = acym_getVar('string', 'sending_type', CampaignClass::SENDING_TYPE_NOW);
        $sendingParams = acym_getVar('array', 'sending_params', []);
        $specificSendingParams = [];
        $isScheduled = CampaignClass::SENDING_TYPE_SCHEDULED === $sendingType;

        $currentCampaign = $campaignClass->getOneById($campaignId);

        if (empty($currentCampaign)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_DOESNT_EXISTS'), 'error');

            $this->listing();

            return;
        }

        if (CampaignClass::SENDING_TYPE_AUTO === $sendingType) {
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

        if (!empty($currentCampaign->sending_params['abtest']) && !empty($sendingParams['abtest'])) {
            $sendingParams['abtest'] = array_merge($currentCampaign->sending_params['abtest'], $sendingParams['abtest']);
        }

        // Handle special emails
        if (!in_array($sendingType, CampaignClass::SENDING_TYPES)) {
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
        if (empty($currentCampaign->sending_params)) {
            $currentCampaign->sending_params = [];
        }

        $currentCampaign->sending_params = array_merge($currentCampaign->sending_params, $sendingParams, $specificSendingParams);
        if (!empty($currentCampaign->sending_params['admin_notification_emails']) && empty($sendingParams['admin_notification_emails'])) {
            $currentCampaign->sending_params['admin_notification_emails'] = [];
        }

        if (empty($currentMail) || empty($senderInformation)) {
            $this->listing();

            return;
        }

        $currentMail->from_name = $senderInformation['from_name'];
        $currentMail->from_email = $senderInformation['from_email'];
        $currentMail->reply_to_name = $senderInformation['reply_to_name'];
        $currentMail->reply_to_email = $senderInformation['reply_to_email'];
        $currentMail->bounce_email = $senderInformation['bounce_email'];
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
            if (acym_getVar('string', 'nextstep', '') === 'listing') {
                acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'));
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
    public function saveSummary(): void
    {
        $this->edit();
    }

    public function summary(): void
    {
        acym_setVar('step', 'summary');
        acym_setVar('layout', 'summary');

        $data = [
            'mailClass' => new MailClass(),
            'campaignClass' => new CampaignClass(),
            'containerClass' => $this->stepContainerClass,
        ];

        $this->prepareCurrentUserSummary($data);
        if (!$this->prepareCampaignSummary($data)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_GET_CAMPAIGN_INFORMATION'), 'error');
            $this->listing();

            return;
        }

        $data['mailInformation'] = $this->prepareMailDataSummary($data, (int)$data['campaignInformation']->mail_id);
        $this->prepareReceiversSummary($data);
        $this->prepareAbTest($data, false);
        $this->prepareAllMailsForAbtest($data);
        $this->prepareMultilingual($data, false);
        $this->prepareAllMailsForMultilingual($data);
        $this->prepareListingClasses($data);
        $this->prepareSegmentData($data);
        $this->prepareSegmentDisplay($data, $data['campaignInformation']->sending_params);

        $this->breadcrumb[$data['campaignInformation']->name] = acym_completeLink('campaigns&task=edit&step=summary&campaignId='.$data['campaignInformation']->id);
        parent::display($data);
    }

    private function prepareSegmentData(array &$data): void
    {
        if (empty($data['campaignInformation']->sending_params['segment'])) {
            return;
        }

        $segmentParams = $data['campaignInformation']->sending_params['segment'];

        $isExcluded = !empty($segmentParams['invert']) && $segmentParams['invert'] === 'exclude';

        $segmentController = new SegmentsController();

        if (!empty($segmentParams['segment_id'])) {
            $segmentClass = new SegmentClass();
            $segment = $segmentClass->getOneById($segmentParams['segment_id']);

            $data['segment'] = [
                'name' => $segment->name,
                'count' => $segmentController->countSegmentById((int)$segment->id, $data['listsIds'], false, $isExcluded),
            ];
        } else {
            $data['segment'] = [
                'name' => acym_translation('ACYM_YOUR_CUSTOM_SEGMENT'),
                'count' => $segmentController->countSegmentByParams($segmentParams, $data['listsIds'], $isExcluded),
            ];
        }
    }

    private function prepareCurrentUserSummary(array &$data): void
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

    private function prepareCampaignSummary(array &$data): bool
    {
        $campaignId = acym_getVar('int', 'campaignId');
        $campaignClass = new CampaignClass();
        $campaign = empty($campaignId) ? null : $campaignClass->getOneByIdWithMail($campaignId);
        if (is_null($campaign)) {
            return false;
        }

        if (!$campaignClass->hasUserAccess($campaignId)) {
            die('Access denied for this campaign');
        }

        $campaign->isAuto = $campaign->sending_type === CampaignClass::SENDING_TYPE_AUTO;

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

        acym_trigger('onAcymCampaignSummary', [&$data]);

        return true;
    }

    private function prepareMailDataSummary(array &$data, int $mailId): object
    {
        $mailArchiveClass = new MailArchiveClass();
        $data['isArchiveCached'] = !empty($mailArchiveClass->getOneByMailId($mailId));

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
        $mailData->settings = empty($mailData->settings) ? [] : json_decode($mailData->settings, true);
        $mailData->stylesheet .= $editorHelper->getSettingsStyle($mailData->settings);

        return $mailData;
    }

    protected function prepareReceiversSummary(array &$data): void
    {
        $nbSubscribers = 0;
        $campaignLists = $data['mailClass']->getAllListsWithCountSubscribersByMailIds([$data['campaignInformation']->mail_id]);
        $listsIds = [];

        if (!empty($campaignLists)) {
            foreach ($campaignLists as $oneList) {
                $listsIds[] = $oneList->list_id;
            }

            if (empty($data['campaignInformation']->sending_params)) {
                // No segment saved, get subscribers count the easy way
                $listClass = new ListClass();
                $nbSubscribers = $listClass->getSubscribersCount($listsIds);
            } else {
                // There may be segments
                $campaignClass = new CampaignClass();
                $nbSubscribers = $campaignClass->countUsersCampaign($data['campaignInformation']->id);
            }
        }

        $data['listsReceiver'] = $campaignLists;
        $data['listsIds'] = $listsIds;
        $data['nbSubscribers'] = $nbSubscribers;

        // Campaign already sent, calculate the number of new receivers
        if (!empty($data['campaignInformation']->sent) && !empty($data['campaignInformation']->active)) {
            $queueClass = new QueueClass();
            $data['mailInformation']->sending_params = $data['campaignInformation']->sending_params;

            $automationHelper = $queueClass->getMailReceivers($data['mailInformation'], true);
            $data['receiversNew'] = acym_loadResult($automationHelper->getQuery(['COUNT(DISTINCT `user`.id)']));
            $automationHelper->removeFlag(SegmentsController::FLAG_COUNT);
        }
    }

    public function tests(): void
    {
        $campaignClass = new CampaignClass();
        acym_setVar('step', 'tests');
        acym_setVar('layout', 'tests');
        $campaignId = acym_getVar('int', 'campaignId', 0);

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

        $mailClass = new MailClass();
        $emailsToTest = $mailClass->getParentAndChildMails($campaign->mail_id);

        $data = [
            'id' => $campaign->id,
            'currentCampaign' => $campaign,
            'test_emails' => $defaultEmails,
            'upgrade' => !acym_level(ACYM_ESSENTIAL),
            'version' => 'enterprise',
            'emails_to_test' => $emailsToTest,
        ];
        if (!acym_isAcyCheckerInstalled()) {
            $lists = $campaignClass->getListsByMailId($campaign->mail_id);
            $listClass = new ListClass();
            $data['recipients'] = $listClass->getTotalSubCount($lists);
        }

        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, $campaign->sending_params);

        $this->breadcrumb[acym_escape($campaign->name)] = acym_completeLink('campaigns&task=edit&step=tests&campaignId='.$campaign->id);
        parent::display($data);
    }

    public function saveTests(): void
    {
        if (!acym_isAdmin()) {
            die('Access denied for tests step');
        }

        $this->edit();
    }
}
