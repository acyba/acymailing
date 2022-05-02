<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\PluginHelper;
use AcyMailing\Libraries\acymClass;

class MailClass extends acymClass
{
    var $table = 'mail';
    var $pkey = 'id';
    var $templateNames = [];

    const FIELDS_ENCODING = ['name', 'subject', 'body', 'autosave', 'preheader'];

    const TYPE_STANDARD = 'standard';
    const TYPE_NOTIFICATION = 'notification';
    const TYPE_OVERRIDE = 'override';
    const TYPE_WELCOME = 'welcome';
    const TYPE_UNSUBSCRIBE = 'unsubscribe';
    const TYPE_AUTOMATION = 'automation';
    const TYPE_FOLLOWUP = 'followup';
    const TYPE_TEMPLATE = 'template';

    // Used by some sending methods to know the priority of a sent email (transactional => reset password / account confirmation...)
    const TYPES_TRANSACTIONAL = [
        self::TYPE_NOTIFICATION,
        self::TYPE_OVERRIDE,
        self::TYPE_WELCOME,
        self::TYPE_UNSUBSCRIBE,
    ];

    // Types on which the click statistics are active
    const TYPES_WITH_STATS = [
        self::TYPE_STANDARD,
        self::TYPE_AUTOMATION,
        self::TYPE_WELCOME,
        self::TYPE_UNSUBSCRIBE,
        self::TYPE_FOLLOWUP,
    ];

    // Types that don't let the user modify the name
    const TYPES_NO_NAME = [
        self::TYPE_NOTIFICATION,
        self::TYPE_OVERRIDE,
    ];

    /**
     * Get mails depending on filters (search, ordering, pagination)
     *
     * @param $settings
     *
     * @return mixed
     */
    public function getMatchingElements($settings = [])
    {
        $query = 'SELECT mail.* FROM #__acym_mail AS mail';
        $queryCount = 'SELECT COUNT(mail.id) FROM #__acym_mail AS mail';

        $filters = [];
        $tagJoin = '';

        // Tag filter
        if (!empty($settings['tag'])) {
            $tagJoin = ' JOIN #__acym_tag AS tag ON mail.id = tag.id_element ';
            $filters[] = 'tag.name = '.acym_escapeDB($settings['tag']);
            $filters[] = 'tag.type = "mail"';
        }
        $query .= $tagJoin;
        $queryCount .= $tagJoin;

        if (!empty($settings['search'])) {
            $filters[] = 'mail.name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if (!empty($settings['editor'])) {
            if ($settings['editor'] == 'html') {
                $filters[] .= 'mail.drag_editor = 0';
            } else {
                $filters[] .= 'mail.drag_editor = 1';
            }
        }

        if (!empty($settings['automation']) || empty($settings['onlyStandard'])) {
            $filters[] = 'mail.type != '.acym_escapeDB($this::TYPE_NOTIFICATION);
            $filters[] = 'mail.type != '.acym_escapeDB($this::TYPE_OVERRIDE);
        } else {
            $filters[] = 'mail.type IN ('.acym_escapeDB($this::TYPE_STANDARD).', '.acym_escapeDB($this::TYPE_TEMPLATE).')';
        }

        $filters[] = 'mail.parent_id IS NULL';

        if (empty($settings['automation'])) {
            $filters[] = 'mail.type IN ('.acym_escapeDB($this::TYPE_TEMPLATE).', '.acym_escapeDB($this::TYPE_WELCOME).', '.acym_escapeDB($this::TYPE_UNSUBSCRIBE).')';
        }

        if (!empty($settings['drag_editor'])) {
            $filters[] = 'mail.drag_editor = 1';
        }

        if (!empty($settings['creator_id'])) {
            $mailTypeCondition = 'mail.type IN ('.acym_escapeDB($this::TYPE_TEMPLATE).', '.acym_escapeDB($this::TYPE_WELCOME).', '.acym_escapeDB($this::TYPE_UNSUBSCRIBE).')';
            $userGroups = acym_getGroupsByUser($settings['creator_id']);
            $groupCondition = '(mail.access LIKE "%,'.implode(',%" OR mail.access LIKE "%,', $userGroups).',%")';
            $filter = 'mail.creator_id = '.intval($settings['creator_id']).' OR ('.$mailTypeCondition.' AND '.$groupCondition.')';

            if (!acym_isAdmin() && !empty($settings['element_tab'])) {
                $filter = '('.$filter.') 
                            OR list.cms_user_id = '.intval($settings['creator_id']).' 
                            OR (list.access LIKE "%,'.implode(',%" OR list.access LIKE "%,', $userGroups).',%")';
            }

            $filters['list'] = '('.$filter.')';
        }

        if (!empty($settings['element_tab'])) {
            $statJoin = ' LEFT JOIN #__acym_mail_stat AS mail_stat ON mail.id = mail_stat.mail_id ';
            $listJoin = acym_isAdmin() ? '' : ' LEFT JOIN #__acym_list AS list ON list.'.acym_escape($settings['element_tab']).'_id = mail.id';
            $query = 'SELECT DISTINCT mail.*, mail_stat.sent as subscribers, mail_stat.open_unique FROM #__acym_mail AS mail'.$statJoin.$tagJoin.$listJoin;
            $filters[] = 'mail.type = '.acym_escapeDB($settings['element_tab']);
        }

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            if (!acym_isAdmin()) unset($filters['list']);
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $query .= ' ORDER BY mail.'.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $results['elements'] = $this->decode(acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']));
        $results['total'] = acym_loadResult($queryCount);

        if (!empty($settings['element_tab'])) {
            $this->getAllListIdsForWelcomeUnsub($results['elements'], $settings['element_tab']);
        }

        $results['status'] = [];

        if (!empty($settings['element_tab'])) {
            $urlClickClass = new UrlClickClass();
            for ($i = 0 ; $i < count($results['elements']) ; $i++) {
                $results['elements'][$i]->open = 0;
                if (!empty($results['elements'][$i]->subscribers)) {
                    $results['elements'][$i]->open = number_format($results['elements'][$i]->open_unique / $results['elements'][$i]->subscribers * 100, 2);

                    $clicksNb = $urlClickClass->getNumberUsersClicked($results['elements'][$i]->id);
                    $results['elements'][$i]->click = number_format($clicksNb / $results['elements'][$i]->subscribers * 100, 2);
                }
            }
        }

        return $results;
    }

    private function getAllListIdsForWelcomeUnsub(&$elements, $type)
    {
        if (empty($elements)) return true;
        $column = $type == $this::TYPE_WELCOME ? 'welcome_id' : 'unsubscribe_id';

        foreach ($elements as $key => $element) {
            $elements[$key]->lists = acym_loadObjectList('SELECT color, name FROM #__acym_list WHERE '.$column.' = '.intval($element->id));
        }

        return true;
    }

    /**
     * @return array
     */
    public function getAll($key = null)
    {
        $allMails = parent::getAll($key);

        return $this->decode($allMails);
    }

    /**
     * @param int     $id
     * @param boolean $needTranslatedSettings
     *
     * @return object
     */
    public function getOneById($id, $needTranslatedSettings = false)
    {
        $mail = $this->decode(acym_loadObject('SELECT * FROM #__acym_mail WHERE id = '.intval($id)));

        if (!empty($mail)) {
            $tagsClass = new TagClass();
            $mail->tags = $tagsClass->getAllTagsByElementId('mail', $id);
        }

        if (isset($mail->access) && !is_array($mail->access)) $mail->access = explode(',', $mail->access);

        if (!empty($mail->parent_id) && $needTranslatedSettings) {
            $this->getTranslatedSettingsMail($mail);
        }

        return $mail;
    }

    private function getTranslatedSettingsMail(&$mail)
    {
        $parentMailTranslation = acym_loadResult('SELECT translation FROM #__acym_mail WHERE id = '.intval($mail->parent_id));
        $parentMailTranslation = empty($parentMailTranslation) ? [] : json_decode($parentMailTranslation, true);

        $translationConfig = $this->config->get('sender_info_translation', '');
        $translationConfig = empty($translationConfig) ? [] : json_decode($translationConfig, true);

        if (!empty($parentMailTranslation[$mail->language])) {
            $senderTranslation = $parentMailTranslation[$mail->language];
        } elseif (!empty($translationConfig[$mail->language])) {
            $senderTranslation = $translationConfig[$mail->language];
            $senderTranslation['reply_to_email'] = $senderTranslation['replyto_email'];
            $senderTranslation['reply_to_name'] = $senderTranslation['replyto_name'];
        } else {
            return $mail;
        }

        $mail->from_name = $senderTranslation['from_name'];
        $mail->from_email = $senderTranslation['from_email'];
        $mail->reply_to_name = $senderTranslation['reply_to_name'];
        $mail->reply_to_email = $senderTranslation['reply_to_email'];

        return $mail;
    }

    /**
     * @param string  $name
     * @param boolean $needTranslatedSettings
     * @param string  $onlyType
     *
     * @return object
     */
    public function getOneByName($name, $needTranslatedSettings = false, $onlyType = null)
    {
        $query = 'SELECT * FROM #__acym_mail WHERE `parent_id` IS NULL AND `name` = '.acym_escapeDB(utf8_encode($name));
        if (!empty($onlyType)) $query .= ' AND `type` = '.acym_escapeDB($onlyType);

        $mail = $this->decode(acym_loadObject($query));

        if (!empty($mail)) {
            $tagsClass = new TagClass();
            $mail->tags = $tagsClass->getAllTagsByElementId('mail', $mail->id);
        }

        if (!empty($mail->parent_id) && $needTranslatedSettings) {
            $this->getTranslatedSettingsMail($mail);
        }

        return $mail;
    }

    /**
     * Get mails depending on their type (standard, welcome, unsubscribe, notification)
     *
     * @param $typeMail
     * @param $settings
     *
     * @return array
     */
    public function getMailsByType($typeMail, $settings)
    {
        if (empty($settings['key'])) {
            $settings['key'] = '';
        }
        if (empty($settings['offset'])) {
            $settings['offset'] = 0;
        }
        if (empty($settings['mailsPerPage'])) {
            $settings['mailsPerPage'] = 12;
        }

        $query = 'SELECT * FROM #__acym_mail AS mail';
        $queryCount = 'SELECT count(*) FROM #__acym_mail AS mail';

        // Mail type filtering
        $filters = [];
        $filters[] = 'mail.type = '.acym_escapeDB($typeMail);

        // Search filter
        if (!empty($settings['search'])) {
            $filters[] = 'mail.name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        $query .= ' WHERE ('.implode(') AND (', $filters).')';
        $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';

        $query .= ' ORDER BY id DESC';

        $results['mails'] = $this->decode(acym_loadObjectList($query, $settings['key'], $settings['offset'], $settings['mailsPerPage']));
        $results['total'] = acym_loadResult($queryCount);

        return $results;
    }

    public function getAllListsWithCountSubscribersByMailIds($ids)
    {
        acym_arrayToInteger($ids);
        if (empty($ids)) {
            return [];
        }

        $query = 'SELECT mailLists.list_id, mailLists.mail_id, list.*, COUNT(userLists.user_id) AS subscribers 
                    FROM #__acym_mail_has_list AS mailLists 
                    JOIN #__acym_list AS list ON mailLists.list_id = list.id
                    LEFT JOIN #__acym_user_has_list AS userLists 
                        JOIN #__acym_user AS acyuser ON userLists.user_id = acyuser.id
                        AND userLists.status = 1
                        AND acyuser.active = 1 ';

        if ($this->config->get('require_confirmation', 1) == 1) {
            $query .= ' AND acyuser.confirmed = 1 ';
        }

        $query .= 'ON list.id = userLists.list_id    
                    WHERE mailLists.mail_id IN ('.implode(',', $ids).')
                    GROUP BY mailLists.list_id, mailLists.mail_id';

        //This line if for guys with big database to not break the page
        acym_query('SET SQL_BIG_SELECTS=1');

        return acym_loadObjectList($query);
    }

    public function getAllListsByMailIds($ids)
    {
        acym_arrayToInteger($ids);
        if (empty($ids)) {
            return [];
        }

        $query = 'SELECT mailLists.mail_id, list.name, list.color
                    FROM #__acym_mail_has_list AS mailLists 
                    JOIN #__acym_list AS list ON mailLists.list_id = list.id
                    WHERE mailLists.mail_id IN ('.implode(',', $ids).')';

        return acym_loadObjectList($query);
    }

    public function getAllListsByMailId($id)
    {
        //If we are from the stats and we have an array we take the first email because it's the main email from automatic campaign
        if (is_array($id)) $id = $id[0];

        $mail = $this->getOneById($id);
        if (empty($mail)) return [];

        if ($this::TYPE_WELCOME === $mail->type) {
            $query = 'SELECT * FROM #__acym_list WHERE welcome_id = '.intval($id);
        } elseif ($this::TYPE_FOLLOWUP === $mail->type) {
            $query = 'SELECT list.* FROM #__acym_followup_has_mail AS followup_mail
                      JOIN #__acym_followup AS followup ON followup.id = followup_mail.followup_id AND followup_mail.mail_id = '.intval($id).'
                      JOIN #__acym_list AS list ON list.id = followup.list_id';
        } else {
            $query = 'SELECT list.*
                    FROM #__acym_mail_has_list AS mailLists
                    JOIN #__acym_list AS list ON mailLists.list_id = list.id
                    WHERE mailLists.mail_id = '.intval($id).'
                    GROUP BY mailLists.list_id, mailLists.mail_id';
        }

        return acym_loadObjectList($query, 'id');
    }

    public function getAllListsByMailIdAndUserId($mailId, $userId)
    {
        if (empty($mailId) || empty($userId)) return [];

        $query = 'SELECT list.* FROM #__acym_list AS list';
        $query .= ' JOIN #__acym_user_has_list AS userlist ON list.id = userlist.list_id AND userlist.user_id = '.intval($userId);
        $query .= ' JOIN #__acym_mail_has_list AS maillist ON maillist.list_id = list.id AND maillist.mail_id = '.intval($mailId);

        return acym_loadObjectList($query);
    }

    public function save($mailToSave)
    {
        $mail = clone $mailToSave;

        if (isset($mail->tags)) {
            $tags = $mail->tags;
            unset($mail->tags);
        }

        if (empty($mail->id)) {
            $mail->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
            if (empty($mail->creator_id)) $mail->creator_id = acym_currentUserId();
        }

        $mail = $this->encode($mail);

        // Clean autosave value
        $mail->autosave = null;

        if (empty($mail->thumbnail) || strpos($mail->thumbnail, 'data:image/png;base64') !== false) unset($mail->thumbnail);
        if (!isset($mail->access)) $mail->access = '';

        foreach ($mail as $oneAttribute => $value) {
            if (empty($value) || in_array($oneAttribute, ['thumbnail', 'settings'])) {
                continue;
            }

            if ($oneAttribute === 'access' && is_array($mail->$oneAttribute)) {
                $value = ','.trim(implode(',', $mail->$oneAttribute), ',').',';
                $mail->$oneAttribute = $value;
            }

            if (is_array($value)) $mail->$oneAttribute = json_encode($value);

            if (in_array($oneAttribute, ['body', 'headers'])) {
                $mail->$oneAttribute = preg_replace('#<input[^>]*value="[^"]*"[^>]*>#Uis', '', $mail->$oneAttribute);

                //Remove tinyMce content edit
                $mail->$oneAttribute = str_replace(' contenteditable="true"', '', $mail->$oneAttribute);
            } else {
                $mail->$oneAttribute = strip_tags($mail->$oneAttribute);
            }
        }

        $mailID = parent::save($mail);

        if (!empty($mailID) && isset($tags)) {
            $tagClass = new TagClass();
            $tagClass->setTags('mail', $mailID, $tags);
        }

        return $mailID;
    }

    public function autoSave($mail, $language = 'main')
    {
        if (empty($mail->id)) return false;
        $mail->autosave = str_replace(' contenteditable="true"', '', $mail->autosave);

        if (acym_isMultilingual() && $language !== 'main') {
            $translationId = $this->getTranslationId($mail->id, $language);
            if (empty($translationId)) {
                $parentCopy = $this->getOneById($mail->id);
                if (empty($parentCopy)) return false;

                unset($parentCopy->id);
                $parentCopy->parent_id = $mail->id;
                $parentCopy->language = $language;
                $translationId = $this->save($parentCopy);
            }
            $mail->id = $translationId;
        }

        $mail = $this->encode($mail);

        return parent::save($mail);
    }

    public function delete($elements)
    {
        if (empty($elements)) return 0;
        if (!is_array($elements)) $elements = [$elements];

        $this->deleteMediaFolder($elements);
        acym_arrayToInteger($elements);

        $allThumbnailToDelete = acym_loadResultArray('SELECT DISTINCT thumbnail FROM #__acym_mail WHERE id IN ('.implode(',', $elements).')');

        $translations = acym_loadResultArray('SELECT id FROM #__acym_mail WHERE parent_id IN ('.implode(',', $elements).')');
        $elements = array_merge($elements, $translations);
        if (!empty($translations)) {
            acym_query('UPDATE #__acym_mail SET `parent_id` = null WHERE `id` IN ('.implode(',', $translations).')');
        }

        acym_query('UPDATE #__acym_list SET welcome_id = null WHERE welcome_id IN ('.implode(',', $elements).')');
        acym_query('UPDATE #__acym_list SET unsubscribe_id = null WHERE unsubscribe_id IN ('.implode(',', $elements).')');
        acym_query('DELETE FROM #__acym_queue WHERE mail_id IN ('.implode(',', $elements).')');
        acym_query('DELETE FROM #__acym_mail_has_list WHERE mail_id IN ('.implode(',', $elements).')');
        acym_query('DELETE FROM #__acym_tag WHERE `type` = "mail" AND `id_element` IN ('.implode(',', $elements).')');
        acym_query('DELETE FROM #__acym_user_stat WHERE mail_id IN ('.implode(',', $elements).')');
        acym_query('DELETE FROM #__acym_url_click WHERE mail_id IN ('.implode(',', $elements).')');
        acym_query('DELETE FROM #__acym_mail_stat WHERE mail_id IN ('.implode(',', $elements).')');
        acym_query('DELETE FROM #__acym_followup_has_mail WHERE mail_id IN ('.implode(',', $elements).')');

        $return = parent::delete($elements);

        $this->deleteUnusedThumbnails($allThumbnailToDelete);

        return $return;
    }

    public function deleteMediaFolder($elements)
    {
        if (empty($elements)) return;

        acym_arrayToInteger($elements);
        $results = acym_loadResultArray('SELECT mail_settings FROM #__acym_mail WHERE mail_settings IS NOT NULL AND id IN ('.implode(',', $elements).')');

        foreach ($results as $template) {
            $settings = json_decode($template, true);

            if (empty($settings['media_folder']) || !file_exists(ACYM_TEMPLATE.$settings['media_folder'])) continue;
            acym_deleteFolder(ACYM_TEMPLATE.$settings['media_folder']);
        }
    }

    public function deleteUnusedThumbnails($thumbnails)
    {
        if (empty($thumbnails)) return;

        if (!is_array($thumbnails)) $thumbnails = [$thumbnails];
        foreach ($thumbnails as $key => $oneThumb) {
            $thumbnails[$key] = acym_escapeDB($oneThumb);
        }

        $stillUsedThumbnails = acym_loadResultArray('SELECT thumbnail FROM #__acym_mail WHERE thumbnail IN ('.implode(',', $thumbnails).')');
        $thumbnailToDelete = array_diff($thumbnails, $stillUsedThumbnails);
        foreach ($thumbnailToDelete as $one) {
            if (!empty($one) && file_exists(ACYM_UPLOAD_FOLDER_THUMBNAIL.$one)) {
                unlink(ACYM_UPLOAD_FOLDER_THUMBNAIL.$one);
            }
        }
    }

    // Delete one attachment from a newsletter
    public function deleteOneAttachment($mailid, $idAttachment)
    {
        $mailid = intval($mailid);
        if (empty($mailid)) {
            return false;
        }
        $mail = $this->getOneById($mailid);

        $attachments = $mail->attachments;
        if (empty($attachments)) {
            return false;
        }
        $decodedAttach = json_decode($attachments, true);
        unset($decodedAttach[$idAttachment]);
        $newAttachments = [];
        if (!empty($decodedAttach)) {
            foreach ($decodedAttach as $oneAttach) {
                $newAttachments[] = $oneAttach;
            }
        }
        $attachdb = json_encode($newAttachments);

        return acym_query('UPDATE #__acym_mail SET attachments = '.acym_escapeDB($attachdb).' WHERE id = '.intval($mailid).' LIMIT 1');
    }

    public function createTemplateFile($id)
    {
        if (empty($id)) {
            return '';
        }
        $cssfile = ACYM_TEMPLATE.'css'.DS.'template_'.$id.'.css';

        $template = $this->getOneById($id);
        if (empty($template->id)) {
            return '';
        }
        $css = $this->buildCSS($template->stylesheet);

        if (empty($css)) {
            return '';
        }

        acym_createDir(ACYM_TEMPLATE.'css');

        if (acym_writeFile($cssfile, $css)) {
            return $cssfile;
        } else {
            acym_enqueueMessage('Could not create the file '.$cssfile, 'error');

            return '';
        }
    }

    public function buildCSS($stylesheet)
    {
        $inline = '';

        if (preg_match_all('#@import[^;]*;#is', $stylesheet, $results)) {
            foreach ($results[0] as $oneResult) {
                //We add the @import CSS at the very beginning for the CSS stylesheet otherwise it does not work
                $inline .= trim($oneResult)."\n";
                //We also remove it from the stylesheet to avoid having a duplicate.
                $stylesheet = str_replace($oneResult, '', $stylesheet);
            }
        }

        $inline .= $stylesheet;

        return $inline;
    }

    public function doupload()
    {
        $zipFilePath = $this->uploadTemplate();
        if (empty($zipFilePath)) return false;

        $templateFolder = $this->extractTemplate($zipFilePath);
        if (empty($templateFolder)) return false;

        if (!$this->installExtractedTemplate($templateFolder)) return false;

        return true;
    }

    private function uploadTemplate()
    {
        $importFile = acym_getVar('none', 'uploadedfile', '', 'files');

        $fileError = $importFile['error'];
        if ($fileError > 0) {
            switch ($fileError) {
                case 1:
                    acym_enqueueMessage(acym_translation('ACYM_FILE_UPLOAD_ERROR_1'), 'error');

                    return false;
                case 2:
                    acym_enqueueMessage(acym_translation('ACYM_FILE_UPLOAD_ERROR_2'), 'error');

                    return false;
                case 3:
                    acym_enqueueMessage(acym_translation('ACYM_FILE_UPLOAD_ERROR_3'), 'error');

                    return false;
                case 4:
                    acym_enqueueMessage(acym_translation('ACYM_FILE_UPLOAD_ERROR_4'), 'error');

                    return false;
                default:
                    acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_5', $fileError), 'error');

                    return false;
            }
        }
        if (empty($importFile['name'])) {
            acym_enqueueMessage(acym_translation('ACYM_BROWSE_FILE'), 'error');

            return false;
        }

        $uploadPath = acym_cleanPath(ACYM_ROOT.ACYM_MEDIA_FOLDER.DS.'templates');

        if (!is_writable($uploadPath)) {
            @chmod($uploadPath, '0755');
            if (!is_writable($uploadPath)) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_WRITABLE_FOLDER', $uploadPath), 'warning');
            }
        }

        if (!(bool)ini_get('file_uploads')) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_6'), 'error');

            return false;
        }

        if (!extension_loaded('zlib')) {
            acym_raiseError(500, acym_translation('ACYM_MISSING_ZLIB'));

            return false;
        }

        $filename = strtolower(acym_makeSafeFile($importFile['name']));
        $extension = strtolower(substr($filename, strrpos($filename, '.') + 1));

        if (!in_array($extension, ['zip', 'tar.gz'])) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_ACCEPTED_TYPE', $extension, 'zip,tar.gz'), 'error');

            return false;
        }

        $tmpPath = acym_getCMSConfig('tmp_path', ACYM_MEDIA.'tmp'.DS);
        $zipFilePath = acym_cleanPath($tmpPath.DS.$filename);
        $tmp_src = $importFile['tmp_name'];

        $uploaded = acym_uploadFile($tmp_src, $zipFilePath);
        if (!$uploaded) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_7', $tmp_src, $zipFilePath), 'error');

            return false;
        }

        return $zipFilePath;
    }

    public function extractTemplate($zipFilePath, $deleteZip = true)
    {
        $extractdir = acym_cleanPath(dirname($zipFilePath).DS.uniqid().'_template');

        $result = acym_extractArchive($zipFilePath, $extractdir);

        if (!$result) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_9', $zipFilePath, $extractdir), 'error');

            return false;
        }

        if ($deleteZip) acym_deleteFile($zipFilePath);

        $allFiles = acym_getFiles($extractdir, '.', true, true, [], []);
        foreach ($allFiles as $oneFile) {
            if (preg_match('#\.(jpg|gif|png|jpeg|ico|bmp|html|htm|css)$#i', $oneFile)) {
                continue;
            }
            if (acym_deleteFile($oneFile)) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_8', $oneFile), 'warning');
            }
        }

        return $extractdir;
    }

    public function installExtractedTemplate($templateFolder)
    {
        if ($this->detecttemplates($templateFolder)) {
            $messages = $this->templateNames;
            array_unshift($messages, acym_translationSprintf('ACYM_TEMPLATES_INSTALL', count($this->templateNames)));
            acym_enqueueMessage($messages);
            if (is_dir($templateFolder)) acym_deleteFolder($templateFolder);

            return true;
        }

        acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_10'), 'error');
        if (is_dir($templateFolder)) acym_deleteFolder($templateFolder);

        return false;
    }

    public function detecttemplates($folder)
    {
        $allFiles = acym_getFiles($folder);
        if (!empty($allFiles)) {
            foreach ($allFiles as $oneFile) {
                if (preg_match('#^.*(html|htm)$#i', $oneFile)) {
                    if ($this->_installtemplate($folder.DS.$oneFile)) return true;
                }
            }
        }

        $status = false;
        $allFolders = acym_getFolders($folder);
        if (!empty($allFolders)) {
            foreach ($allFolders as $oneFolder) {
                $status = $this->detecttemplates($folder.DS.$oneFolder) || $status;
            }
        }

        return $status;
    }

    private function _installtemplate($filepath)
    {
        $fileContent = acym_fileGetContent($filepath);

        $newTemplate = new \stdClass();
        $newTemplate->name = trim(preg_replace('#[^a-z0-9]#i', ' ', substr(dirname($filepath), strpos($filepath, '_template'))));
        if (preg_match('#< *title[^>]*>(.*)< */ *title *>#Uis', $fileContent, $results) && !empty($results[1])) $newTemplate->name = $results[1];

        if (preg_match('#< *meta *name="fromname" *content="([^"]*)"#Uis', $fileContent, $results) && !empty($results[1])) $newTemplate->fromname = $results[1];
        if (preg_match('#< *meta *name="fromemail" *content="([^"]*)"#Uis', $fileContent, $results) && !empty($results[1])) $newTemplate->fromemail = $results[1];
        if (preg_match('#< *meta *name="replyname" *content="([^"]*)"#Uis', $fileContent, $results) && !empty($results[1])) $newTemplate->replyname = $results[1];
        if (preg_match('#< *meta *name="replyemail" *content="([^"]*)"#Uis', $fileContent, $results) && !empty($results[1])) $newTemplate->replyemail = $results[1];
        if (preg_match('#< *meta *name="subject" *content="([^"]*)"#Uis', $fileContent, $results) && !empty($results[1])) $newTemplate->subject = $results[1];
        if (preg_match('#< *meta *name="settings" *content="([^"]*)"#Uis', $fileContent, $results) && !empty($results[1])) {
            $newTemplate->settings = htmlspecialchars_decode($results[1]);
        }

        $newFolder = preg_replace('#[^a-z0-9]#i', '_', strtolower($newTemplate->name));
        $newTemplateFolder = $newFolder;
        $i = 1;
        while (is_dir(ACYM_TEMPLATE.$newTemplateFolder)) {
            $newTemplateFolder = $newFolder.'_'.$i;
            $i++;
        }
        $newTemplate->mail_settings = ['media_folder' => $newTemplateFolder];

        $moveResult = acym_copyFolder(dirname($filepath), ACYM_TEMPLATE.$newTemplateFolder);
        if ($moveResult !== true) {
            acym_display([acym_translationSprintf('ACYM_ERROR_COPYING_FOLDER_TO', dirname($filepath), ACYM_TEMPLATE.$newTemplateFolder), $moveResult], 'error');

            return false;
        }

        if (!file_exists(ACYM_TEMPLATE.$newTemplateFolder.DS.'index.html')) {
            $indexFile = '<html><body bgcolor="#FFFFFF"></body></html>';
            acym_writeFile(ACYM_TEMPLATE.$newTemplateFolder.DS.'index.html', $indexFile);
        }

        $fileContent = str_replace(
            [
                'src="./',
                'src="../',
                'src="images/',
                'url("images/',
                'url(&quot;images/',
            ],
            [
                'src="'.ACYM_TEMPLATE_URL.$newTemplateFolder.'/',
                'src="'.ACYM_TEMPLATE_URL,
                'src="'.ACYM_TEMPLATE_URL.$newTemplateFolder.'/images/',
                'url("'.ACYM_TEMPLATE_URL.$newTemplateFolder.'/images/',
                'url(&quot;'.ACYM_TEMPLATE_URL.$newTemplateFolder.'/images/',
            ],
            $fileContent
        );

        $fileContent = preg_replace('#(src|background)[ ]*=[ ]*\"(?!(https?://|/))(?:\.\./|\./)?#', '$1="'.ACYM_TEMPLATE_URL.$newTemplateFolder.'/', $fileContent);

        if (preg_match('#< *body[^>]*>(.*)< */ *body *>#Uis', $fileContent, $results)) {
            $newTemplate->body = $results[1];
        } else {
            $newTemplate->body = $fileContent;
        }

        $newTemplate->stylesheet = '';
        if (preg_match_all('#< *style[^>]*>(.*)< */ *style *>#Uis', $fileContent, $results)) {
            $newTemplate->stylesheet .= preg_replace('#(<!--|-->)#s', '', implode("\n", $results[1]));
        }
        $cssFiles = [];
        $cssFiles[ACYM_TEMPLATE.$newTemplateFolder] = acym_getFiles(ACYM_TEMPLATE.$newTemplateFolder, '\.css$');
        $subFolders = acym_getFolders(ACYM_TEMPLATE.$newTemplateFolder);
        foreach ($subFolders as $oneFolder) {
            $cssFiles[ACYM_TEMPLATE.$newTemplateFolder.DS.$oneFolder] = acym_getFiles(ACYM_TEMPLATE.$newTemplateFolder.DS.$oneFolder, '\.css$');
        }

        foreach ($cssFiles as $cssFolder => $cssFile) {
            if (empty($cssFile)) continue;
            $newTemplate->stylesheet .= "\n".acym_fileGetContent($cssFolder.DS.reset($cssFile));
        }

        if (!empty($newTemplate->stylesheet)) {
            if (preg_match('#body *\{[^\}]*background-color:([^;\}]*)[;\}]#Uis', $newTemplate->stylesheet, $backgroundresults)) {
                $newTemplate->stylesheet = preg_replace('#(body *\{[^\}]*)background-color:[^;\}]*[;\}]#Uis', '$1', $newTemplate->stylesheet);
            }

            $quickstyle = [
                'tag_h1' => 'h1',
                'tag_h2' => 'h2',
                'tag_h3' => 'h3',
                'tag_h4' => 'h4',
                'tag_h5' => 'h5',
                'tag_h6' => 'h6',
                'tag_a' => 'a',
                'tag_ul' => 'ul',
                'tag_li' => 'li',
                'acym_unsub' => '\.acym_unsub',
                'acym_online' => '\.acym_online',
                'acym_title' => '\.acym_title',
                'acym_content' => '\.acym_content',
                'acym_readmore' => '\.acym_readmore',
            ];
            foreach ($quickstyle as $styledb => $oneStyle) {
                if (preg_match('#[^a-z\. ,] *'.$oneStyle.' *{([^}]*)}#Uis', $newTemplate->stylesheet, $quickstyleresults)) {
                    $newTemplate->stylesheet = str_replace($quickstyleresults[0], '', $newTemplate->stylesheet);
                }
            }
        }

        $foldersForPicts = [$newTemplateFolder];
        $otherFolders = acym_getFolders(ACYM_TEMPLATE.$newTemplateFolder);
        foreach ($otherFolders as $oneFold) {
            $foldersForPicts[] = $newTemplateFolder.DS.$oneFold;
        }
        $allPictures = [];
        foreach ($foldersForPicts as $oneFolder) {
            $allPictures[$oneFolder] = acym_getFiles(ACYM_TEMPLATE.$oneFolder);
        }

        $uploadsFolder = ACYM_UPLOAD_FOLDER_THUMBNAIL;

        $newConfig = new \stdClass();
        $thumbNb = intval($this->config->get('numberThumbnail', 2));

        foreach ($allPictures as $folder => $pictfolders) {
            foreach ($pictfolders as $onePict) {
                if (!preg_match('#\.(jpg|gif|png|jpeg|ico|bmp)$#i', $onePict)) continue;
                if (preg_match('#(thumbnail|screenshot|muestra)#i', $onePict)) {
                    $thumbNb++;
                    $newNamePict = 'thumbnail_'.$thumbNb.'.png';
                    copy(ACYM_TEMPLATE.str_replace(DS, '/', $folder).'/'.$onePict, $uploadsFolder.$newNamePict);
                    $newTemplate->thumbnail = $newNamePict;
                }
            }
        }

        $newConfig->numberThumbnail = $thumbNb;
        $this->config->save($newConfig);

        $newTemplate->drag_editor = strpos($newTemplate->body, 'acym__wysid__template__content') !== false ? 1 : 0;
        $newTemplate->type = $this::TYPE_TEMPLATE;
        $newTemplate->creation_date = acym_date('now', 'Y-m-d H:i:s', false);

        $tempid = $this->save($newTemplate);

        $this->templateId = $tempid;
        $this->templateNames[] = $newTemplate->name;

        return true;
    }

    public function sendAutomation($mailId, $userIds, $sendingDate, $automationAdmin = [])
    {
        if (empty($mailId)) return acym_translationSprintf('ACYM_EMAILS_ADDED_QUEUE', 0);
        if (empty($sendingDate)) return acym_translation('ACYM_WRONG_DATE');
        if (empty($userIds)) return acym_translation('ACYM_USER_NOT_FOUND');
        acym_arrayToInteger($userIds);

        // If we send a notification
        if (isset($automationAdmin['automationAdmin']) && $automationAdmin['automationAdmin']) {
            $userClass = new UserClass();
            $mail = $this->getOneById($mailId);
            $user = $userClass->getOneById($automationAdmin['user_id']);

            if (empty($mail) || empty($user)) return false;

            // Get the current user values
            $mailerHelper = new MailerHelper();
            $pluginHelper = new PluginHelper();
            $extractedTags = $pluginHelper->extractTags($mail, 'subscriber');
            if (!empty($extractedTags)) {
                foreach ($extractedTags as $dtext => $oneTag) {
                    if (empty($oneTag->info) || $oneTag->info != 'current' || empty($user->{$oneTag->id})) continue;

                    $mailerHelper->addParam(str_replace(['{', '}'], '', $dtext), $user->{$oneTag->id});
                }
            }

            $mailSent = 0;

            foreach ($userIds as $userId) {
                if ($mailerHelper->sendOne($mail->id, $userId)) $mailSent++;
            }

            return $mailSent;
        }

        $result = acym_query(
            'INSERT IGNORE INTO #__acym_queue (`mail_id`, `user_id`, `sending_date`) 
                SELECT '.intval($mailId).', user.id, '.acym_escapeDB($sendingDate).' 
                FROM #__acym_user AS user 
                WHERE user.active = 1 AND user.id IN ('.implode(',', $userIds).')'
        );


        $mailStatClass = new MailStatClass();
        $mailStat = $mailStatClass->getOneRowByMailId($mailId);

        if (empty($mailStat)) {
            $mailStat = new \stdClass();
            $mailStat->mail_id = intval($mailId);
            $mailStat->total_subscribers = intval($result);
            $mailStat->send_date = $sendingDate;
        } else {
            $mailStat->total_subscribers += intval($result);
        }

        unset($mailStat->sent);
        $mailStatClass->save($mailStat);

        if ($result === 0) {
            return acym_translation('ACYM_CAMPAIGN_ALREADY_QUEUED');
        }

        return $result;
    }

    /**
     * Encode array of mails
     *
     * @param array $mails
     *
     * @return array
     */
    public function encode($mails = [])
    {
        $isArray = true;
        if (!is_array($mails)) {
            $mails = [$mails];

            $isArray = false;
        }

        $encodedMails = array_map([$this, 'removePoweredByAcyMailing'], $mails);

        $encodedMails = array_map([$this, 'utf8Encode'], $encodedMails);

        return $isArray ? $encodedMails : $encodedMails[0];
    }

    /**
     * Decode array of mails
     *
     * @param array $mails
     *
     * @return mixed
     */
    public function decode($mails = [])
    {
        $isArray = true;
        if (!is_array($mails)) {
            $mails = [$mails];

            $isArray = false;
        }

        $decodedMails = array_map([$this, 'utf8Decode'], $mails);

        $decodedMails = array_map([$this, 'addPoweredByAcyMailing'], $decodedMails);

        foreach ($decodedMails as $i => $oneMail) {
            if (!isset($oneMail->access) || is_array($oneMail->access)) continue;
            $decodedMails[$i]->access = empty($oneMail->access) ? '' : explode(',', $oneMail->access);
        }

        return $isArray ? $decodedMails : $decodedMails[0];
    }

    /**
     * Decode one mail from UTF8. (decode only attributes defined in $fieldsToDecode)
     *
     * @param $mail
     *
     * @return mixed
     */
    protected function utf8Decode($mail)
    {
        if (!empty($mail)) {
            foreach (self::FIELDS_ENCODING as $oneField) {

                if (is_array($mail)) {
                    if (empty($mail[$oneField])) continue;
                    $value = &$mail[$oneField];
                } else {
                    if (empty($mail->$oneField)) continue;
                    $value = &$mail->$oneField;
                }

                $value = utf8_decode($value);
            }
        }

        return $mail;
    }

    protected function addPoweredByAcyMailing($mail)
    {
        if (empty($mail->body)) return $mail;
        //If we don't display the powered by we remove it
        if ($this->config->get('display_built_by', 0) != 1) {
            if (strpos($mail->body, 'acym__powered_by_acymailing') !== false) {
                $mailBodyDom = new \DOMDocument();
                @$mailBodyDom->loadHTML('<?xml encoding="utf-8" ?>'.$mail->body);
                $tables = $mailBodyDom->getElementsByTagName('table');
                foreach ($tables as $table) {
                    if ($table->getAttribute('id') != 'acym__powered_by_acymailing') continue;
                    $table->parentNode->removeChild($table);
                    break;
                }
                $mail->body = $mailBodyDom->saveHTML();
            }

            return $mail;
        }
        if (empty($mail->body)) return $mail;
        if (strpos($mail->body, 'acym__powered_by_acymailing') !== false) return $mail;

        $isWysidEditor = strpos($mail->body, 'acym__wysid__template') !== false;

        $urlPoweredByImage = ACYM_IMAGES.'poweredby_black.png';

        $poweredByHTML = '<p id="acym__powered_by_acymailing">';
        $poweredByHTML .= '<a href="https://www.acymailing.com/?utm_campaign=powered_by_v7&utm_source=acymailing_plugin" target="blank">';
        $poweredByHTML .= '<img alt="Email built with AcyMailing" height="40" width="199" style="height: 40px; width:199px; max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block; margin-left: auto; margin-right: auto;" src="'.$urlPoweredByImage.'"/>';
        $poweredByHTML .= '</a></p>';
        $poweredByWYSID = <<<CONTENT
<table id="acym__powered_by_acymailing" class="row" bgcolor="#ffffff" style="background-color: transparent" cellpadding="0" cellspacing="0" border="0">
    <tbody bgcolor style="background-color: inherit;">
        <tr>
            <th class="small-12 medium-12 large-12 columns" valign="top" style="height: auto;">
                <table border="0" cellpadding="0" cellspacing="0"
                    style="min-height: 0px; display: table; height: auto;">
                    <tbody style="min-height: 0px; display: table-row-group;">
                        <tr
                            style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">
                            <td class="large-12">
                                <div style="position: relative;">
                                    <p style="word-break: break-word; text-align: center;">
                                    <a href="https://www.acymailing.com/?utm_campaign=powered_by_v7&utm_source=acymailing_plugin" target="_blank">
                                        <img src="$urlPoweredByImage"
                                            title="poweredby" alt=""
                                            style="height: 40px; width:199px; max-width: 100%; height: auto; box-sizing: border-box; padding: 0px 5px; display: inline-block; margin-left: auto; margin-right: auto;"
                                            height="40" width="199">
                                    </a>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </th>
        </tr>
    </tbody>
</table>
CONTENT;

        if (!$isWysidEditor) {
            $mail->body = $mail->body.$poweredByHTML;
        } else {

            $mailBodyDom = new \DOMDocument();
            //Some inserted content add specific tags that shows warnings
            @$mailBodyDom->loadHTML('<?xml encoding="utf-8" ?>'.$mail->body);

            $htmlToAddDom = new \DOMDocument();
            @$htmlToAddDom->loadHTML('<?xml encoding="utf-8" ?>'.$poweredByWYSID);
            $tableToAdd = $htmlToAddDom->getElementById('acym__powered_by_acymailing');

            $tds = $mailBodyDom->getElementsByTagName('td');
            foreach ($tds as $td) {
                $classes = explode(' ', $td->getAttribute('class'));
                if (!in_array('acym__wysid__row', $classes)) continue;
                if (!empty($tableToAdd)) $td->appendChild($mailBodyDom->importNode($tableToAdd, true));
                break;
            }
            $mail->body = $mailBodyDom->saveHTML();
        }

        return $mail;
    }

    protected function removePoweredByAcyMailing($mail)
    {
        if (empty($mail->body)) return $mail;

        if (strpos($mail->body, 'acym__wysid__template') !== false) return $mail;

        $regexPoweredByAcyMailing = '#<p id="acym__powered_by_acymailing.*<\/p>#U';

        $mail->body = preg_replace($regexPoweredByAcyMailing, '', $mail->body);

        return $mail;
    }

    /**
     * Encode one mail in UTF8 for handling specific characters as emoji. (encode only attributes defined in $fieldsToEncode)
     *
     * @param $mail
     *
     * @return mixed
     */
    protected function utf8Encode($mail)
    {
        if (!empty($mail)) {
            foreach (self::FIELDS_ENCODING as $oneField) {

                if (is_array($mail)) {
                    if (empty($mail[$oneField])) continue;
                    $value = &$mail[$oneField];
                } else {
                    if (empty($mail->$oneField)) continue;
                    $value = &$mail->$oneField;
                }

                $value = utf8_encode($value);
            }
        }

        return $mail;
    }

    public function getTranslationId($parentId, $langCode)
    {
        return acym_loadResult(
            'SELECT `id` 
            FROM #__acym_mail 
            WHERE `parent_id` = '.intval($parentId).' 
                AND `language` = '.acym_escapeDB($langCode)
        );
    }

    public function getTranslationsById($mailId, $full = false, $includeParent = false)
    {
        $data = $full ? '*' : '`language`, `subject`, `preheader`, `body`, `autosave`';
        $where = $includeParent ? ' OR `id` = '.intval($mailId) : '';

        return $this->decode(
            acym_loadObjectList(
                'SELECT '.$data.' 
                FROM #__acym_mail 
                WHERE `parent_id` = '.intval($mailId).$where,
                'language'
            )
        );
    }

    public function deleteByTranslationLang($languageCodes)
    {
        if (!is_array($languageCodes)) $languageCodes = [$languageCodes];
        if (empty($languageCodes)) return;

        foreach ($languageCodes as $key => $oneLangCode) {
            $languageCodes[$key] = acym_escapeDB($oneLangCode);
        }

        $this->delete(
            acym_loadResultArray(
                'SELECT `id` 
                FROM #__acym_mail 
                WHERE `parent_id` IS NOT NULL 
                    AND `language` IN ('.implode(', ', $languageCodes).')'
            )
        );
    }

    /**
     * Get all multilingual mails linked to a parent mail, also get the parent mail
     *
     * @param $parentId
     *
     * @return array
     */
    public function getMultilingualMails($parentId)
    {
        $mails = $this->decode(
            acym_loadObjectList(
                'SELECT * FROM #__acym_mail WHERE parent_id = '.intval($parentId).' OR id = '.intval($parentId),
                'language'
            )
        );

        return $this->translateMailSettings($mails);
    }

    /**
     * Get all multilingual mails linked to a parent mail, also get the parent mail by name
     *
     * @param $parentName
     *
     * @return array
     */
    public function getMultilingualMailsByName($parentName)
    {
        $parentName = utf8_encode($parentName);
        $query = 'SELECT mail2.* FROM #__acym_mail AS mail LEFT JOIN #__acym_mail AS mail2 ON mail.id = mail2.parent_id OR mail2.id = mail.id WHERE mail.name = '.acym_escapeDB(
                $parentName
            );

        $mails = $this->decode(acym_loadObjectList($query, 'language'));

        return $this->translateMailSettings($mails);
    }

    private function translateMailSettings($mails)
    {
        if (empty($mails)) return $mails;

        $defaultLanguage = $this->config->get('multilingual_default');

        if (empty($mails[$defaultLanguage])) {
            $firstMail = reset($mails);
            if (empty($firstMail) || empty($firstMail->parent_id)) return $mails;
            $mainMail = $this->getOneById($firstMail->parent_id);
        } else {
            $mainMail = $mails[$defaultLanguage];
        }

        if (!empty($mainMail->translation)) {
            $translation = json_decode($mainMail->translation, true);

            foreach ($mails as $lang => $mail) {
                if (empty($mail->parent_id) || empty($translation[$lang])) continue;

                if (!empty($translation[$lang]['from_name'])) $mails[$lang]->from_name = $translation[$lang]['from_name'];
                if (!empty($translation[$lang]['from_email'])) $mails[$lang]->from_email = $translation[$lang]['from_email'];
                if (!empty($translation[$lang]['reply_to_name'])) $mails[$lang]->reply_to_name = $translation[$lang]['reply_to_name'];
                if (!empty($translation[$lang]['reply_to_email'])) $mails[$lang]->reply_to_email = $translation[$lang]['reply_to_email'];
            }
        }

        return $mails;
    }

    public function getMailAttachments($mailId)
    {
        return acym_loadResult('SELECT attachments FROM #__acym_mail WHERE id = '.intval($mailId));
    }

    public function isTransactionalMail($mail)
    {
        if ($mail->type == self::TYPE_STANDARD) return false;

        if ($mail->type == self::TYPE_AUTOMATION) {
            $query = 'SELECT step.triggers FROM #__acym_step AS step
                        JOIN #__acym_condition AS acym_condition ON acym_condition.step_id = step.id
                        JOIN #__acym_action AS action ON action.condition_id = acym_condition.id AND action.actions LIKE '.acym_escapeDB('%"acy_add_queue":{"mail_id":"13"%');

            return empty(acym_loadResult($query));
        }

        return true;
    }

    public function getAutomaticMailIds($mailIds)
    {
        acym_arrayToInteger($mailIds);

        $query = 'SELECT DISTINCT campaign2.mail_id 
                  FROM #__acym_campaign AS campaign1 
                  JOIN #__acym_campaign AS campaign2 ON campaign1.id = campaign2.parent_id 
                  WHERE campaign1.mail_id IN ('.implode(',', $mailIds).')';
        $generatedMailIds = acym_loadResultArray($query);

        if (empty($generatedMailIds)) return [];

        acym_arrayToInteger($generatedMailIds);

        return $generatedMailIds;
    }

    public function getMultilingualMailIds($mailIds)
    {
        acym_arrayToInteger($mailIds);

        $query = 'SELECT DISTINCT mail2.id 
                  FROM #__acym_mail AS mail1 
                  JOIN #__acym_mail AS mail2 ON mail1.id = mail2.parent_id 
                  WHERE mail1.id IN ('.implode(',', $mailIds).')';

        $multipleMailIds = acym_loadResultArray($query);

        if (empty($multipleMailIds)) return [];

        acym_arrayToInteger($multipleMailIds);

        return $multipleMailIds;
    }
}
