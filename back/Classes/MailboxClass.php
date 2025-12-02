<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Core\AcymClass;

class MailboxClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'mailbox_action';
        $this->pkey = 'id';
    }

    public function getMatchingElements(array $settings = []): array
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

        return [
            'elements' => acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']),
            'total' => acym_loadObject($queryCount),
        ];
    }

    public function duplicate(array $mailboxIds): void
    {
        if (empty($mailboxIds)) {
            return;
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
            $mailbox->conditions = json_encode($mailbox->conditions);
            $mailbox->actions = json_encode($mailbox->actions);

            if (empty($this->save($mailbox))) {
                $duplicateErrors[] = acym_translationSprintf('ACYM_ERROR_DUPLICATING_MAILBOX_ACTION', $originalName);
            }
        }

        if (!empty($duplicateErrors)) {
            acym_enqueueMessage($duplicateErrors, 'error');
        }
    }

    public function getOneById(int $id): ?object
    {
        $mailbox = acym_loadObject('SELECT * FROM #__acym_mailbox_action WHERE `id` = '.intval($id));

        if (empty($mailbox)) {
            return null;
        }

        $mailbox->conditions = json_decode($mailbox->conditions, true);
        $mailbox->actions = json_decode($mailbox->actions, true);

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

    private function decodeMailbox(object $mailbox): object
    {
        $mailbox->conditions = json_decode($mailbox->conditions, true);
        $mailbox->actions = json_decode($mailbox->actions, true);

        return $mailbox;
    }
}
