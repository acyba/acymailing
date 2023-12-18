<?php

namespace AcyMailing\Controllers\Mails;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Controllers\CampaignsController;
use AcyMailing\Helpers\EditorHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Types\UploadfileType;

trait Edition
{
    public function edit()
    {
        if (!acym_isAdmin()) {
            acym_checkToken();
        }

        $tempId = acym_getVar('int', 'id');
        $mailClass = $this->currentClass;
        $typeEditor = acym_getVar('string', 'type_editor');
        $notification = acym_getVar('cmd', 'notification');
        $return = acym_getVar('string', 'return', '');
        $followupId = acym_getVar('int', 'followup_id', 0);
        $followupClass = new FollowupClass();

        $campaignController = new CampaignsController();

        if (base64_decode($return, true) === false) {
            $return = empty($return) ? '' : $return;
        } else {
            $return = empty($return) ? '' : urldecode(base64_decode($return));
        }

        $type = acym_getVar('string', 'type');
        $fromId = acym_getVar('int', 'from');
        $listIds = acym_getVar('int', 'list_id', []);

        if (in_array($type, [$mailClass::TYPE_WELCOME, $mailClass::TYPE_UNSUBSCRIBE])) {
            array_pop($this->breadcrumb);
            $this->breadcrumb[acym_translation('ACYM_LISTS')] = acym_completeLink('lists');

            if (!empty($listIds)) {
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

        if (!empty($fromId)) {
            $fromMail = $mailClass->getOneById($fromId);
        }

        if ($type === 'automation_admin') {
            $type = $mailClass::TYPE_AUTOMATION;
            $isAutomationAdmin = true;
        }

        // new mails
        if (empty($tempId)) {
            if (empty($fromId)) {
                $mail = new \stdClass();
                $mail->name = '';
                $mail->subject = '';
                $mail->preheader = '';
                $mail->tags = [];
                $mail->type = $type;
                $mail->body = '';
                $mail->editor = in_array($type, [$mailClass::TYPE_AUTOMATION, $mailClass::TYPE_FOLLOWUP]) ? 'acyEditor' : $typeEditor;
                $mail->headers = '';
                $mail->thumbnail = null;
                $mail->links_language = '';
                $mail->from_email = '';
                $mail->from_name = '';
                $mail->reply_to_email = '';
                $mail->reply_to_name = '';
            } else {
                $mail = $fromMail;
                $mail->id = 0;
                $mail->from_email = $fromMail->from_email;
                $mail->from_name = $fromMail->from_name;
                $mail->reply_to_email = $fromMail->reply_to_email;
                $mail->reply_to_name = $fromMail->reply_to_name;
                if (0 == $mail->drag_editor) {
                    $mail->editor = 'html';
                } else {
                    $mail->editor = !empty($typeEditor) ? $typeEditor : 'acyEditor';
                }
            }
            $mail->access = [];
            $mail->delay = 0;
            $mail->delay_unit = $followupClass::DEFAULT_DELAY_UNIT;

            if (!empty($type)) $mail->type = $type;

            if ($mailClass::TYPE_AUTOMATION != $type || empty($fromId)) $mail->id = 0;

            switch ($type) {
                case $mailClass::TYPE_WELCOME:
                    $breadcrumbTitle = 'ACYM_CREATE_WELCOME_MAIL';
                    break;
                case $mailClass::TYPE_UNSUBSCRIBE:
                    $breadcrumbTitle = 'ACYM_CREATE_UNSUBSCRIBE_MAIL';
                    break;
                case $mailClass::TYPE_AUTOMATION:
                    $breadcrumbTitle = 'ACYM_NEW_EMAIL';
                    break;
                case $mailClass::TYPE_FOLLOWUP:
                    $breadcrumbTitle = 'ACYM_NEW_FOLLOW_UP_EMAIL';
                    break;
                default:
                    $breadcrumbTitle = 'ACYM_CREATE_TEMPLATE';
            }

            $breadcrumbTitle = acym_translation($breadcrumbTitle);
            $breadcrumbUrl = 'mails&task=edit&type_editor='.$typeEditor.(!empty($fromId) ? '&from='.$fromId : '').'&type='.$type;
        } else {
            // Existing mails and notifications
            if (!$mailClass->hasUserAccess($tempId)) {
                die('Access denied for this email');
            }

            $mail = $mailClass->getOneById($tempId);

            if (!empty($fromMail)) {
                $mail->drag_editor = $fromMail->drag_editor;
                $mail->body = $fromMail->body;
                $mail->stylesheet = $fromMail->stylesheet;
                $mail->settings = $fromMail->settings;
                $mail->from_email = $fromMail->from_email;
                $mail->from_name = $fromMail->from_name;
                $mail->reply_to_email = $fromMail->reply_to_email;
                $mail->reply_to_name = $fromMail->reply_to_name;
            }

            if (!empty($followupId)) $followupClass->getDelaySettingToMail($mail, $followupId);

            $mail->editor = $mail->drag_editor == 0 ? 'html' : 'acyEditor';
            if (!empty($typeEditor)) $mail->editor = $typeEditor;

            if (empty($notification)) {
                if (in_array($mail->type, [$mailClass::TYPE_WELCOME, $mailClass::TYPE_UNSUBSCRIBE])) {
                    array_pop($this->breadcrumb);
                    $this->breadcrumb[acym_translation('ACYM_LISTS')] = acym_completeLink('lists');
                }

                if ($mail->type === $mailClass::TYPE_OVERRIDE) {
                    array_pop($this->breadcrumb);
                    $this->breadcrumb[acym_translation('ACYM_EMAILS_OVERRIDE')] = acym_completeLink('override');
                    acym_loadLanguageFile('plg_user_joomla', ACYM_BASE);
                    acym_loadLanguageFile('com_users');
                    $breadcrumbTitle = acym_translationSprintf(
                        preg_replace(
                            '#^{trans:([A-Z_]+)(|.+)*}$#',
                            '$1',
                            $mail->subject
                        ),
                        '{param1}',
                        '{param2}'
                    );
                } else {
                    $breadcrumbTitle = $mail->name;
                }

                $breadcrumbUrl = 'mails&task=edit&id='.$mail->id;
            } else {
                if (empty($return)) {
                    $return = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
                }

                $notifName = acym_translation('ACYM_NOTIFICATIION_'.strtoupper(substr($mail->name, 4)));
                if (strpos($notifName, 'ACYM_NOTIFICATIION_') !== false) {

                    array_pop($this->breadcrumb);
                    $this->breadcrumb[acym_translation('ACYM_CONFIGURATION')] = acym_completeLink('configuration');

                    $notifName = $mail->name;

                    if ($notifName === 'acy_confirm') {
                        $notifName = acym_translation('ACYM_CONFIRMATION_EMAIL');
                    } elseif ($notifName === 'acy_notification_create') {
                        $notifName = acym_translation('ACYM_NOTIFICATION_CREATE_EMAIL');
                    } elseif ($notifName === 'acy_notification_unsub') {
                        $notifName = acym_translation('ACYM_NOTIFICATION_UNSUB_EMAIL');
                    } elseif ($notifName === 'acy_notification_unsuball') {
                        $notifName = acym_translation('ACYM_NOTIFICATION_UNSUBALL_EMAIL');
                    } elseif ($notifName === 'acy_notification_subform') {
                        $notifName = acym_translation('ACYM_NOTIFICATION_SUBFORM_EMAIL');
                    } elseif ($notifName === 'acy_notification_profile') {
                        $notifName = acym_translation('ACYM_NOTIFICATION_PROFILE_EMAIL');
                    } elseif ($notifName === 'acy_notification_confirm') {
                        $notifName = acym_translation('ACYM_NOTIFICATION_CONFIRM_EMAIL');
                    }
                }

                $breadcrumbTitle = $notifName;
                $breadcrumbUrl = 'mails&task=edit&notification='.$mail->name;
            }

            if (!empty($mail->stylesheet) && strpos($mail->stylesheet, '[class="') !== false) {
                acym_enqueueMessage(acym_translation('ACYM_WARNING_STYLESHEET_NOT_CORRECT'), 'warning');
            }
        }

        if (!empty($return)) $breadcrumbUrl .= '&return='.urlencode(base64_encode($return));
        $this->breadcrumb[acym_escape($breadcrumbTitle)] = acym_completeLink($breadcrumbUrl);

        $lists = [];

        if (in_array($mail->type, [$mailClass::TYPE_WELCOME, $mailClass::TYPE_UNSUBSCRIBE])) {
            $listClass = new ListClass();
            $lists = $listClass->getAllWithIdName();

            if (empty($listIds) && !empty($mail->id)) $listIds = $listClass->getListIdsByWelcomeUnsub($mail->id, $mail->type == $mailClass::TYPE_WELCOME);
            if (!is_array($listIds)) {
                $listIds = [$listIds];
            }
        }

        if (!empty($mail->attachments) && !is_array($mail->attachments)) {
            $mail->attachments = json_decode($mail->attachments);
        } elseif (empty($mail->attachments)) {
            $mail->attachments = [];
        }

        $tagClass = new TagClass();
        $data = [
            'mail' => $mail,
            'allTags' => $tagClass->getAllTagsByType('mail'),
            'isAutomationAdmin' => $isAutomationAdmin,
            'social_icons' => $this->config->get('social_icons', '{}'),
            'fromId' => $fromId,
            'langChoice' => acym_languageOption($mail->links_language, 'mail[links_language]'),
            'list_id' => $listIds,
            'lists' => $lists,
            'delay_unit' => $followupClass->getDelayUnits(),
            'default_delay_unit' => $followupClass::DEFAULT_DELAY_UNIT,
            'followup_id' => $followupId,
            'uploadFileType' => new UploadfileType(),
            'mailClass' => $this->currentClass,
        ];

        $this->prepareMailMultilingual($data);
        $this->prepareEditorEdit($data);

        $campaignController->prepareMaxUpload($data);

        if (!empty($return)) $data['return'] = $return;

        acym_setVar('layout', 'edit');
        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    private function prepareMailMultilingual(&$data)
    {
        $mail = $data['mail'];
        if (in_array($mail->type, [$data['mailClass']::TYPE_WELCOME, $data['mailClass']::TYPE_UNSUBSCRIBE, $data['mailClass']::TYPE_NOTIFICATION]) && acym_isMultilingual()) {
            $data['multilingual'] = true;
        }
    }

    private function prepareEditorEdit(&$data)
    {
        $data['editor'] = new EditorHelper();
        $data['editor']->content = $data['mail']->body;
        $data['editor']->autoSave = empty($data['mail']->autosave) ? '' : $data['mail']->autosave;
        $data['editor']->editor = empty($data['mail']->editor) ? '' : $data['mail']->editor;
        if (!empty($data['mail']->id)) $data['editor']->mailId = $data['mail']->id;
        if (!empty($data['mail']->type)) $data['editor']->automation = $data['isAutomationAdmin'];
        if (!empty($data['mail']->settings)) $data['editor']->settings = $data['mail']->settings;
        if (!empty($data['mail']->stylesheet)) $data['editor']->stylesheet = $data['mail']->stylesheet;

        $data['editor']->data = [
            'mail' => $data['mail'],
            'mailClass' => $this->currentClass,
        ];

        if (!empty($data['multilingual'])) {
            $data['editor']->data['tagClass'] = new TagClass();
            $data['editor']->data['main_language'] = $this->config->get('multilingual_default');
            $data['editor']->data['languages'] = explode(',', $this->config->get('multilingual_languages'));
            $data['editor']->data['multilingual'] = true;

            $allLanguages = acym_getLanguages();

            $mainLang = new \stdClass();
            $mainLang->code = $data['editor']->data['main_language'];
            $mainLang->name = empty($allLanguages[$data['editor']->data['main_language']]) ? $data['editor']->data['main_language']
                : $allLanguages[$data['editor']->data['main_language']]->name;
            $data['editor']->data['main_language'] = $mainLang;
            $mailClass = new MailClass();
            $mailId = empty($data['mail']->id) ? -1 : $data['mail']->id;
            $translations = $mailClass->getTranslationsById($mailId, true);
            foreach ($data['editor']->data['languages'] as $i => $oneLangCode) {
                $data['editor']->data['languages'][$i] = new \stdClass();
                $data['editor']->data['languages'][$i]->code = $oneLangCode;
                $data['editor']->data['languages'][$i]->name = empty($allLanguages[$oneLangCode]) ? $oneLangCode : $allLanguages[$oneLangCode]->name;
                $data['editor']->data['languages'][$i]->subject = empty($translations[$oneLangCode]->subject) ? '' : $translations[$oneLangCode]->subject;
                $data['editor']->data['languages'][$i]->preview = empty($translations[$oneLangCode]->preheader) ? '' : $translations[$oneLangCode]->preheader;
                $data['editor']->data['languages'][$i]->content = empty($translations[$oneLangCode]->body) ? '' : $translations[$oneLangCode]->body;
                $data['editor']->data['languages'][$i]->autosave = empty($translations[$oneLangCode]->autosave) ? '' : $translations[$oneLangCode]->autosave;
                $data['editor']->data['languages'][$i]->settings = empty($translations[$oneLangCode]->settings) ? '' : $translations[$oneLangCode]->settings;
                $data['editor']->data['languages'][$i]->stylesheet = empty($translations[$oneLangCode]->stylesheet) ? '' : $translations[$oneLangCode]->stylesheet;
            }
            $data['editor']->data['editor'] = new \stdClass();
            $data['editor']->data['editor']->editor = 'acyEditor';
        }

        if ($data['editor']->isDragAndDrop()) {
            $this->loadScripts['edit'][] = 'editor-wysid';
            $this->loadScripts['edit']['vue-applications'] = ['custom_view'];
        }
    }

    public function store($ajax = false)
    {
        acym_checkToken();

        $mailClass = $this->currentClass;
        $formData = acym_getVar('array', 'mail', []);
        $versions = acym_getVar('array', 'versions', [], 'REQUEST', ACYM_ALLOWRAW);
        $versionType = acym_getVar('string', 'version_type', '');
        $mail = new \stdClass();
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

        if (!empty($mail->id)) {
            if (!$mailClass->hasUserAccess($mail->id)) {
                die('Cannot save this mail');
            }
            $previousMail = $mailClass->getOneById($mail->id);
        }


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

        $saveAsTmpl = acym_getVar('int', 'saveAsTmpl', 0);
        if ($saveAsTmpl === 1) {
            unset($mail->id);
            $mail->type = $mailClass::TYPE_TEMPLATE;
        }

        if ($fromAutomation) {
            acym_setVar('from', $mail->id);
            acym_setVar('type', $mailClass::TYPE_AUTOMATION);
            acym_setVar('type_editor', 'acyEditor');
        }

        if (empty($mail->subject) && !empty($mail->type) && $mail->type != $mailClass::TYPE_TEMPLATE) {
            $mail->subject = acym_translation('ACYM_EMAIL_SUBJECT');
        }

        $inputNameBody = $saveAsTmpl ? 'editor_content_template' : 'editor_content';
        $inputNameSettings = $saveAsTmpl ? 'editor_settings_template' : 'editor_settings';
        $inputNameStylesheet = $saveAsTmpl ? 'editor_stylesheet_template' : 'editor_stylesheet';
        $inputNameColors = $saveAsTmpl ? 'main_colors_template' : 'main_colors';

        $mail->tags = acym_getVar('array', 'template_tags', []);
        $mail->body = acym_getVar('string', $inputNameBody, '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->settings = acym_getVar('string', $inputNameSettings, '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->stylesheet = acym_getVar('string', $inputNameStylesheet, '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->headers = acym_getVar('string', 'editor_headers', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->drag_editor = strpos($mail->body, 'acym__wysid__template') === false ? 0 : 1;

        $mail->thumbnail = '';
        if (!$fromAutomation) {
            $thumbnailName = acym_getVar('string', 'editor_thumbnail', '', 'REQUEST', ACYM_ALLOWRAW);

            if (preg_match('#^thumbnail_([0-9]*)\.png$#', $thumbnailName)) {
                $mail->thumbnail = $thumbnailName;
            }
        }

        if ($fromAutomation) $mail->type = $mailClass::TYPE_AUTOMATION;
        if (empty($mail->id)) {
            $mail->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
        }

        if (acym_getVar('bool', 'custom_thumbnail_reset', false)) {
            $mail->thumbnail = null;
        }

        $uploadedThumbnail = $this->setThumbnailFromInput();
        if ($uploadedThumbnail) {
            $mail->thumbnail = $uploadedThumbnail;
        }

        if (isset($previousMail) && !empty($previousMail->mail_settings)) {
            $mailSettings = json_decode($previousMail->mail_settings, false);
        } else {
            $mailSettings = new \stdClass();
        }
        $mailSettings->mainColors = acym_getVar('string', $inputNameColors, '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->mail_settings = json_encode($mailSettings);

        // Use the thumbnail of the source mail if not modified
        if (!empty($fromId) && empty($mail->thumbnail) && !$fromAutomation) {
            $thumbnail = $this->setThumbnailFrom($fromId);
            if (!empty($thumbnail)) $mail->thumbnail = $thumbnail;
        }

        if (empty($mail->name) && !in_array($mail->type, $mailClass::TYPES_NO_NAME) && empty($mail->id)) {
            $mail->name = empty($mail->subject) ? acym_translation('ACYM_TEMPLATE_NAME') : $mail->subject;
        }

        $this->setAttachmentToMail($mail);

        $mailID = $mailClass->save($mail);
        if (!empty($mailID)) {
            if (!empty($mail->type) && in_array($mail->type, [$mailClass::TYPE_WELCOME, $mailClass::TYPE_UNSUBSCRIBE])) {
                $listIds = acym_getVar('array', 'list_ids', []);
                $listClass = new ListClass();
                $listClass->setWelcomeUnsubEmail($listIds, $mailID, $mail->type);
            } elseif (!empty($mail->type) && $mail->type == $mailClass::TYPE_FOLLOWUP) {
                // Pass the new email ID in the return URL to ask user if we should add it to the queue
                acym_setVar('return', acym_getVar('string', 'return').'&newEmailId='.$mailID);

                $followupData = acym_getVar('array', 'followup', []);
                $followupClass = new FollowupClass();
                if (!$followupClass->saveDelaySettings($followupData, $mailID)) {
                    acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_SAVE_DELAY_SETTINGS'), 'error');
                }
                if (!empty($followupData['id'])) {
                    acym_setVar('followup_id', $followupData['id']);
                }
            }

            if (!$ajax) acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            if ($fromAutomation) {
                acym_setVar('type', $mailClass::TYPE_AUTOMATION);
                acym_setVar('type_editor', 'acyEditor');
            } else {
                acym_setVar('mailID', $mailID);
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

            return $mailID;
        } else {
            if (!$ajax) acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            if (!empty($mailClass->errors)) {
                if (!$ajax) acym_enqueueMessage($mailClass->errors, 'error');
            }

            return false;
        }
    }

    protected function setThumbnailFromInput()
    {
        $thumbnailFile = acym_getVar('array', 'custom_thumbnail', [], 'FILES');
        if (empty($thumbnailFile['name'])) {
            return false;
        }

        $extension = acym_fileGetExt($thumbnailFile['name']);

        $thumbNb = $this->config->get('numberThumbnail', 2);
        $filename = 'thumbnail_custom_'.($thumbNb + 1).'.'.$extension;
        $newConfig = new \stdClass();
        $newConfig->numberThumbnail = $thumbNb + 1;
        $this->config->save($newConfig);
        $thumbnailFile['name'] = $filename;

        ob_start();
        $uploaded = acym_importFile($thumbnailFile, ACYM_UPLOAD_FOLDER_THUMBNAIL, true);
        ob_end_clean();
        if ($uploaded) {
            return $uploaded;
        }

        acym_enqueueMessage(acym_translation('ACYM_UPLOADED_FILE_IS_NOT_IMAGE'), 'error');

        return false;
    }

    public function setAttachmentToMail(&$mail)
    {
        if (!empty($mail->id)) {
            $mail->attachments = $this->currentClass->getMailAttachments($mail->id);
        }

        if (!empty($mail->attachments) && !is_array($mail->attachments)) {
            $mail->attachments = json_decode($mail->attachments);
        } else {
            $mail->attachments = [];
        }

        // Attachments
        $newAttachments = [];
        $attachments = acym_getVar('array', 'attachments', []);
        if (!empty($attachments)) {
            foreach ($attachments as $filepath) {
                if (empty($filepath)) continue;

                $attachment = new \stdClass();
                $attachment->filename = $filepath;
                $attachment->size = filesize(ACYM_ROOT.$filepath);

                //We will never allow some files to be uploaded...
                if (preg_match('#\.(php.?|.?htm.?|pl|py|jsp|asp|sh|cgi)#Ui', $attachment->filename)) {
                    acym_enqueueMessage(
                        acym_translationSprintf(
                            'ACYM_ACCEPTED_TYPE',
                            substr($attachment->filename, strrpos($attachment->filename, '.') + 1),
                            $this->config->get('allowed_files')
                        ),
                        'notice'
                    );
                    continue;
                }

                if (in_array((array)$attachment, $mail->attachments)) continue;

                $newAttachments[] = $attachment;
            }
            // Add to previous attachments
            if (!empty($mail->attachments) && is_array($mail->attachments)) {
                $newAttachments = array_merge($mail->attachments, $newAttachments);
            }
            $mail->attachments = $newAttachments;
        }

        if (empty($mail->attachments)) {
            unset($mail->attachments);
        }

        if (!empty($mail->attachments) && !is_string($mail->attachments)) {
            $mail->attachments = json_encode($mail->attachments);
        }
    }

    protected function setThumbnailFrom($fromId)
    {
        $thumbNb = $this->config->get('numberThumbnail', 2);
        $fileName = 'thumbnail_'.($thumbNb + 1).'.png';
        $newConfig = new \stdClass();
        $newConfig->numberThumbnail = $thumbNb + 1;
        $this->config->save($newConfig);

        $mailClass = $this->currentClass;
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
        $mailId = $this->store();
        acym_setVar('id', $mailId);
        $this->edit();
    }

    public function save()
    {
        $mailid = $this->store();

        // When saving notifications, we return to page where we clicked the "Edit email" button
        $return = str_replace('{mailid}', empty($mailid) ? '' : $mailid, acym_getVar('string', 'return', ''));
        if (empty($return)) {
            $this->listing();
        } else {
            acym_redirect($return);
        }
    }

    public function autoSave()
    {
        $mailClass = $this->currentClass;
        $mail = new \stdClass();

        $language = acym_getVar('string', 'language', 'main');
        $mail->id = acym_getVar('int', 'mailId', 0);
        $mail->autosave = acym_getVar('string', 'autoSave', '', 'REQUEST', ACYM_ALLOWRAW);

        if (empty($mail->id) || !$mailClass->hasUserAccess($mail->id) || !$mailClass->autoSave($mail, $language)) {
            echo 'error';
        } else {
            echo 'saved';
        }

        exit;
    }

    public function ajaxCheckVideoUrl()
    {
        acym_checkToken();
        $videoUrl = acym_getVar('string', 'url', '');

        if (!acym_isValidUrl($videoUrl)) {
            acym_sendAjaxResponse('', [], false);
        }

        $image = '';
        $imageName = '';

        $youtubeMatch = '';
        $vimeoMatch = '';
        $dailymotionMatch = '';

        preg_match('/(?:https?:\/{2})?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)(?:\/watch\?v=|\/)([^\s&]+)/', $videoUrl, $youtubeMatch);
        preg_match('/^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/', $videoUrl, $vimeoMatch);
        preg_match('/^(?:(?:http|https):\/\/)?(?:www.)?(dailymotion\.com|dai\.ly)\/((video\/([^_]+))|(hub\/([^_]+)|([^\/_]+)))$/', $videoUrl, $dailymotionMatch);

        if (!empty($youtubeMatch)) {
            $image = 'https://img.youtube.com/vi/'.$youtubeMatch[1].'/0.jpg';
            $imageName = $youtubeMatch[1];
        } elseif (!empty($dailymotionMatch)) {
            $dailymotionImage = $dailymotionMatch[4] ?? $dailymotionMatch[2];

            $image = 'https://www.dailymotion.com/thumbnail/video/'.$dailymotionImage;
            $imageName = $dailymotionMatch[2];
        } elseif (!empty($vimeoMatch)) {
            $image = unserialize(file_get_contents('https://vimeo.com/api/v2/video/'.$vimeoMatch[5].'.php'));
            $image = $image[0]['thumbnail_large'];
            $imageName = $vimeoMatch[5];
        }

        if (empty($image) || !acym_isValidUrl($image)) {
            acym_sendAjaxResponse('', [], false);
        }

        acym_sendAjaxResponse('', ['new_image_name' => $this->saveVideoPreview($image, urlencode($imageName).'.jpg')]);
    }

    public function saveVideoPreview($image, $fileName): string
    {
        $imageVideo = imagecreatefromjpeg($image);
        $playButton = @imagecreatefrompng(ACYM_ROOT.ACYM_MEDIA_FOLDER.'images'.DS.'editor'.DS.'play_button.png');
        if ($playButton === false || $imageVideo === false) {
            return $image;
        }
        $imageWidth = imagesx($imageVideo);
        $imageHeight = imagesy($imageVideo);
        $logoWidth = imagesx($playButton);
        $logoHeight = imagesy($playButton);

        $left = round(($imageWidth - $logoWidth) / 2);
        $top = round(($imageHeight - $logoHeight) / 2);
        imagecopy($imageVideo, $playButton, $left, $top, 0, 0, $logoWidth, $logoHeight);
        imagepng($imageVideo, 'tmp.jpg', 9);

        $input = imagecreatefrompng('tmp.jpg');
        $output = imagecreatetruecolor($imageWidth, $imageHeight);
        $white = imagecolorallocate($output, 255, 255, 255);

        imagefilledrectangle($output, 0, 0, $imageWidth, $imageHeight, $white);
        imagecopy($output, $input, 0, 0, 0, 0, $imageWidth, $imageHeight);

        ob_start();
        $status = imagejpeg($output, null, 95);
        $imageContent = ob_get_clean();
        if ($status && acym_writeFile(ACYM_ROOT.ACYM_UPLOAD_FOLDER.$fileName, $imageContent)) {
            return ACYM_UPLOADS_URL.$fileName;
        }

        return '';
    }

    public function getTemplateAjax()
    {
        acym_checkToken();
        $pagination = new PaginationHelper();
        $id = acym_getVar('int', 'id');
        $id = empty($id) ? '' : '&id='.$id;
        $searchFilter = acym_getVar('string', 'search', '');
        $tagFilter = acym_getVar('string', 'tag', 0);
        $ordering = 'creation_date';
        $orderingSortOrder = 'DESC';
        $type = acym_getVar('string', 'type', 'custom');
        $automation = acym_getVar('boolean', 'automation', false);
        $returnUrl = acym_getVar('string', 'return');
        $returnUrl = empty($returnUrl) || 'undefined' == $returnUrl ? '' : '&return='.urlencode(base64_encode($returnUrl));
        $fromMultilingual = acym_getVar('int', 'is_multilingual_edition', 0);

        $mailsPerPage = $pagination->getListLimit();
        $page = acym_getVar('int', 'pagination_page_ajax', 1);
        $page != 'undefined' ? : $page = '1';

        $mailClass = new MailClass();
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
                'drag_editor' => !empty($fromMultilingual),
                'gettingTemplates' => true,
            ]
        );

        $return = '<div class="grid-x grid-padding-x grid-padding-y grid-margin-x grid-margin-y xxlarge-up-6 large-up-4 medium-up-3 small-up-1 cell acym__template__choose__list margin-top-1">';

        $followup_id = '';
        if ($type == $mailClass::TYPE_FOLLOWUP) {
            $followup_id = acym_getVar('int', 'followup_id', 0);
        }
        $listId = 0;
        if (in_array($type, [$mailClass::TYPE_WELCOME, $mailClass::TYPE_UNSUBSCRIBE])) {
            $listId = acym_getVar('int', 'list_id', 0);
        }
        foreach ($matchingMails['elements'] as $oneTemplate) {
            $return .= '<div class="cell grid-x acym__templates__oneTpl acym__listing__block" id="'.acym_escape($oneTemplate->id).'">
                <div class="cell acym__templates__pic text-center">';

            $url = acym_getVar('cmd', 'ctrl').'&task=edit&step=editEmail&from='.intval($oneTemplate->id).$returnUrl.'&type='.$type.$id.'&'.acym_getFormToken();
            if (!empty($followup_id)) {
                $url .= '&followup_id='.$followup_id;
            }
            if (!empty($listId)) {
                $url .= '&list_id='.$listId;
            }
            if (!empty($this->data['campaignInformation'])) {
                $url .= '&id='.intval($this->data['campaignInformation']);
            }
            if (!$automation || !empty($returnUrl)) {
                $return .= '<a href="'.acym_completeLink($url, false, false, true).'">';
            }

            $return .= '<img src="'.acym_escape(acym_getMailThumbnail($oneTemplate->thumbnail)).'" alt="template thumbnail"/>';
            if (!$automation || !empty($returnUrl)) {
                $return .= '</a>';
            }

            if ($oneTemplate->drag_editor) {
                $return .= '<div class="acym__templates__choose__ribbon acyeditor">'.acym_translation('ACYM_DD_EDITOR').'</div>';
            } else {
                $return .= '<div class="acym__templates__choose__ribbon htmleditor">'.acym_translation('ACYM_HTML_EDITOR').'</div>';
            }

            if (strlen($oneTemplate->name) > 55) {
                $oneTemplate->name = substr($oneTemplate->name, 0, 50).'...';
            }
            $return .= '</div>
                            <div class="cell grid-x acym__templates__footer text-center">
                                <div class="cell acym__templates__footer__title acym_text_ellipsis" title="'.acym_escape($oneTemplate->name).'">'.acym_escape($oneTemplate->name).'</div>
                                <div class="cell">'.acym_date($oneTemplate->creation_date, 'ACYM_DATE_FORMAT_LC3').'</div>
                            </div>
                        </div>';
        }

        $return .= '</div>';

        $pagination->setStatus($matchingMails['total'], $page, $mailsPerPage);

        $return .= $pagination->displayAjax();

        echo $return;
        exit;
    }

    public function setNewThumbnail()
    {
        if (!acym_isAdmin()) {
            die('Access denied for thumbnail creation');
        }

        acym_checkToken();
        $contentThumbnail = acym_getVar('string', 'content', '');
        if (strpos($contentThumbnail, 'data:image/png') !== 0) {
            acym_sendAjaxResponse('This file is not allowed.', [], false);
        }

        $mailId = acym_getVar('int', 'id', 0);
        if (!empty($mailId)) {
            $mailClass = new MailClass();
            $mail = $mailClass->getOneById($mailId);
            if (!empty($mail)) {
                $file = $mail->thumbnail;
            }
        }

        if (empty($file) || strpos($file, 'http') === 0) {
            $thumbNb = $this->config->get('numberThumbnail', 2);
            $file = 'thumbnail_'.($thumbNb + 1).'.png';
            $newConfig = new \stdClass();
            $newConfig->numberThumbnail = $thumbNb + 1;
            $this->config->save($newConfig);
        }

        $extension = acym_fileGetExt($file);
        if (strpos($file, 'thumbnail_') === false || !in_array($extension, ['png', 'jpeg', 'jpg', 'gif', 'webp'])) {
            acym_sendAjaxResponse('This file is not allowed.', [], false);
        }

        acym_createFolder(ACYM_UPLOAD_FOLDER_THUMBNAIL);
        file_put_contents(ACYM_UPLOAD_FOLDER_THUMBNAIL.$file, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $contentThumbnail)));

        acym_sendAjaxResponse('', ['fileName' => $file]);
    }

    public function setNewIconShare()
    {
        acym_checkToken();
        $socialName = acym_getVar('string', 'social', '');
        $socialMedias = acym_getSocialMedias();
        if (!in_array($socialName, $socialMedias)) {
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_UNKNOWN_SOCIAL', $socialName), [], false);
        }

        $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $newPath = ACYM_UPLOAD_FOLDER.'socials'.DS.$socialName;
        $newPathComplete = $newPath.'.'.$extension;

        $allowedExtensions = acym_getImageFileExtensions();
        if (!in_array($extension, $allowedExtensions)) {
            $errorMessage = acym_translationSprintf('ACYM_ACCEPTED_TYPE', $extension, implode(', ', $allowedExtensions));
        } elseif (!acym_uploadFile($_FILES['file']['tmp_name'], ACYM_ROOT.$newPathComplete)) {
            $errorMessage = acym_translationSprintf('ACYM_ERROR_UPLOADING_FILE_X', $newPathComplete);
        }

        if (!empty($errorMessage)) {
            acym_sendAjaxResponse($errorMessage, [], false);
        }

        $newConfig = new \stdClass();
        $newConfig->social_icons = json_decode($this->config->get('social_icons', '{}'), true);

        $newImg = acym_rootURI().$newPathComplete;
        $newImgWithoutExtension = acym_rootURI().$newPath;

        $newConfig->social_icons[$socialName] = $newImg;
        $newConfig->social_icons = json_encode($newConfig->social_icons);
        $this->config->save($newConfig);

        acym_sendAjaxResponse(
            acym_translation('ACYM_ICON_IMPORTED'),
            [
                'url' => $newImgWithoutExtension,
                'extension' => $extension,
            ]
        );
    }

    public function saveAjax()
    {
        $result = $this->store(true);
        if ($result) {
            acym_sendAjaxResponse('', ['result' => $result]);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_SAVING'), [], false);
        }
    }

    public function loadCSS()
    {
        header('Content-Type: text/css');
        $idMail = acym_getVar('int', 'id', 0);
        if (empty($idMail)) {
            exit;
        }

        $mailClass = $this->currentClass;
        $mail = $mailClass->getOneById($idMail);

        echo $mailClass->buildCSS($mail->stylesheet);
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

        $mailClass = $this->currentClass;
        $mail = $mailClass->getOneById($mailId);

        if (empty($mail)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), 'error');
            $this->edit();

            return;
        }

        $mailerHelper = new MailerHelper();
        $mailerHelper->autoAddUser = true;
        $mailerHelper->report = false;

        $currentEmail = acym_currentUserEmail();
        if ($mailerHelper->sendOne($mailId, $currentEmail, true)) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_SEND_SUCCESS', $mail->name, $currentEmail), 'info');
        } else {
            acym_enqueueMessage(acym_translationSprintf('ACYM_SEND_ERROR', $mail->name, $currentEmail), 'error');
        }

        $this->edit();
    }

    public function sendTest()
    {
        acym_checkToken();
        $controller = acym_getVar('string', 'controller', 'mails');
        $level = 'info';

        $testNote = acym_getVar('string', 'test_note', '');

        if (in_array($controller, ['mails', 'frontmails'])) {
            $mailId = acym_getVar('int', 'id', 0);
        } else {
            $campaignId = acym_getVar('int', 'id', 0);
            $campaignClass = new CampaignClass();
            $campaign = $campaignClass->getOneById($campaignId);
            if (empty($campaign)) {
                acym_sendAjaxResponse('', ['level' => 'error', 'message' => acym_translation('ACYM_CAMPAIGN_NOT_FOUND')], false);
            }
            if (!$campaignClass->hasUserAccess($campaignId)) {
                die('A test of this campaign cannot be sent');
            }

            $mailId = $campaign->mail_id;

            $languageVersion = acym_getVar('string', 'lang_version', 'main');
            if (!empty($languageVersion) && $languageVersion !== 'main') {
                $translationId = $this->currentClass->getTranslationId($mailId, $languageVersion);
                if (!empty($translationId)) $mailId = $translationId;
            }
        }

        $mailClass = $this->currentClass;
        $mail = $mailClass->getOneById($mailId);

        if (empty($mail) || !$mailClass->hasUserAccess($mailId)) {
            acym_sendAjaxResponse('', ['level' => 'error', 'message' => acym_translation('ACYM_EMAIL_NOT_FOUND')], false);
        }

        $mailerHelper = new MailerHelper();
        $mailerHelper->autoAddUser = true;
        $mailerHelper->report = false;


        $report = [];

        $testEmails = explode(',', acym_getVar('string', 'test_emails'));
        foreach ($testEmails as $oneAddress) {
            if (!$mailerHelper->sendOne($mail->id, $oneAddress, true, $testNote)) {
                $level = 'error';
            }

            if (!empty($mailerHelper->reportMessage)) {
                $report[] = $mailerHelper->reportMessage;
            }
        }

        acym_sendAjaxResponse('', ['level' => $level, 'message' => implode('<br/>', $report)], $level !== 'error');
    }

    public function getMailContent()
    {
        $mailClass = $this->currentClass;
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

    protected function setFrontEndParamsForTemplateChoose()
    {
        return '';
    }

    public function saveAsTmplAjax()
    {
        $isWellSaved = $this->store(true);
        acym_sendAjaxResponse($isWellSaved ? '' : acym_translation('ACYM_ERROR_SAVING'), ['result' => $isWellSaved], $isWellSaved);
    }
}
