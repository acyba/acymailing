<?php

class MailsController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $type = acym_getVar('string', 'type');
        $this->breadcrumb[acym_translation('automation' != $type ? 'ACYM_TEMPLATES' : 'ACYM_AUTOMATION')] = acym_completeLink('automation' != $type ? 'mails' : 'automation');
        $this->loadScripts = [
            'edit' => ['editor-wysid'],
        ];
        acym_header('X-XSS-Protection:0');
    }

    public function listing()
    {
        acym_setVar('layout', 'listing');

        // Get filters data
        $searchFilter = $this->getVarFiltersListing('string', 'mails_search', '');
        $tagFilter = $this->getVarFiltersListing('string', 'mails_tag', '');
        $ordering = $this->getVarFiltersListing('string', 'mails_ordering', 'creation_date');
        $status = 'standard';
        $orderingSortOrder = $this->getVarFiltersListing('cmd', 'mails_ordering_sort_order', 'desc');

        $pagination = acym_get('helper.pagination');
        // Get pagination data
        $mailsPerPage = $pagination->getListLimit();
        $page = acym_getVar('int', 'mails_pagination_page', 1);

        $requestData = [
            'ordering' => $ordering,
            'search' => $searchFilter,
            'elementsPerPage' => $mailsPerPage,
            'offset' => ($page - 1) * $mailsPerPage,
            'tag' => $tagFilter,
            'status' => $status,
            'ordering_sort_order' => $orderingSortOrder,
            'onlyStandard' => true,
        ];
        $matchingMails = $this->getMatchingElementsFromData($requestData, $status, $page);


        $matchingMailsNb = count($matchingMails['elements']);

        if (empty($matchingMailsNb) && $page > 1) {
            acym_setVar('mails_pagination_page', 1);
            $this->listing();

            return;
        }

        // Prepare the pagination
        $pagination->setStatus($matchingMails['total'], $page, $mailsPerPage);

        ob_start();
        require acym_getView('mails', 'listing_import');
        $templateImportView = ob_get_clean();

        $mailsData = [
            'allMails' => $matchingMails['elements'],
            'allTags' => acym_get('class.tag')->getAllTagsByType('mail'),
            'pagination' => $pagination,
            'search' => $searchFilter,
            'tag' => $tagFilter,
            'ordering' => $ordering,
            'status' => $status,
            'mailNumberPerStatus' => $matchingMails['status'],
            'orderingSortOrder' => $orderingSortOrder,
            'templateImportView' => $templateImportView,
        ];

        if (!empty($mailsData['tag'])) {
            $mailsData['status_toolbar'] = [
                'mails_tag' => $mailsData['tag'],
            ];
        }

        $this->prepareToolbar($mailsData);
        parent::display($mailsData);
    }

    public function prepareToolbar(&$data)
    {
        $toolbarHelper = acym_get('helper.toolbar');
        $toolbarHelper->addSearchBar($data['search'], 'mails_search', 'ACYM_SEARCH');
        $toolbarHelper->addFilterByTag($data, 'mails_tag', 'acym__templates__filter__tags acym__select');
        $toolbarHelper->addButton(acym_translation('ACYM_ADD_DEFAULT_TMPL'), ['data-task' => 'installDefaultTmpl', 'id' => 'acym__mail__install-default'], 'content_copy');
        $otherContent = acym_modal(
            '<i class="acymicon-download"></i>'.acym_translation('ACYM_IMPORT'),
            $data['templateImportView'],
            null,
            '',
            'class="acym__toolbar__button acym__toolbar__button-secondary cell medium-6 large-shrink" data-reload="true" data-ajax="false"'
        );

        $otherContent .= acym_modal(
            '<i class="acymicon-add"></i>'.acym_translation('ACYM_CREATE'),
            '<div class="cell grid-x grid-margin-x">
								<button type="button" data-task="edit" data-editor="acyEditor" class="acym__create__template button cell medium-auto margin-top-1">'.acym_translation('ACYM_DD_EDITOR').'</button>
								<button type="button" data-task="edit" data-editor="html" class="acym__create__template button cell large-auto small-6 margin-top-1 button-secondary">'.acym_translation('ACYM_HTML_EDITOR').'</button>
							</div>',
            '',
            '',
            'class="acym_vcenter acym__toolbar__button acym__toolbar__button-primary cell medium-6 large-shrink"',
            true,
            false
        );
        $toolbarHelper->addOtherContent($otherContent);

        $data['toolbar'] = $toolbarHelper;
    }

    public function choose()
    {
        acym_setVar('layout', 'choose');

        $this->breadcrumb[acym_translation('ACYM_CREATE')] = '';

        // Get filters data
        $searchFilter = acym_getVar('string', 'mailchoose_search', '');
        $tagFilter = acym_getVar('string', 'mailchoose_tag', 0);
        $ordering = acym_getVar('string', 'mailchoose_ordering', 'creation_date');
        $orderingSortOrder = acym_getVar('string', 'mailchoose_ordering_sort_order', 'DESC');

        // Get pagination data
        $mailsPerPage = 12;
        $page = acym_getVar('int', 'mailchoose_pagination_page', 1);

        $mailClass = acym_get('class.mail');
        $matchingMails = $mailClass->getMatchingElements(
            [
                'ordering' => $ordering,
                'ordering_sort_order' => $orderingSortOrder,
                'search' => $searchFilter,
                'elementsPerPage' => $mailsPerPage,
                'offset' => ($page - 1) * $mailsPerPage,
                'tag' => $tagFilter,
            ]
        );

        // Prepare the pagination
        $pagination = acym_get('helper.pagination');
        $pagination->setStatus($matchingMails['total'], $page, $mailsPerPage);

        $mailsData = [
            'allMails' => $matchingMails['elements'],
            'allTags' => acym_get('class.tag')->getAllTagsByType('mail'),
            'pagination' => $pagination,
            'search' => $searchFilter,
            'tag' => $tagFilter,
            'ordering' => $ordering,
            'type' => acym_getVar('string', 'type'),
        ];


        parent::display($mailsData);
    }

    public function edit()
    {
        $tempId = acym_getVar('int', 'id');
        $mailClass = acym_get('class.mail');
        $typeEditor = acym_getVar('string', 'type_editor');
        $notification = acym_getVar('cmd', 'notification');
        $return = acym_getVar('string', 'return', '');

        if (base64_decode($return, true) === false) {
            $return = empty($return) ? '' : $return;
        } else {
            $return = empty($return) ? '' : urldecode(base64_decode($return));
        }

        $type = acym_getVar('string', 'type');
        $fromId = acym_getVar('int', 'from');
        $listIds = acym_getVar('int', 'list_id', []);

        if (in_array($type, ['welcome', 'unsubscribe'])) {
            array_pop($this->breadcrumb);
            $this->breadcrumb[acym_translation('ACYM_LISTS')] = acym_completeLink('lists');

            if (!empty($listIds)) {
                $campaignController = acym_get('controller.campaigns');
                $campaignController->setTaskListing($type);
                $listIds = [$listIds];
            }
        }

        if (!empty($notification)) {
            $mail = $mailClass->getOneByName($notification);
            if (!empty($mail->id)) {
                $tempId = $mail->id;
            }
        }

        $isAutomationAdmin = false;
        $fromMail = '';

        if (!empty($fromId)) $fromMail = $mailClass->getOneById($fromId);

        // Automations or new mails
        if (strpos($type, 'automation') !== false || empty($tempId)) {
            if ($type == 'automation_admin') {
                $type = 'automation';
                $isAutomationAdmin = true;
            }

            if (empty($fromId)) {
                $mail = new stdClass();
                $mail->name = '';
                $mail->subject = '';
                $mail->preheader = '';
                $mail->tags = [];
                $mail->type = '';
                $mail->body = '';
                $mail->editor = 'automation' == $type ? 'acyEditor' : $typeEditor;
                $mail->headers = '';
                $mail->thumbnail = null;
                $mail->links_language = '';
            } else {
                $mail = $fromMail;
                $mail->id = 0;
                if (0 == $mail->drag_editor) {
                    $mail->editor = 'html';
                } else {
                    $mail->editor = !empty($typeEditor) ? $typeEditor : 'acyEditor';
                }
            }
            $mail->access = [];

            if (!empty($type)) $mail->type = $type;

            if ('automation' != $type || empty($fromId)) $mail->id = 0;

            switch ($type) {
                case 'welcome':
                    $breadcrumbTitle = 'ACYM_CREATE_WELCOME_MAIL';
                    break;
                case 'unsubscribe':
                    $breadcrumbTitle = 'ACYM_CREATE_UNSUBSCRIBE_MAIL';
                    break;
                case 'automation':
                    $breadcrumbTitle = 'ACYM_NEW_EMAIL';
                    break;
                default:
                    $breadcrumbTitle = 'ACYM_CREATE_TEMPLATE';
            }

            $breadcrumbTitle = acym_translation($breadcrumbTitle);
            $breadcrumbUrl = 'mails&task=edit&type_editor='.$typeEditor.(!empty($fromId) ? '&from='.$fromId : '').'&type='.$type;
        } else {
            // Existing mails and notifications
            $mail = $mailClass->getOneById($tempId);
            if (!empty($fromMail)) {
                $mail->drag_editor = $fromMail->drag_editor;
                $mail->body = $fromMail->body;
                $mail->stylesheet = $fromMail->stylesheet;
                $mail->settings = $fromMail->settings;
            }
            $mail->editor = $mail->drag_editor == 0 ? 'html' : 'acyEditor';
            if (!empty($typeEditor)) $mail->editor = $typeEditor;

            if (empty($notification)) {
                if (in_array($mail->type, ['welcome', 'unsubscribe'])) {
                    array_pop($this->breadcrumb);
                    $this->breadcrumb[acym_translation('ACYM_LISTS')] = acym_completeLink('lists');
                }

                $breadcrumbTitle = $mail->name;
                $breadcrumbUrl = 'mails&task=edit&id='.$mail->id;
            } else {
                if (empty($return)) {
                    $return = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
                }

                $notifName = acym_translation('ACYM_NOTIFICATIION_'.strtoupper(substr($mail->name, 4)));
                if (strpos($notifName, 'ACYM_NOTIFICATIION_') !== false) {
                    $notifName = $mail->name;
                }

                $breadcrumbTitle = $notifName;
                $breadcrumbUrl = 'mails&task=edit&notification='.$mail->name;
            }

            if (strpos($mail->stylesheet, '[class="') !== false) {
                acym_enqueueMessage(acym_translation('ACYM_WARNING_STYLESHEET_NOT_CORRECT'), 'warning');
            }
        }

        if (!empty($return)) $breadcrumbUrl .= '&return='.urlencode(base64_encode($return));
        $this->breadcrumb[acym_escape($breadcrumbTitle)] = acym_completeLink($breadcrumbUrl);

        $lists = [];

        if (in_array($mail->type, ['welcome', 'unsubscribe'])) {
            $listClass = acym_get('class.list');
            $lists = $listClass->getAllWithIdName();

            if (empty($listIds) && !empty($mail->id)) $listIds = $listClass->getListIdsByWelcomeUnsub($mail->id, $mail->type == 'welcome');
        }

        $data = [
            'mail' => $mail,
            'allTags' => acym_get('class.tag')->getAllTagsByType('mail'),
            'isAutomationAdmin' => $isAutomationAdmin,
            'social_icons' => $this->config->get('social_icons', '{}'),
            'fromId' => $fromId,
            'langChoice' => acym_languageOption($mail->links_language, 'mail[links_language]'),
            'list_id' => $listIds,
            'lists' => $lists,
        ];

        if (!empty($return)) $data['return'] = $return;

        acym_setVar('layout', 'edit');
        parent::display($data);
    }

    public function store($ajax = false)
    {
        acym_checkToken();

        $mailClass = acym_get('class.mail');
        $formData = acym_getVar('array', 'mail', []);
        $mail = new stdClass();
        $allowedFields = acym_getColumns('mail');
        $fromId = acym_getVar('int', 'fromId', '');
        $return = acym_getVar('string', 'return');
        $fromAutomation = false;
        if (!empty($return) && strpos($return, 'automation') !== false) $fromAutomation = true;
        foreach ($formData as $name => $data) {
            if (!in_array($name, $allowedFields)) {
                continue;
            }
            $mail->{$name} = $data;
        }

        $saveAsTmpl = acym_getVar('int', 'saveAsTmpl', 0);
        if ($saveAsTmpl === 1) {
            unset($mail->id);
            $mail->type = 'standard';
        }

        if ($fromAutomation) {
            acym_setVar('from', $mail->id);
            acym_setVar('type', 'automation');
            acym_setVar('type_editor', 'acyEditor');
        }

        if (empty($mail->subject) && !empty($mail->type) && $mail->type != 'standard') {
            return false;
        }

        $mail->tags = acym_getVar('array', 'template_tags', []);
        $mail->body = acym_getVar('string', 'editor_content', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->settings = acym_getVar('string', 'editor_settings', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->stylesheet = acym_getVar('string', 'editor_stylesheet', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->headers = acym_getVar('string', 'editor_headers', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->thumbnail = $fromAutomation ? '' : acym_getVar('string', 'editor_thumbnail', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->template = $fromAutomation ? 2 : 1;
        $mail->library = 0;
        $mail->drag_editor = strpos($mail->body, 'acym__wysid__template') === false ? 0 : 1;
        if ($fromAutomation) $mail->type = 'automation';
        if (empty($mail->id)) {
            $mail->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
        }

        // Use the thumbnail of the source mail if not modified
        if (!empty($fromId) && empty($mail->thumbnail) && !$fromAutomation) {
            $thumbname = $this->setThumbnailFrom($fromId);
            if (!empty($thumbname)) $mail->thumbnail = $thumbname;
        }

        $mailID = $mailClass->save($mail);
        if (!empty($mailID)) {

            if (!empty($mail->type) && in_array($mail->type, ['welcome', 'unsubscribe'])) {
                $listIds = acym_getVar('array', 'list_ids', []);
                $listClass = acym_get('class.list');
                $listClass->setWelcomeUnsubEmail($listIds, $mailID, $mail->type);
            }

            if (!$ajax) acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            if ($fromAutomation) {
                acym_setVar('from', $mailID);
                acym_setVar('type', 'automation');
                acym_setVar('type_editor', 'acyEditor');
            } else {
                acym_setVar('mailID', $mailID);
            }

            return $mailID;
        } else {
            if (!$ajax) acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            if (!empty($mailClass->errors)) {
                if (!$ajax) acym_enqueueMessage($mailClass->errors, 'error');
            }

            return false;
        }
    }

    protected function setThumbnailFrom($fromId)
    {
        $thumbNb = $this->config->get('numberThumbnail', 2);
        $fileName = 'thumbnail_'.($thumbNb + 1).'.png';
        $newConfig = new stdClass();
        $newConfig->numberThumbnail = $thumbNb + 1;
        $this->config->save($newConfig);

        $mailClass = acym_get('class.mail');
        $fromMail = $mailClass->getOneById($fromId);
        $fromThumbnail = $fromMail->thumbnail;

        $ret = acym_createFolder(ACYM_UPLOAD_FOLDER_THUMBNAIL);
        if (!$ret) return '';

        $fromThumbnailSource = acym_fileGetContent(acym_getMailThumbnail($fromThumbnail));
        if (empty($fromThumbnailSource)) return '';

        file_put_contents(ACYM_UPLOAD_FOLDER_THUMBNAIL.$fileName, $fromThumbnailSource);

        return $fileName;
    }

    public function apply()
    {
        $this->store();
        $mailId = acym_getVar('int', 'mailID', 0);
        acym_setVar('id', $mailId);
        $this->edit();
    }

    public function save()
    {
        $mailid = $this->store();

        // When saving notifications, we return to page where we clicked the "Edit email" button
        $return = str_replace('{mailid}', empty($mailid) ? '' : $mailid, acym_getVar('string', 'return'));
        if (empty($return)) {
            $this->listing();
        } else {
            acym_redirect($return);
        }
    }

    public function autoSave()
    {
        $mailClass = acym_get('class.mail');
        $mail = new stdClass();

        $language = acym_getVar('string', 'language', 'main');
        $mail->id = acym_getVar('int', 'mailId', 0);
        $mail->autosave = acym_getVar('string', 'autoSave', '', 'REQUEST', ACYM_ALLOWRAW);

        if (empty($mail->id) || !$mailClass->autoSave($mail, $language)) {
            echo 'error';
        } else {
            echo 'saved';
        }

        exit;
    }

    public function getTemplateAjax()
    {
        $pagination = acym_get('helper.pagination');
        $id = acym_getVar('int', 'id');
        $id = empty($id) ? '' : '&id='.$id;
        $searchFilter = acym_getVar('string', 'search', '');
        $tagFilter = acym_getVar('string', 'tag', 0);
        $ordering = 'creation_date';
        $orderingSortOrder = 'DESC';
        $type = acym_getVar('string', 'type', 'custom');
        $editor = acym_getVar('string', 'editor');
        $automation = acym_getVar('boolean', 'automation', false);
        $returnUrl = acym_getVar('string', 'return');
        $returnUrl = empty($returnUrl) || 'undefined' == $returnUrl ? '' : '&return='.urlencode(base64_encode($returnUrl));

        $mailsPerPage = $pagination->getListLimit();
        $page = acym_getVar('int', 'pagination_page_ajax', 1);
        $page != 'undefined' ? : $page = '1';

        $mailClass = acym_get('class.mail');
        $matchingMails = $mailClass->getMatchingElements(
            [
                'ordering' => $ordering,
                'ordering_sort_order' => $orderingSortOrder,
                'search' => $searchFilter,
                'elementsPerPage' => $mailsPerPage,
                'offset' => ($page - 1) * $mailsPerPage,
                'tag' => $tagFilter,
                'automation' => $automation,
                'onlyStandard' => true,
                'creator_id' => $this->setFrontEndParamsForTemplateChoose(),
            ]
        );

        $return = '<div class="grid-x grid-padding-x grid-padding-y grid-margin-x grid-margin-y xxlarge-up-6 large-up-4 medium-up-3 small-up-1 cell acym__template__choose__list">';

        foreach ($matchingMails['elements'] as $oneTemplate) {
            $return .= '<div class="cell grid-x acym__templates__oneTpl acym__listing__block" id="'.acym_escape($oneTemplate->id).'">
                <div class="cell acym__templates__pic text-center">';

            $url = acym_getVar('cmd', 'ctrl').'&task=edit&step=editEmail&from='.intval($oneTemplate->id).$returnUrl.'&type='.$type.$id;
            if (!empty($this->data['campaignInformation'])) $url .= '&id='.intval($this->data['campaignInformation']);
            if (!$automation || !empty($returnUrl)) $return .= '<a href="'.acym_completeLink($url, false, false, true).'">';

            $return .= '<img src="'.acym_escape(acym_getMailThumbnail($oneTemplate->thumbnail)).'" alt="template thumbnail"/>';
            if (!$automation || !empty($returnUrl)) $return .= '</a>';
            $return .= '<div class="acym__templates__choose__ribbon '.($oneTemplate->drag_editor ? 'acyeditor' : 'htmleditor').'">'.($oneTemplate->drag_editor ? 'AcyEditor' : 'HTML Editor').'</div>';

            if (strlen($oneTemplate->name) > 55) {
                $oneTemplate->name = substr($oneTemplate->name, 0, 50).'...';
            }
            $return .= '</div>
                            <div class="cell grid-x acym__templates__footer text-center">
                                <div class="cell acym__templates__footer__title" title="'.acym_escape($oneTemplate->name).'">'.acym_escape($oneTemplate->name).'</div>
                                <div class="cell">'.acym_date($oneTemplate->creation_date, 'M. j, Y').'</div>
                            </div>
                        </div>';
        }

        $return .= '</div>';

        $pagination->setStatus($matchingMails['total'], $page, $mailsPerPage);

        $return .= $pagination->displayAjax();

        echo $return;
        exit;
    }

    protected function setFrontEndParamsForTemplateChoose()
    {
        return '';
    }

    public function getMailContent()
    {
        $mailClass = acym_get('class.mail');
        $from = acym_getVar('string', 'from', '');

        if (empty($from)) {
            echo 'error';
            exit;
        }

        $echo = $mailClass->getOneById($from);

        if ($echo->drag_editor == 0) {
            echo 'no_new_editor';
            exit;
        }

        $echo = ['mailSettings' => $echo->settings, 'content' => $echo->body, 'stylesheet' => $echo->stylesheet];

        $echo = json_encode($echo);

        echo $echo;
        exit;
    }

    /*
     * Method used to send a test when editing a notification email
     */
    public function test()
    {
        $mailId = $this->store();
        $return = acym_getVar('string', 'return', '');
        acym_setVar('return', $return);
        acym_setVar('id', $mailId);

        $mailClass = acym_get('class.mail');
        $mail = $mailClass->getOneById($mailId);

        if (empty($mail)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), 'error');
            $this->edit();

            return;
        }

        $mailerHelper = acym_get('helper.mailer');
        $mailerHelper->autoAddUser = true;
        $mailerHelper->checkConfirmField = false;
        $mailerHelper->report = false;

        $currentEmail = acym_currentUserEmail();
        if ($mailerHelper->sendOne($mailId, $currentEmail)) {
            acym_enqueueMessage(acym_translation_sprintf('ACYM_SEND_SUCCESS', $mail->name, $currentEmail), 'info');
        } else {
            acym_enqueueMessage(acym_translation_sprintf('ACYM_SEND_ERROR', $mail->name, $currentEmail), 'error');
        }

        $this->edit();
    }

    public function sendTest()
    {
        $controller = acym_getVar('string', 'controller', 'mails');
        $result = new stdClass();
        $result->level = 'info';
        $result->message = '';

        $testNote = acym_getVar('string', 'test_note', '');

        if ($controller == 'mails') {
            $mailId = acym_getVar('int', 'id', 0);
        } else {
            $campaingId = acym_getVar('int', 'id', 0);
            $campaignClass = acym_get('class.campaign');
            $campaign = $campaignClass->getOneById($campaingId);
            if (empty($campaign)) {
                echo json_encode(['level' => 'error', 'message' => acym_translation('ACYM_CAMPAIGN_NOT_FOUND')]);

                exit;
            }

            $mailId = $campaign->mail_id;

            $languageVersion = acym_getVar('string', 'lang_version', 'main');
            if (!empty($languageVersion) && $languageVersion !== 'main') {
                $translationId = $this->currentClass->getTranslationId($mailId, $languageVersion);
                if (!empty($translationId)) $mailId = $translationId;
            }
        }

        $mailClass = acym_get('class.mail');
        $mail = $mailClass->getOneById($mailId);

        if (empty($mail)) {
            echo json_encode(['level' => 'error', 'message' => acym_translation('ACYM_EMAIL_NOT_FOUND')]);

            exit;
        }

        $mailerHelper = acym_get('helper.mailer');
        $mailerHelper->autoAddUser = true;
        $mailerHelper->checkConfirmField = false;
        $mailerHelper->report = false;


        $report = [];

        $testEmails = explode(',', acym_getVar('string', 'test_emails'));
        foreach ($testEmails as $oneAddress) {
            if (!$mailerHelper->sendOne($mail->id, $oneAddress, true, $testNote)) {
                $result->level = 'error';
                $result->timer = '';
            }

            if (!empty($mailerHelper->reportMessage)) {
                $report[] = $mailerHelper->reportMessage;
            }
        }

        $result->message = implode('<br/>', $report);
        echo json_encode($result);
        exit;
    }

    public function setNewThumbnail()
    {
        acym_checkToken();
        $contentThumbnail = acym_getVar('string', 'content', '');
        $file = acym_getVar('string', 'thumbnail', '');

        if (empty($file) || strpos($file, 'http') === 0) {
            $thumbNb = $this->config->get('numberThumbnail', 2);
            $file = 'thumbnail_'.($thumbNb + 1).'.png';
            $newConfig = new stdClass();
            $newConfig->numberThumbnail = $thumbNb + 1;
            $this->config->save($newConfig);
        }

        $extension = acym_fileGetExt($file);
        if (strpos($file, 'thumbnail_') === false || !in_array($extension, ['png', 'jpeg', 'jpg', 'gif'])) exit;

        acym_createFolder(ACYM_UPLOAD_FOLDER_THUMBNAIL);
        file_put_contents(ACYM_UPLOAD_FOLDER_THUMBNAIL.$file, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $contentThumbnail)));
        echo $file;

        exit;
    }

    public function loadCSS()
    {
        header('Content-Type: text/css');
        $idMail = acym_getVar('int', 'id', 0);
        if (empty($idMail)) {
            exit;
        }

        $mailClass = acym_get('class.mail');
        $mail = $mailClass->getOneById($idMail);

        echo $mailClass->buildCSS($mail->stylesheet);
        exit;
    }

    public function doUploadTemplate()
    {
        $mailClass = acym_get('class.mail');
        $mailClass->doupload();

        $this->listing();
    }

    public function setNewIconShare()
    {
        $socialName = acym_getVar('string', 'social', '');
        if (!in_array($socialName, ['facebook', 'twitter', 'instagram', 'linkedin', 'pinterest', 'vimeo', 'wordpress', 'youtube'])) {
            echo json_encode(
                [
                    'type' => 'error',
                    'message' => acym_translation_sprintf('ACYM_UNKNOWN_SOCIAL', $socialName),
                ]
            );
            exit;
        }
        $extension = pathinfo($_FILES['file']['name']);
        $newPath = ACYM_UPLOAD_FOLDER.'socials'.DS.$socialName;
        $newPathComplete = $newPath.'.'.$extension['extension'];

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'ico', 'bmp', 'svg'];
        if (!in_array($extension['extension'], $allowedExtensions)) {
            $errorMessage = acym_translation_sprintf('ACYM_ACCEPTED_TYPE', $extension['extension'], implode(', ', $allowedExtensions));
        } elseif (empty($socialName) || !acym_uploadFile($_FILES['file']['tmp_name'], ACYM_ROOT.$newPathComplete)) {
            $errorMessage = acym_translation_sprintf('ACYM_ERROR_UPLOADING_FILE_X', $newPathComplete);
        }

        if (!empty($errorMessage)) {
            echo json_encode(
                [
                    'type' => 'error',
                    'message' => $errorMessage,
                ]
            );
            exit;
        }

        $newConfig = new stdClass();
        $newConfig->social_icons = json_decode($this->config->get('social_icons', '{}'), true);

        $newImg = acym_rootURI().$newPathComplete;
        $newImgWithoutExtension = acym_rootURI().$newPath;

        $newConfig->social_icons[$socialName] = $newImg;
        $newConfig->social_icons = json_encode($newConfig->social_icons);
        $this->config->save($newConfig);

        echo json_encode(
            [
                'type' => 'success',
                'message' => acym_translation('ACYM_ICON_IMPORTED'),
                'url' => $newImgWithoutExtension,
                'extension' => $extension['extension'],
            ]
        );
        exit;
    }

    public function deleteMailAutomation()
    {
        $mailClass = acym_get('class.mail');
        $mailId = acym_getVar('int', 'id', 0);

        if (!empty($mailId)) $mailClass->delete($mailId);


        exit;
    }

    public function duplicateMailAutomation()
    {
        $mailClass = acym_get('class.mail');
        $mailId = acym_getVar('int', 'id', 0);
        $prevMail = acym_getVar('int', 'previousId');

        if (!empty($prevMail)) $mailClass->delete($prevMail);

        if (empty($mailId)) {
            echo json_encode(['error' => acym_translation_sprintf('ACYM_NOT_FOUND', acym_translation('ACYM_ID'))]);
            exit;
        }

        $mail = $mailClass->getOneById($mailId);

        if (empty($mail)) {
            echo json_encode(['error' => acym_translation_sprintf('ACYM_NOT_FOUND', acym_translation('ACYM_EMAIL'))]);
            exit;
        }

        $newMail = new stdClass();
        $newMail->name = $mail->name.'_copy';
        $newMail->thumbnail = '';
        $newMail->type = 'automation';
        $newMail->drag_editor = $mail->drag_editor;
        $newMail->library = 0;
        $newMail->body = $mail->body;
        $newMail->subject = $mail->subject;
        $newMail->template = 2;
        $newMail->from_name = $mail->from_name;
        $newMail->from_email = $mail->from_email;
        $newMail->reply_to_name = $mail->reply_to_name;
        $newMail->reply_to_email = $mail->reply_to_email;
        $newMail->bcc = $mail->bcc;
        $newMail->settings = $mail->settings;
        $newMail->stylesheet = $mail->stylesheet;
        $newMail->attachments = $mail->attachments;
        $newMail->headers = $mail->headers;

        $newMail->id = $mailClass->save($newMail);

        if (empty($newMail->id)) {
            echo json_encode(['error' => acym_translation('ACYM_COULD_NOT_DUPLICATE_EMAIL')]);
            exit;
        }

        echo json_encode($newMail);
        exit;
    }

    public function saveAjax()
    {
        $return = $this->store(true);
        echo json_encode(['error' => !$return ? acym_translation('ACYM_ERROR_SAVING') : '', 'data' => $return]);
        exit;
    }

    public function installDefaultTmpl()
    {
        $updateHelper = acym_get('helper.update');
        $updateHelper->installTemplates(true);

        $this->listing();
    }

    public function export()
    {
        acym_checkToken();

        // Get passed data and check if we have everything we need
        $templateId = acym_getVar('int', 'templateId', 0);

        if (empty($templateId)) exit;

        $template = $this->currentClass->getOneById($templateId);

        // We have all we need for the export, prepare the headers for the download
        $exportHelper = acym_get('helper.export');
        $exportHelper->exportTemplate($template);

        exit;
    }

    public function duplicate()
    {
        $templateId = acym_getVar('int', 'templateId', 0);

        if (empty($templateId)) {
            acym_enqueueMessage(acym_translation('ACYM_TEMPLATE_DUPLICATE_ERROR'), 'error');
            $this->listing();

            return;
        }

        $mailClass = acym_get('class.mail');
        $oldTemplate = $mailClass->getOneById($templateId);

        if (empty($oldTemplate)) {
            acym_enqueueMessage(acym_translation('ACYM_TEMPLATE_DUPLICATE_ERROR'), 'error');
            $this->listing();

            return;
        }

        $newTemplate = $oldTemplate;
        $newTemplate->id = 0;
        $newTemplate->name = $oldTemplate->name.'_copy';

        $mailClass->save($newTemplate);

        $this->listing();
    }

    public function delete()
    {
        $returnListing = acym_getVar('string', 'return_listing', '');
        parent::delete();
        if (!empty($returnListing)) {
            $link = acym_isAdmin() ? acym_completeLink($returnListing, false, true) : acym_frontendLink($returnListing);
            acym_redirect($link);
        }
    }
}

