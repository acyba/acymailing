<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Libraries\acymClass;

class MailboxClass extends acymClass
{
    var $table = 'mailbox_action';
    var $pkey = 'id';

    public function getMatchingElements($settings = [])
    {
        $query = 'SELECT * FROM #__acym_mailbox_action';
        $queryCount = 'SELECT COUNT(id) AS total, SUM(active) AS totalActive FROM #__acym_mailbox_action';

        if (!empty($settings['search'])) {
            $filters[] = 'name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if (!empty($filters)) {
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['status'])) {
            $filters[] = 'active = '.($settings['status'] == 'active' ? '1' : '0');
        }

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $query .= ' ORDER BY '.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
        } else {
            $query .= ' ORDER BY id asc';
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $results['elements'] = acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']);
        $results['total'] = acym_loadObject($queryCount);

        return $results;
    }

    public function duplicate($mailboxIds)
    {
        if (empty($mailboxIds)) {
            return;
        }

        if (!is_array($mailboxIds)) {
            $mailboxIds = [$mailboxIds];
        }

        $duplicateErrors = [];
        foreach ($mailboxIds as $mailboxId) {
            $mailbox = $this->getOneById($mailboxId);

            if (empty($mailbox)) {
                continue;
            }

            $originalName = $mailbox->name;
            unset($mailbox->id);
            $mailbox->active = 0;
            $mailbox->name .= '_copy';

            if (empty($this->save($mailbox))) {
                $duplicateErrors[] = acym_translationSprintf('ACYM_ERROR_DUPLICATING_MAILBOX_ACTION', $originalName);
            }
        }

        if (!empty($duplicateErrors)) {
            acym_enqueueMessage($duplicateErrors, 'error');
        }
    }

    /**
     * @param int $id
     *
     * @return object
     */
    public function getOneById($id)
    {
        $mailbox = acym_loadObject('SELECT * FROM #__acym_mailbox_action WHERE `id` = '.intval($id));

        if (!empty($mailbox)) {
            $mailbox->conditions = json_decode($mailbox->conditions, true);
            $mailbox->actions = json_decode($mailbox->actions, true);
        }

        return $mailbox;
    }

    public function getAllActiveReadyWithActions(): array
    {
        $mailboxes = acym_loadObjectList('SELECT * FROM #__acym_mailbox_action WHERE active = 1 AND nextdate < '.time().' AND actions IS NOT NULL ORDER BY id ASC');

        if (empty($mailboxes)) {
            return [];
        }

        return array_map([$this, 'decodeMailbox'], $mailboxes);
    }

    public function decodeMailbox($mailbox)
    {
        $mailbox->conditions = json_decode($mailbox->conditions, true);
        $mailbox->actions = json_decode($mailbox->actions, true);

        return $mailbox;
    }
}
