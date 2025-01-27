<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class TagClass extends AcymClass
{
    const TYPE_MAIL = 'mail';
    const TYPE_LIST = 'list';

    public function __construct()
    {
        parent::__construct();

        $this->table = 'tag';
        $this->pkey = 'id';
    }

    /**
     * Attaches the tags passed in parameter to the specified element
     */
    public function setTags(string $type, int $elementId, array $newTags): void
    {
        // Remove the old tags from the element
        acym_query('DELETE FROM #__acym_tag WHERE `type` = '.acym_escapeDB($type).' AND `id_element` = '.intval($elementId));

        $tagsToInsertQuery = [];

        foreach ($newTags as $oneTag) {
            $newTag = new \stdClass();
            $newTag->type = $type;

            if (strpos($oneTag, 'acy_new_tag_') !== false) {
                $tagName = substr($oneTag, 12);
                if (empty($tagName)) {
                    continue;
                }

                $newTag->name = $tagName;
            } else {
                $newTag->name = $oneTag;
            }
            $tagsToInsertQuery[] = '('.acym_escapeDB($newTag->name).','.acym_escapeDB($newTag->type).', '.intval($elementId).')';
        }

        if (!empty($tagsToInsertQuery)) {
            acym_query('INSERT INTO #__acym_tag (`name`, `type`, `id_element`) VALUES '.implode(',', $tagsToInsertQuery));
        }
    }

    public function getAllTagsByType(string $type): array
    {
        return acym_loadObjectList(
            'SELECT `name` AS `value`, `name` 
            FROM #__acym_tag 
            WHERE `type` = '.acym_escapeDB($type).' 
            GROUP BY `name`'
        );
    }

    public function getAllTagsByElementId(string $type, int $id): array
    {
        if (empty($id)) {
            return [];
        }

        $tags = acym_loadResultArray(
            'SELECT * 
            FROM #__acym_tag 
            WHERE `type` = '.acym_escapeDB($type).' 
                AND `id_element` = '.intval($id)
        );

        return empty($tags) ? [] : $tags;
    }

    public function getAllTagsByTypeAndElementIds(string $type, array $ids): array
    {
        acym_arrayToInteger($ids);
        if (empty($ids)) {
            return [];
        }

        return acym_loadObjectList(
            'SELECT * 
            FROM #__acym_tag 
            WHERE `type` = '.acym_escapeDB($type).' 
                AND `id_element` IN ('.implode(',', $ids).')'
        );
    }

    public function getAllTagsForSelect(): array
    {
        return acym_loadObjectList(
            'SELECT DISTINCT `name` 
            FROM #__acym_tag 
            ORDER BY `name` ASC'
        );
    }

    public function deleteByName(string $name): void
    {
        acym_query('DELETE FROM #__acym_tag WHERE `name` = '.acym_escapeDB($name));
    }
}
