<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class MailArchiveClass extends acymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'mail_archive';
        $this->pkey = 'id';
    }

    public function save($mailArchiveToSave)
    {
        $mailArchive = clone $mailArchiveToSave;

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
        return acym_loadObject('SELECT * FROM #__acym_mail_archive WHERE `mail_id` = '.intval($mailId));
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

        return [
            'status' => $status !== false,
            'message' => $message,
        ];
    }
}
