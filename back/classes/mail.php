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

        if (!empty($settings['type'])) {
            if ($settings['type'] == 'custom') {
                $filters[] .= 'mail.library = 0';
            } else {
                $filters[] .= 'mail.library = 1';
            }
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
            $filters[] = 'mail.type = '.acym_escapeDB($this::TYPE_STANDARD);
        }

        $filters[] = 'mail.parent_id IS NULL';

        if (empty($settings['automation'])) {
            $filters[] = 'mail.template = 1';
        }

        if (!empty($settings['drag_editor'])) {
            $filters[] = 'mail.drag_editor = 1';
        }

        if (!empty($settings['creator_id'])) {
            $userGroups = acym_getGroupsByUser($settings['creator_id']);
            $groupCondition = '(mail.access LIKE "%,'.implode(',%" OR mail.access LIKE "%,', $userGroups).',%")';
            $filter = 'mail.creator_id = '.intval($settings['creator_id']).' OR (mail.template = 1 AND '.$groupCondition.')';
            if (!acym_isAdmin() && !empty($settings['element_tab'])) {
                $listGroup = $groupCondition = '(list.access LIKE "%,'.implode(',%" OR list.access LIKE "%,', $userGroups).',%")';
                $listFilter = 'list.cms_user_id = '.intval($settings['creator_id']).' OR '.$listGroup;
                $filter = '(mail.creator_id = '.intval($settings['creator_id']).' OR (mail.template = 1 AND '.$groupCondition.')) OR '.$listFilter;
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
     * @param int $id
     *
     * @return object
     */
    public function getOneById($id)
    {
        $mail = $this->decode(acym_loadObject('SELECT * FROM #__acym_mail WHERE id = '.intval($id)));

        if (!empty($mail)) {
            $tagsClass = new TagClass();
            $mail->tags = $tagsClass->getAllTagsByElementId('mail', $id);
        }

        if (isset($mail->access) && !is_array($mail->access)) $mail->access = explode(',', $mail->access);

        return $mail;
    }

    /**
     * @param string $name
     *
     * @return object
     */
    public function getOneByName($name, $library = false)
    {
        $query = 'SELECT * FROM #__acym_mail WHERE `parent_id` IS NULL AND `name` = '.acym_escapeDB(utf8_encode($name));
        if ($library) $query .= ' AND `library` = 1';

        $mail = $this->decode(acym_loadObject($query));

        if (!empty($mail)) {
            $tagsClass = new TagClass();
            $mail->tags = $tagsClass->getAllTagsByElementId('mail', $mail->id);
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

    public function getAllListsByMailId($id)
    {
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
            acym_raiseError(E_WARNING, 'SOME_ERROR_CODE', acym_translation('WARNINSTALLZLIB'));

            return false;
        }

        $filename = strtolower(acym_makeSafeFile($importFile['name']));
        $extension = strtolower(substr($filename, strrpos($filename, '.') + 1));

        if (!in_array($extension, ['zip', 'tar.gz'])) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_ACCEPTED_TYPE', $extension, 'zip,tar.gz'), 'error');

            return false;
        }

        $jpath = acym_getCMSConfig('tmp_path', ACYM_MEDIA.'tmp'.DS);
        $tmp_dest = acym_cleanPath($jpath.DS.$filename);
        $tmp_src = $importFile['tmp_name'];

        $uploaded = acym_uploadFile($tmp_src, $tmp_dest);
        if (!$uploaded) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_7', $tmp_src, $tmp_dest), 'error');

            return false;
        }

        $tmpdir = uniqid().'_template';

        $extractdir = acym_cleanPath(dirname($tmp_dest).DS.$tmpdir);

        $result = acym_extractArchive($tmp_dest, $extractdir);
        acym_deleteFile($tmp_dest);

        $allFiles = acym_getFiles($extractdir, '.', true, true, [], []);
        foreach ($allFiles as $oneFile) {
            if (preg_match('#\.(jpg|gif|png|jpeg|ico|bmp|html|htm|css)$#i', $oneFile)) {
                continue;
            }
            if (acym_deleteFile($oneFile)) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_8', $oneFile), 'warning');
            }
        }

        if (!$result) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_9', $tmp_dest, $extractdir), 'error');

            return false;
        }

        if ($this->detecttemplates($extractdir)) {

            $messages = $this->templateNames;
            array_unshift($messages, acym_translationSprintf('ACYM_TEMPLATES_INSTALL', count($this->templateNames)));
            acym_enqueueMessage($messages, 'success');
            if (is_dir($extractdir)) acym_deleteFolder($extractdir);

            return true;
        }

        acym_enqueueMessage(acym_translationSprintf('ACYM_FILE_UPLOAD_ERROR_10'), 'error');
        if (is_dir($extractdir)) acym_deleteFolder($extractdir);

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
            ],
            [
                'src="'.ACYM_TEMPLATE_URL.$newTemplateFolder.'/',
                'src="'.ACYM_TEMPLATE_URL,
                'src="'.ACYM_TEMPLATE_URL.$newTemplateFolder.'/images/',
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

        $newTemplate->drag_editor = 0;
        $newTemplate->type = $this::TYPE_STANDARD;
        $newTemplate->template = 1;
        $newTemplate->library = 0;
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
            $mailerHelper = new MailerHelper();
            $mail = $this->getOneById($mailId);
            $user = $userClass->getOneById($automationAdmin['user_id']);

            if (empty($mail) || empty($user)) return false;

            // Get the current user values
            $pluginHelper = new PluginHelper();
            $extractedTags = $pluginHelper->extractTags($mail, 'subtag');
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

        $return = array_map([$this, 'utf8Encode'], $mails);

        return $isArray ? $return : $return[0];
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

        $return = array_map([$this, 'utf8Decode'], $mails);

        foreach ($return as $i => $oneMail) {
            if (!isset($oneMail->access) || is_array($oneMail->access)) continue;
            $return[$i]->access = empty($oneMail->access) ? '' : explode(',', $oneMail->access);
        }

        return $isArray ? $return : $return[0];
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

            if (!empty($mail->name) && $mail->name === 'acy_confirm') {
                $mail->name = acym_translation('ACYM_CONFIRMATION_EMAIL');
            }
            //TODO: Also translate the other core mails names
        }

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
        return $this->decode(acym_loadObjectList('SELECT * FROM #__acym_mail WHERE parent_id = '.intval($parentId).' OR id = '.intval($parentId), 'language'));
    }

    public function getMailAttachments($mailId)
    {
        return acym_loadResult('SELECT attachments FROM #__acym_mail WHERE id = '.intval($mailId));
    }
}
