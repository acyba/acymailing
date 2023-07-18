<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class MailArchiveClass extends acymClass
{
    public $table = 'mail_archive';
    public $pkey = 'id';

    const FIELDS_ENCODING = ['subject', 'body'];

    public function save($mailArchiveToSave)
    {
        $mailArchive = clone $mailArchiveToSave;
        $mailArchive = $this->utf8Encode($mailArchive);

        foreach ($mailArchive as $oneAttribute => $value) {
            if (empty($value) || $oneAttribute === 'settings') {
                continue;
            }

            if (is_array($value)) {
                $mailArchive->$oneAttribute = json_encode($value);
            }
        }

        return parent::save($mailArchive);
    }

    public function getOneByMailId($mailId)
    {
        return $this->utf8Decode(acym_loadObject('SELECT * FROM #__acym_mail_archive WHERE `mail_id` = '.intval($mailId)));
    }

    /**
     * Encode one mail archive in UTF8 for handling specific characters as emoji.
     *
     * @param $mailArchive
     *
     * @return mixed
     */
    protected function utf8Encode($mailArchive)
    {
        foreach (self::FIELDS_ENCODING as $oneField) {
            if (is_array($mailArchive)) {
                if (empty($mailArchive[$oneField])) continue;
                $value = &$mailArchive[$oneField];
            } else {
                if (empty($mailArchive->$oneField)) continue;
                $value = &$mailArchive->$oneField;
            }

            $value = acym_utf8Encode($value);
        }

        return $mailArchive;
    }

    /**
     * Decode one mail archive from UTF8.
     *
     * @param $mailArchive
     *
     * @return mixed
     */
    protected function utf8Decode($mailArchive)
    {
        if (!empty($mailArchive)) {
            foreach (self::FIELDS_ENCODING as $oneField) {
                if (is_array($mailArchive)) {
                    if (empty($mailArchive[$oneField])) continue;
                    $value = &$mailArchive[$oneField];
                } else {
                    if (empty($mailArchive->$oneField)) continue;
                    $value = &$mailArchive->$oneField;
                }

                $value = acym_utf8Decode($value);
            }
        }

        return $mailArchive;
    }

    public function deleteArchivePeriod(): array
    {
        if (empty($this->config->get('delete_archive_history_enabled', 1))) {
            return [];
        }

        $deleteOverSecond = $this->config->get('delete_archive_history_after', 86400 * 90);
        if (empty($deleteOverSecond)) {
            return [];
        }

        try {
            $status = acym_query('DELETE FROM #__acym_mail_archive WHERE `date` < '.intval(time() - $deleteOverSecond));
            $message = empty($status) ? '' : acym_translationSprintf('ACYM_DELETE_X_ROWS_TABLE_X', $status, strtolower(acym_translation('ACYM_EMAIL_ARCHIVE_HISTORY')));
        } catch (\Exception $e) {
            $status = false;
            $message = $e->getMessage();
        }

        return ['status' => $status !== false, 'message' => $message];
    }
}
